<?php

namespace App\Livewire\Students;

use App\Enums\AttendanceStatus;
use App\Enums\InvoiceStatus;
use App\Enums\SkillStatus;
use App\Livewire\Concerns\RecordsPayments;
use App\Models\Course;
use App\Models\FeePlan;
use App\Models\Level;
use App\Models\Setting;
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
    use RecordsPayments, WithFileUploads;

    public Student $student;

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    #[Url(as: 'course', except: null)]
    public ?int $courseId = null;

    public $photo;

    public string $caption = '';

    public ?string $photoDate = null;

    public ?int $assessingLevelId = null;

    /** @var array<int, int|string> skill id => score 1–5 */
    public array $scores = [];

    public string $assessmentNote = '';

    public ?string $assessmentDate = null;

    public bool $addingCharge = false;

    public ?int $chargePlanId = null;

    public string $chargeLabel = '';

    public string $chargeAmount = '';

    public string $chargeDiscount = '';

    public ?string $chargeDue = null;

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

    public function startAssessment(int $levelId): void
    {
        $level = Level::with('skills')->findOrFail($levelId);

        $this->resetValidation();
        $this->assessingLevelId = $level->id;
        $this->scores = $level->skills->mapWithKeys(fn ($skill) => [$skill->id => 3])->all();
        $this->assessmentNote = '';
        $this->assessmentDate = now()->toDateString();
    }

    public function saveAssessment(): void
    {
        $this->validate([
            'scores' => 'required|array',
            'scores.*' => 'required|integer|between:1,5',
            'assessmentNote' => 'nullable|string|max:1000',
            'assessmentDate' => 'required|date|before_or_equal:today',
        ]);

        $level = Level::with('skills')->findOrFail($this->assessingLevelId);
        $skillIds = $level->skills->pluck('id')->all();

        $assessment = $this->student->assessments()->create([
            'level_id' => $level->id,
            'date' => $this->assessmentDate,
            'overall_note' => $this->assessmentNote ?: null,
        ]);

        foreach ($this->scores as $skillId => $score) {
            if (in_array((int) $skillId, $skillIds, true)) {
                $assessment->scores()->create(['skill_id' => $skillId, 'score' => $score]);
            }
        }

        $this->assessingLevelId = null;
    }

    public function deleteAssessment(int $id): void
    {
        $this->student->assessments()->findOrFail($id)->delete();
    }

    public function setEnrollmentPlan(int $enrollmentId, ?int $planId): void
    {
        $this->student->enrollments()->findOrFail($enrollmentId)
            ->update(['fee_plan_id' => $planId ?: null]);
    }

    public function updatedChargePlanId(): void
    {
        if ($plan = FeePlan::find($this->chargePlanId)) {
            $this->chargeLabel = $plan->type === 'monthly' ? now()->format('M Y') : $plan->name;
            $this->chargeAmount = (string) (float) $plan->amount;
        }
    }

    public function startCharge(): void
    {
        $this->resetValidation();
        $this->addingCharge = true;
        $this->chargePlanId = null;
        $this->chargeLabel = '';
        $this->chargeAmount = '';
        $this->chargeDiscount = '';
        $dueDay = min((int) Setting::get('fee_due_day'), now()->daysInMonth);
        $this->chargeDue = now()->day >= $dueDay ? now()->toDateString() : now()->day($dueDay)->toDateString();
    }

    public function saveCharge(): void
    {
        $data = $this->validate([
            'chargeLabel' => 'required|string|max:50',
            'chargeAmount' => 'required|numeric|min:1|max:1000000',
            'chargeDiscount' => 'nullable|numeric|min:0|lte:chargeAmount',
            'chargeDue' => 'required|date',
        ]);

        $this->student->invoices()->create([
            'period_label' => $data['chargeLabel'],
            'amount' => $data['chargeAmount'],
            'discount' => $data['chargeDiscount'] ?: 0,
            'due_date' => $data['chargeDue'],
            'status' => InvoiceStatus::Due,
        ]);

        $this->addingCharge = false;
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
            'assessments' => $this->tab === 'progress'
                ? $this->student->assessments()->with(['level', 'scores.skill'])
                    ->whereHas('level', fn ($q) => $q->where('course_id', $this->courseId))
                    ->orderByDesc('date')->orderByDesc('id')->get()
                : collect(),
            'samples' => $this->tab === 'samples' ? $this->student->samples()->orderByDesc('date')->orderByDesc('id')->get() : collect(),
            'enrollments' => $this->tab === 'fees' ? $this->student->enrollments()->where('status', 'active')->with('batch')->get() : collect(),
            'invoices' => $this->tab === 'fees' ? $this->student->invoices()->with('payments')->orderByDesc('due_date')->orderByDesc('id')->get() : collect(),
            'feePlans' => $this->tab === 'fees' ? FeePlan::orderBy('name')->get() : collect(),
        ])->title($this->student->name);
    }
}
