@extends('layouts.pdf')

{{-- Payment receipt — a voucher, not a report: particulars column, divider
     rule, amount in words, signature lines. That anatomy lives under
     "TRANSACTIONAL DOCUMENTS" in public/css/pdf.css.

     The TRN is passed through letterhead-extra rather than dropped: a tax
     registration number on a receipt is content, and unifying a letterhead is
     not a reason to remove it. --}}

@section('pdf-title', 'Receipt')
@section('report-title', 'Receipt')
@section('report-sub')
Receipt for invoice {{ $invoice->invoice_number }}
@endsection
@section('letterhead-extra')
{{-- Glued: this lands at the end of the letterhead's contact line, and an
     unglued "#" or number can be stranded on its own by a wrap. --}}
TRN&nbsp;#&nbsp;200010076400002
@endsection

@section('content')

<div class="rv-dated-row">
    <div class="rv-fill"></div>
    <div class="rv-lbl">Dated</div>
    <div class="rv-val">: {{ $payment->payment_date->format('d-M-Y') }}</div>
</div>

<table class="rv-rv">
    <thead>
        <tr>
            <th class="rv-particulars-col">Particulars</th>
            <th class="rv-divider"></th>
            <th class="rv-amt-head">Amount (BHD)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="rv-p-account">Account :</td>
            <td class="rv-divider"></td>
            <td class="rv-amt"></td>
        </tr>
        <tr>
            <td class="rv-p-tenant">{{ $invoice->tenant_name }}</td>
            <td class="rv-divider"></td>
            <td class="rv-amt">{{ number_format($payment->amount, 3) }}</td>
        </tr>
        <tr>
            <td class="rv-p-category">Primary Cost Category</td>
            <td class="rv-divider"></td>
            <td class="rv-amt"></td>
        </tr>
        <tr>
            <td class="rv-p-unit-line">
                {{ $invoice->unit ?: $invoice->property_name }}
                <span class="rv-amt-inline">{{ number_format($payment->amount, 3) }}</span>
                <span class="rv-cr">Cr</span>
            </td>
            <td class="rv-divider"></td>
            <td class="rv-amt"></td>
        </tr>
        <tr class="rv-spacer"><td></td><td class="rv-divider"></td><td></td></tr>
        <tr class="rv-meta-row">
            <td>
                <div class="rv-meta-lbl">Through :</div>
                <div class="rv-meta-val">{{ $payment->reference ?: $payment->method_label }}</div>
            </td>
            <td class="rv-divider"></td>
            <td class="rv-amt"></td>
        </tr>
        <tr>
            <td>
                <div class="rv-meta-lbl">On Account of :</div>
                <div class="rv-meta-val">{{ $payment->notes ?: 'Being the payment against ' . $invoice->type_label . ' (Inv ' . $invoice->invoice_number . ')' }}</div>
            </td>
            <td class="rv-divider"></td>
            <td class="rv-amt"></td>
        </tr>
        @if($payment->ewaBill)
        <tr>
            <td>
                <div class="rv-meta-lbl">EWA :</div>
                <div class="rv-meta-val">
                    {{ $payment->ewaBill->bill_number }} — {{ $payment->ewaBill->billing_period ?: $payment->ewaBill->reading_date?->format('M Y') }}
                    @if($payment->ewaBill->ewa_cap !== null && (float) $payment->ewaBill->ewa_cap > 0)
                        (Cap: {{ number_format($payment->ewaBill->ewa_cap, 3) }} BHD)
                    @endif
                </div>
            </td>
            <td class="rv-divider"></td>
            <td class="rv-amt"></td>
        </tr>
        @endif
        <tr>
            <td>
                <div class="rv-meta-lbl">Amount (in words) :</div>
                <div class="rv-meta-val">{{ \App\Support\NumberToWords::bahrainiDinars($payment->amount) }}</div>
            </td>
            <td class="rv-divider"></td>
            <td class="rv-amt"></td>
        </tr>
        <tr class="rv-total-row">
            <td></td>
            <td class="rv-divider"></td>
            <td class="rv-amt">{{ number_format($payment->amount, 3) }}</td>
        </tr>
    </tbody>
</table>

<div class="rv-sign-block">
    <div class="rv-sign-cell"></div>
    <div class="rv-sign-cell right">
        <div class="rv-sign-line">Authorised Signatory</div>
    </div>
</div>

<div class="rv-prepared-by">Prepared by</div>

@endsection
