<div class="space-y-4">
    <x-back-header :href="route('more')" title="Courses & skills" />

    <div class="flex flex-col gap-2 sm:flex-row" x-data="{ adding: false, name: '' }">
        <select wire:model.live="courseId" class="input sm:w-72" aria-label="Course">
            @foreach ($courses as $c)
                <option value="{{ $c->id }}">{{ $c->name }}@unless ($c->active) (not in use) @endunless</option>
            @endforeach
        </select>
        <button type="button" class="btn-secondary" x-show="!adding" x-on:click="adding = true; $nextTick(() => $refs.newCourse.focus())">+ New course</button>
        <form x-show="adding" x-cloak class="flex flex-1 gap-2" x-on:submit.prevent="$wire.addCourse(name); name = ''; adding = false">
            <input x-ref="newCourse" x-model="name" type="text" placeholder="Course name" class="input flex-1">
            <button type="submit" class="btn-primary">Add</button>
        </form>
    </div>

    @if ($course)
        <section class="card space-y-3 p-4 lg:p-6">
            <label for="course-name" class="label">Course name</label>
            <div class="flex gap-2">
                <input id="course-name" type="text" value="{{ $course->name }}" wire:key="course-name-{{ $course->id }}" wire:change="renameCourse($event.target.value)" class="input flex-1">
                <button type="button" wire:click="toggleCourse" class="btn-secondary shrink-0">{{ $course->active ? 'Stop using' : 'Use again' }}</button>
            </div>
        </section>

        @foreach ($course->levels as $level)
            <section wire:key="level-{{ $level->id }}" class="card space-y-3 p-4 lg:p-6">
                <div class="flex items-center gap-2">
                    <input type="text" value="{{ $level->name }}" wire:change="rename('level', {{ $level->id }}, $event.target.value)" class="input flex-1 font-semibold" aria-label="Level name">
                    <button type="button" wire:click="move('level', {{ $level->id }}, -1)" class="btn-ghost" aria-label="Move level up" @disabled($loop->first)>↑</button>
                    <button type="button" wire:click="move('level', {{ $level->id }}, 1)" class="btn-ghost" aria-label="Move level down" @disabled($loop->last)>↓</button>
                    <button type="button" wire:click="remove('level', {{ $level->id }})" wire:confirm="Delete this level and its skills? Progress recorded for these skills will be removed." class="btn-ghost hover:text-danger" aria-label="Delete level">✕</button>
                </div>

                <ul class="space-y-2 sm:pl-4">
                    @foreach ($level->skills as $skill)
                        <li wire:key="skill-{{ $skill->id }}" class="flex items-center gap-1">
                            <input type="text" value="{{ $skill->name }}" wire:change="rename('skill', {{ $skill->id }}, $event.target.value)" class="input flex-1 py-2" aria-label="Skill name">
                            <button type="button" wire:click="move('skill', {{ $skill->id }}, -1)" class="btn-ghost" aria-label="Move skill up" @disabled($loop->first)>↑</button>
                            <button type="button" wire:click="move('skill', {{ $skill->id }}, 1)" class="btn-ghost" aria-label="Move skill down" @disabled($loop->last)>↓</button>
                            <button type="button" wire:click="remove('skill', {{ $skill->id }})" wire:confirm="Delete this skill? Progress recorded for it will be removed." class="btn-ghost hover:text-danger" aria-label="Delete skill">✕</button>
                        </li>
                    @endforeach
                </ul>

                <form class="flex gap-2 sm:pl-4" x-data="{ name: '' }" x-on:submit.prevent="if (name.trim()) { $wire.addSkill({{ $level->id }}, name); name = '' }">
                    <input x-model="name" type="text" placeholder="Add a skill" class="input flex-1 py-2">
                    <button type="submit" class="btn-secondary">Add</button>
                </form>
            </section>
        @endforeach

        <form class="card flex gap-2 p-4" x-data="{ name: '' }" x-on:submit.prevent="if (name.trim()) { $wire.addLevel(name); name = '' }">
            <input x-model="name" type="text" placeholder="New level name" class="input flex-1">
            <button type="submit" class="btn-primary">+ Add level</button>
        </form>
        <p class="text-sm text-muted">Changes save as soon as you leave a box.</p>
    @endif
</div>
