@extends('emails.layout', [
    'ribbonBg' => '#D9D2B0',
    'ribbonFg' => '#1E3A8A',
    'ribbonLabel' => 'Invoice Issued',
    'ribbonSummary' => $invoice->invoice_number . ' · Due on receipt',
    'ribbonAmount' => 'BHD ' . number_format($invoice->total_incl_vat, 3),
    'ribbonAmountLabel' => 'Amount due',
])

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<p style="font-size:13px; color:#111827; margin:0 0 14px; font-family:Arial, Helvetica, sans-serif;">Dear {{ $invoice->tenant_name }},</p>
<p style="font-size:13px; color:#111827; line-height:1.6; margin:0 0 4px; font-family:Arial, Helvetica, sans-serif;">
    A new invoice has been issued to your account. The full invoice is attached to this email as a PDF for your records.
</p>

@include('emails.partials.details-box', [
    'boxTitle' => 'Invoice Summary',
    'rows' => [
        ['label' => 'Invoice number', 'value' => $invoice->invoice_number],
        ['label' => 'Property', 'value' => $invoice->property_name . ($invoice->unit ? ' · Unit ' . $invoice->unit : '')],
        ['label' => 'Invoice date', 'value' => $invoice->invoice_date->format('d M Y')],
        ['label' => 'Amount due', 'value' => 'BHD ' . number_format($invoice->total_incl_vat, 3)],
    ],
])

<p style="font-size:12px; color:#64748B; line-height:1.6; margin:14px 0 0; font-family:Arial, Helvetica, sans-serif;">
    Please arrange settlement at your earliest convenience. If you've already paid, kindly disregard this notice.
</p>
@endsection
