<?php

namespace Tests\Feature;

use App\Livewire\Settings\Account;
use App\Livewire\Settings\Curriculum;
use App\Livewire\Settings\FeePlans;
use App\Livewire\Settings\School;
use App\Models\Course;
use App\Models\FeePlan;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['password' => 'old-password']);
        $this->actingAs($this->user);
    }

    public function test_settings_pages_render(): void
    {
        foreach (['/more', '/settings/class', '/settings/curriculum', '/settings/fee-plans', '/settings/account', '/settings/backups', '/reports'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_class_details_are_saved_and_used(): void
    {
        Livewire::test(School::class)
            ->set('school_name', 'Ritu’s Writing Studio')
            ->set('school_phone', '98765 00000')
            ->set('fee_due_day', 5)
            ->set('receipt_prefix', 'RWS')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Ritu’s Writing Studio', Setting::get('school_name'));
        $this->assertEquals(5, Setting::get('fee_due_day'));
        $this->get('/')->assertSee('Ritu’s Writing Studio');
    }

    public function test_fee_due_day_must_be_in_range(): void
    {
        Livewire::test(School::class)->set('fee_due_day', 31)->call('save')->assertHasErrors('fee_due_day');
    }

    public function test_fee_plans_can_be_added_and_edited(): void
    {
        Livewire::test(FeePlans::class)
            ->call('edit')
            ->set('name', 'Cursive pack')
            ->set('type', 'pack')
            ->set('amount', '2400')
            ->set('classes_count', '12')
            ->call('save')
            ->assertHasNoErrors();

        $plan = FeePlan::sole();
        $this->assertSame(12, (int) $plan->classes_count);

        Livewire::test(FeePlans::class)
            ->call('edit', $plan->id)
            ->set('type', 'monthly')
            ->set('active', false)
            ->call('save');

        $this->assertNull($plan->fresh()->classes_count);
        $this->assertFalse($plan->fresh()->active);
    }

    public function test_curriculum_can_be_edited_and_reordered(): void
    {
        $this->seed(CurriculumSeeder::class);
        $course = Course::firstWhere('name', 'Phonics');
        [$first, $second] = $course->levels->take(2)->all();
        $skillCount = $first->skills()->count();
        $removedSkillId = $first->skills()->first()->id;

        Livewire::test(Curriculum::class, ['courseId' => $course->id])
            ->call('rename', 'level', $first->id, 'Sounds')
            ->call('addSkill', $first->id, 'qu ou oi')
            ->call('move', 'level', $second->id, -1)
            ->call('addLevel', 'Fluency')
            ->call('remove', 'skill', $removedSkillId);

        $levels = $course->fresh()->levels;
        $this->assertSame($second->id, $levels->first()->id);
        $this->assertSame('Sounds', $levels->firstWhere('id', $first->id)->name);
        $this->assertSame('Fluency', $levels->last()->name);
        $this->assertTrue($first->skills()->where('name', 'qu ou oi')->exists());
        $this->assertSame($skillCount, $first->skills()->count());
        $this->assertFalse($first->skills()->whereKey($removedSkillId)->exists());
    }

    public function test_cannot_edit_levels_of_another_course(): void
    {
        $this->seed(CurriculumSeeder::class);
        $print = Course::firstWhere('name', 'Handwriting (Print)');
        $phonicsLevel = Course::firstWhere('name', 'Phonics')->levels->first();

        Livewire::test(Curriculum::class, ['courseId' => $print->id])
            ->call('remove', 'level', $phonicsLevel->id)
            ->assertNotFound();

        $this->assertNotNull($phonicsLevel->fresh());
    }

    public function test_password_change_needs_current_password(): void
    {
        Livewire::test(Account::class)
            ->set('current_password', 'wrong')
            ->set('password', 'new-password-1')
            ->set('password_confirmation', 'new-password-1')
            ->call('savePassword')
            ->assertHasErrors('current_password');

        Livewire::test(Account::class)
            ->set('current_password', 'old-password')
            ->set('password', 'new-password-1')
            ->set('password_confirmation', 'new-password-1')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password-1', $this->user->fresh()->password));
    }
}
