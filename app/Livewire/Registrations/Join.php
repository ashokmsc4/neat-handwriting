<?php

namespace App\Livewire\Registrations;

use App\Models\Course;
use App\Models\Registration;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public sign-up form parents open from the shared link. No login; the link's
 * secret token, a rate limit and a hidden honeypot field keep out bots.
 */
#[Layout('layouts::guest')]
#[Title('Registration')]
class Join extends Component
{
    #[Locked]
    public string $token = '';

    public bool $sent = false;

    public string $child_name = '';

    public ?string $dob = null;

    public string $school = '';

    public string $grade = '';

    /** @var array<int, string> */
    public array $course_ids = [];

    public string $preferred_time = '';

    public string $notes = '';

    public string $guardian_name = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $address = '';

    public bool $photo_consent = false;

    /** Hidden from people; bots that fill every field get silently dropped. */
    public string $website = '';

    public function mount(string $token): void
    {
        abort_unless(hash_equals(Registration::token(), $token), 404);

        $this->token = $token;
    }

    protected function rules(): array
    {
        return [
            'child_name' => 'required|string|max:255',
            'dob' => 'required|date|before:today|after:-25 years',
            'school' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:30',
            'course_ids' => 'array',
            'course_ids.*' => 'integer|exists:courses,id',
            'preferred_time' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'guardian_name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'photo_consent' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'dob.required' => "Please add your child's date of birth.",
            'phone.regex' => 'Please enter a valid phone number.',
            'whatsapp.regex' => 'Please enter a valid WhatsApp number.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['child_name' => "child's name", 'guardian_name' => 'your name', 'dob' => 'date of birth'];
    }

    public function submit(): void
    {
        abort_unless(Registration::isOpen() && hash_equals(Registration::token(), $this->token), 404);

        $data = $this->validate();

        $key = 'register:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('child_name', 'Too many sign-ups from this connection. Please try again later.');

            return;
        }
        RateLimiter::hit($key, 3600);

        if ($this->website === '') {
            Registration::create([
                ...$data,
                'course_ids' => array_map('intval', $data['course_ids'] ?? []),
                'whatsapp' => $data['whatsapp'] ?: null,
                'email' => $data['email'] ?: null,
                'school' => $data['school'] ?: null,
                'grade' => $data['grade'] ?: null,
                'preferred_time' => $data['preferred_time'] ?: null,
                'notes' => $data['notes'] ?: null,
                'address' => $data['address'] ?: null,
            ]);
        }

        $this->sent = true;
    }

    public function another(): void
    {
        // Same parent registering a sibling: keep the parent's details.
        $this->reset(['child_name', 'dob', 'school', 'grade', 'course_ids', 'preferred_time', 'notes', 'sent']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.registrations.join', [
            'open' => Registration::isOpen(),
            'courses' => Course::where('active', true)->orderBy('name')->get(),
        ]);
    }
}
