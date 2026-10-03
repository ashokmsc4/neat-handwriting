<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['student_id', 'enrollment_id', 'period_label', 'period', 'due_date', 'amount', 'discount', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'status' => InvoiceStatus::class,
        ];
    }

    public function scopeOutstanding(Builder $query): void
    {
        $query->whereIn('status', [InvoiceStatus::Due, InvoiceStatus::Partial]);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Amount still owed; uses loaded payments when available to avoid extra queries. */
    public function balance(): float
    {
        if ($this->status === InvoiceStatus::Waived) {
            return 0;
        }

        $paid = $this->relationLoaded('payments') ? $this->payments->sum('amount') : $this->payments()->sum('amount');

        return max(0, (float) $this->amount - (float) $this->discount - (float) $paid);
    }
}
