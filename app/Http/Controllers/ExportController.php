<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV downloads for Excel / Google Sheets. */
class ExportController extends Controller
{
    public function __invoke(Request $request, string $type): StreamedResponse
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()))->endOfDay();

        [$headers, $rows] = match ($type) {
            'students' => $this->students(),
            'attendance' => $this->attendance($from, $to),
            'payments' => $this->payments($from, $to),
            'outstanding' => $this->outstanding(),
            default => abort(404),
        };

        $filename = "{$type}-".($type === 'attendance' || $type === 'payments' ? $from->format('Y-m-d').'-to-'.$to->format('Y-m-d') : now()->format('Y-m-d')).'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads ₹ and names correctly
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function students(): array
    {
        $rows = Student::with(['guardian', 'batches'])->orderBy('name')->get()->map(fn ($s) => [
            $s->name, $s->status->label(), $s->grade, $s->school, $s->dob?->toDateString(), $s->joined_on?->toDateString(),
            $s->guardian?->name, $s->guardian?->phone, $s->guardian?->whatsapp, $s->guardian?->email,
            $s->batches->pluck('name')->join(', '), $s->notes,
        ]);

        return [['Student', 'Status', 'Grade', 'School', 'Date of birth', 'Joined', 'Parent', 'Phone', 'WhatsApp', 'Email', 'Batches', 'Notes'], $rows];
    }

    private function attendance(Carbon $from, Carbon $to): array
    {
        $rows = Attendance::with(['student', 'classSession.batch'])
            ->whereHas('classSession', fn ($q) => $q->whereBetween('date', [$from, $to]))
            ->get()
            ->sortBy(fn ($a) => [$a->classSession->date->timestamp, $a->classSession->batch->name, $a->student->name])
            ->map(fn ($a) => [$a->classSession->date->toDateString(), $a->classSession->batch->name, $a->student->name, $a->status->label(), $a->classSession->topic_note]);

        return [['Date', 'Batch', 'Student', 'Status', 'Class note'], $rows];
    }

    private function payments(Carbon $from, Carbon $to): array
    {
        $rows = Payment::with('invoice.student')->whereBetween('paid_on', [$from, $to])->orderBy('paid_on')->get()->map(fn ($p) => [
            $p->paid_on->toDateString(), $p->receipt_no, $p->invoice->student->name, $p->invoice->period_label,
            $p->method->label(), $p->reference, number_format((float) $p->amount, 2, '.', ''),
        ]);

        return [['Date', 'Receipt', 'Student', 'For', 'Method', 'Reference', 'Amount'], $rows];
    }

    private function outstanding(): array
    {
        $rows = Invoice::outstanding()->with(['student.guardian', 'payments'])->orderBy('due_date')->get()->map(fn ($i) => [
            $i->student->name, $i->student->guardian?->phone, $i->period_label, $i->due_date->toDateString(),
            number_format((float) $i->amount - (float) $i->discount, 2, '.', ''), number_format($i->balance(), 2, '.', ''),
        ]);

        return [['Student', 'Parent phone', 'For', 'Due date', 'Amount', 'Balance due'], $rows];
    }
}
