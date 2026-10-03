<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;

class ReceiptController extends Controller
{
    public function __invoke(Payment $payment)
    {
        $payment->load('invoice.student.guardian', 'invoice.payments');

        return view('receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'student' => $payment->invoice->student,
            'school' => [
                'name' => Setting::get('school_name'),
                'phone' => Setting::get('school_phone'),
                'address' => Setting::get('school_address'),
            ],
            'currency' => config('school.currency'),
        ]);
    }
}
