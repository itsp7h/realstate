@extends('emails.layout', [
    'ribbonBg' => '#DCFCE7',
    'ribbonFg' => '#15803D',
    'ribbonLabel' => 'Payment Received',
    'ribbonSummary' => $payment->payment_number . ' · ' . $invoice->invoice_number,
    'ribbonAmount' => 'BHD ' . number_format($payment->amount, 3),
    'ribbonAmountLabel' => 'Amount paid',
])

@section('title', 'Receipt ' . $payment->payment_number)

@section('content')
<p style="font-size:13px; color:#111827; margin:0 0 14px; font-family:Arial, Helvetica, sans-serif;">Dear {{ $invoice->tenant_name }},</p>
<p style="font-size:13px; color:#111827; line-height:1.6; margin:0 0 4px; font-family:Arial, Helvetica, sans-serif;">
    Thank you &mdash; we've received your payment. The official receipt is attached to this email as a PDF for your records.
</p>

@include('emails.partials.details-box', [
    'boxTitle' => 'Payment Summary',
    'rows' => [
        ['label' => 'Receipt number', 'value' => $payment->payment_number],
        ['label' => 'Against invoice', 'value' => $invoice->invoice_number],
        ['label' => 'Payment date', 'value' => $payment->payment_date->format('d M Y')],
        ['label' => 'Method', 'value' => $payment->method_label],
        ['label' => 'Amount paid', 'value' => 'BHD ' . number_format($payment->amount, 3)],
        ['label' => 'Remaining balance', 'value' => 'BHD ' . number_format($invoice->balance_due, 3)],
    ],
])

<p style="font-size:12px; color:#64748B; line-height:1.6; margin:14px 0 0; font-family:Arial, Helvetica, sans-serif;">
    @if($invoice->balance_due > 0)
        There is a remaining balance on this invoice. A further reminder will follow if it becomes overdue.
    @else
        This invoice is now fully settled. No further action is needed.
    @endif
</p>
@endsection
