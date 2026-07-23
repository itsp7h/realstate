@extends('emails.layout', [
    'ribbonBg' => '#FEE2E2',
    'ribbonFg' => '#991B1B',
    'ribbonLabel' => 'Payment Overdue',
    'ribbonSummary' => $invoice->invoice_number . ' · ' . $daysOverdue . ' day' . ($daysOverdue === 1 ? '' : 's') . ' overdue',
    'ribbonAmount' => 'BHD ' . number_format($invoice->balance_due, 3),
    'ribbonAmountLabel' => 'Balance due',
])

@section('title', 'Overdue: Invoice ' . $invoice->invoice_number)

@section('content')
<p style="font-size:13px; color:#111827; margin:0 0 14px; font-family:Arial, Helvetica, sans-serif;">Dear {{ $invoice->tenant_name }},</p>
<p style="font-size:13px; color:#111827; line-height:1.6; margin:0 0 4px; font-family:Arial, Helvetica, sans-serif;">
    Our records show the invoice below remains unpaid past its due date. Please arrange settlement as soon as possible to avoid further action.
</p>

@include('emails.partials.details-box', [
    'boxTitle' => 'Overdue Invoice',
    'rows' => [
        ['label' => 'Invoice number', 'value' => $invoice->invoice_number],
        ['label' => 'Property', 'value' => $invoice->property_name . ($invoice->unit ? ' · Unit ' . $invoice->unit : '')],
        ['label' => 'Invoice date', 'value' => $invoice->invoice_date->format('d M Y')],
        ['label' => 'Days overdue', 'value' => (string) $daysOverdue],
        ['label' => 'Balance due', 'value' => 'BHD ' . number_format($invoice->balance_due, 3)],
    ],
])

<p style="font-size:12px; color:#64748B; line-height:1.6; margin:14px 0 0; font-family:Arial, Helvetica, sans-serif;">
    If payment has already been made, please share proof of payment so we can update our records. Otherwise, kindly settle the balance above at your earliest convenience.
</p>
@endsection
