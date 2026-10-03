<?php

namespace App\Livewire\Settings;

use App\Models\Course;
use App\Models\Level;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Courses & skills')]
class Curriculum extends Component
{
    #[Url(as: 'course', except: null)]
    public ?int $courseId = null;

    public function mount(): void
    {
        $this->courseId ??= Course::orderBy('name')->value('id');
    }

    public function addCourse(string $name): void
    {
        if ($name = $this->clean($name)) {
            $this->courseId = Course::create(['name' => $name])->id;
        }
    }

    public function renameCourse(string $name): void
    {
        if ($name = $this->clean($name)) {
            Course::findOrFail($this->courseId)->update(['name' => $name]);
        }
    }

    public function toggleCourse(): void
    {
        $course = Course::findOrFail($this->courseId);
        $course->update(['active' => ! $course->active]);
    }

    public function addLevel(string $name): void
    {
        if ($name = $this->clean($name)) {
            $course = Course::findOrFail($this->courseId);
            $course->levels()->create(['name' => $name, 'sort_order' => $course->levels()->max('sort_order') + 1]);
        }
    }

    public function addSkill(int $levelId, string $name): void
    {
        if ($name = $this->clean($name)) {
            $level = Level::findOrFail($levelId);
            $level->skills()->create(['name' => $name, 'sort_order' => $level->skills()->max('sort_order') + 1]);
        }
    }

    public function rename(string $type, int $id, string $name): void
    {
        if ($name = $this->clean($name)) {
            $this->model($type, $id)->update(['name' => $name]);
        }
    }

    public function remove(string $type, int $id): void
    {
        $this->model($type, $id)->delete();
    }

    /** Swaps an item with its neighbour above (-1) or below (+1). */
    public function move(string $type, int $id, int $direction): void
    {
        $item = $this->model($type, $id);
        $parentKey = $type === 'level' ? 'course_id' : 'level_id';
        $siblings = $item::where($parentKey, $item->{$parentKey})->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $siblings->search(fn ($s) => $s->id === $item->id);
        $other = $siblings->get($index + $direction);

        if (! $other) {
            return;
        }

        // Re-number everything so ties from older data don't stop the swap.
        $order = $siblings->pluck('id')->all();
        [$order[$index], $order[$index + $direction]] = [$order[$index + $direction], $order[$index]];
        foreach ($order as $position => $siblingId) {
            $item::whereKey($siblingId)->update(['sort_order' => $position + 1]);
        }
    }

    private function model(string $type, int $id): Model
    {
        return match ($type) {
            'level' => Level::whereHas('course', fn ($q) => $q->whereKey($this->courseId))->findOrFail($id),
            'skill' => Skill::whereHas('level', fn ($q) => $q->where('course_id', $this->courseId))->findOrFail($id),
        };
    }

    private function clean(string $name): string
    {
        return mb_substr(trim($name), 0, 255);
    }

    public function render()
    {
        return view('livewire.settings.curriculum', [
            'courses' => Course::orderByDesc('active')->orderBy('name')->get(),
            'course' => $this->courseId ? Course::with('levels.skills')->find($this->courseId) : null,
        ]);
    }
}
