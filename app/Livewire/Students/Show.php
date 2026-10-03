<?php

namespace App\Livewire\Students;

use App\Enums\AttendanceStatus;
use App\Enums\SkillStatus;
use App\Models\Course;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkill;
use App\Support\ImageResizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public Student $student;

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    #[Url(as: 'course', except: null)]
    public ?int $courseId = null;

    public $photo;

    public string $caption = '';

    public ?string $photoDate = null;

    public function mount(Student $student): void
    {
        $this->student = $student;
        $this->courseId ??= $student->batches()->value('course_id') ?? Course::orderBy('name')->value('id');
        $this->photoDate = now()->toDateString();
    }

    /** Cycles a skill: not started → practising → mastered → not started. */
    public function cycleSkill(int $skillId): void
    {
        $skill = Skill::findOrFail($skillId);

        $record = StudentSkill::firstOrNew(['student_id' => $this->student->id, 'skill_id' => $skill->id]);
        $current = $record->status ?? SkillStatus::NotStarted;

        $record->status = match ($current) {
            SkillStatus::NotStarted => SkillStatus::Practising,
            SkillStatus::Practising => SkillStatus::Mastered,
            SkillStatus::Mastered => SkillStatus::NotStarted,
        };
        $record->updated_on = now()->toDateString();
        $record->save();

        unset($this->skillStatuses);
    }

    public function savePhoto(): void
    {
        $this->validate([
            'photo' => 'required|image|max:12288',
            'caption' => 'nullable|string|max:255',
            'photoDate' => 'required|date|before_or_equal:today',
        ]);

        $base = 'samples/'.$this->student->id.'/'.now()->format('Ymd-His').'-'.Str::random(6);
        $source = $this->photo->getRealPath();

        Storage::disk('local')->put("$base.jpg", ImageResizer::toJpeg($source, 1600));
        Storage::disk('local')->put("{$base}_thumb.jpg", ImageResizer::toJpeg($source, 480, 75));

        $this->student->samples()->create([
            'date' => $this->photoDate,
            'image_path' => "$base.jpg",
            'caption' => $this->caption ?: null,
        ]);

        $this->reset('photo', 'caption');
        $this->photoDate = now()->toDateString();
        $this->dispatch('photo-saved');
    }

    public function deletePhoto(int $sampleId): void
    {
        $this->student->samples()->findOrFail($sampleId)->delete();
    }

    #[Computed]
    public function skillStatuses()
    {
        return $this->student->skills()->get()->keyBy('skill_id');
    }

    public function render()
    {
        $since = now()->subDays(30)->startOfDay();
        $recent = $this->student->attendance()
            ->whereHas('classSession', fn ($q) => $q->where('date', '>=', $since))
            ->with('classSession.batch')
            ->get()
            ->sortByDesc(fn ($a) => $a->classSession->date);

        $attended = $recent->filter(fn ($a) => in_array($a->status, [AttendanceStatus::Present, AttendanceStatus::Late]))->count();

        return view('livewire.students.show', [
            'guardian' => $this->student->guardian,
            'batches' => $this->student->batches()->with('course')->get(),
            'recent' => $recent->take(10),
            'attendedCount' => $attended,
            'sessionCount' => $recent->count(),
            'courses' => Course::orderBy('name')->get(['id', 'name']),
            'course' => $this->tab === 'progress' && $this->courseId ? Course::with('levels.skills')->find($this->courseId) : null,
            'samples' => $this->tab === 'samples' ? $this->student->samples()->orderByDesc('date')->orderByDesc('id')->get() : collect(),
        ])->title($this->student->name);
    }
}
