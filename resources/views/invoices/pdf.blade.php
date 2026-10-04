@extends('layouts.pdf')

{{-- Tax invoice. Not a report: it carries an addressee, a VAT breakdown and a
     total in words, so its own anatomy lives under "TRANSACTIONAL DOCUMENTS" in
     public/css/pdf.css. The page box, letterhead, footer and type come from the
     layout like every other PDF. --}}

@section('pdf-title', 'Invoice')
@section('report-title', 'Tax Invoice')
@section('report-sub')
Invoice {{ $invoice->invoice_number }}
@endsection
@section('letterhead-extra')
{{-- Glued: this lands at the end of the letterhead's contact line, and an
     unglued "#" or number can be stranded on its own by a wrap. --}}
TRN&nbsp;#&nbsp;200010076400002
@endsection

@section('content')

<div>

    {{-- LETTERHEAD --}}
    

    

    {{-- INVOICE NUMBER / DATE --}}
    <div class="inv-meta-row">
        <div class="inv-meta-left">Invoice No.: {{ $invoice->invoice_number }}</div>
        <div class="inv-meta-right">Invoice Date: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
    </div>

    {{-- TENANT INFO --}}
    <div class="inv-tenant-title">Tenant Information</div>
    <div class="inv-tenant-line"><b>Name:</b> &nbsp;{{ $invoice->tenant_name }}</div>
    <div class="inv-tenant-line"><b>Code:</b> &nbsp;{{ $invoice->tenant_code ?: '—' }}</div>
    <div class="inv-tenant-line"><b>Address:</b> &nbsp;{{ $invoice->display_address ?: '—' }}</div>

    {{-- RENTAL DETAILS --}}
    <div class="inv-rental-wrap">
        <div class="inv-rental-title">Rental Details</div>
        <table class="inv-rental">
            <thead>
                <tr>
                    <th style="width:6%">S.No.</th>
                    <th style="width:26%">Property</th>
                    <th style="width:14%">Unit No.</th>
                    <th style="width:14%">Lease No.</th>
                    <th style="width:24%">Rental Period</th>
                    <th class="right" style="width:16%">Rent (BD)</th>
                </tr>
            </thead>
            <tbody>
                @php $lines = $invoice->lines ?? []; @endphp
                @foreach($lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $line['property_name'] ?? '' }}</b></td>
                    <td>{{ $line['unit'] ?? '' }}</td>
                    <td>{{ $line['lease_agreement_no'] ?? '' }}</td>
                    <td>
                        @if(!empty($line['rental_period_start']))
                            From {{ \Illuminate\Support\Carbon::parse($line['rental_period_start'])->format('d-M-y') }} to {{ !empty($line['rental_period_end']) ? \Illuminate\Support\Carbon::parse($line['rental_period_end'])->format('d-m-Y') : '' }}
                        @endif
                    </td>
                    <td class="right">{{ number_format($line['amount'] ?? 0, 3) }}</td>
                </tr>
                @endforeach
                {{-- pad with empty rows so the table reads like the paper template --}}
                @for($i = count($lines); $i < max(10, count($lines) + 1); $i++)
                <tr class="inv-empty-row"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
                @endfor
                <tr class="inv-total-row">
                    <td></td><td></td><td></td><td></td>
                    <td class="right">Total</td>
                    <td class="right">{{ number_format($invoice->amount, 3) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- TOTALS --}}
    <div class="inv-totals-box">
        <table>
            <tr><td>Total Excl. VAT (BD)</td><td class="right">{{ number_format($invoice->amount, 3) }}</td></tr>
            <tr><td>VAT ({{ number_format($invoice->vat_rate, 0) }}%)</td><td class="right">{{ $invoice->vat_amount > 0 ? number_format($invoice->vat_amount, 3) : '-' }}</td></tr>
            <tr><td>Total incl. VAT (BD)</td><td class="right">{{ number_format($invoice->total_incl_vat, 3) }}</td></tr>
        </table>
    </div>

    <div class="inv-spacer"></div>

    {{-- AMOUNT IN WORDS --}}
    <div class="inv-words-label">Amount In Words</div>
    <div class="inv-words-value">{{ $invoice->amount_in_words }}</div>

    @if($invoice->remarks)
    {{-- REMARKS --}}
    <div class="inv-remarks-label">Remarks</div>
    <div class="inv-remarks-value">{{ $invoice->remarks }}</div>
    @endif

    {{-- BANK DETAILS --}}
    <div class="inv-bank-label">Please remit your payment to our Bankers</div>
    <div class="inv-bank-line">Al Salam Bank Bahrain</div>
    <div class="inv-bank-line">In the name of: Promoseven Holdings BSC &copy;</div>
    <div class="inv-bank-line">IBAN: BH26ALSA00280465160030</div>
    <div class="inv-bank-line">Account No. 280465160030</div>
    <div class="inv-bank-line">Swift Code: ALSABHBM</div>

    {{-- SIGNATURE --}}
    <div class="inv-sign-block">
        For and on Behalf of<br>
        Promoseven Holdings BSC &copy;<br>
        Real Estate Division
    </div>

    

</div>

@endsection
