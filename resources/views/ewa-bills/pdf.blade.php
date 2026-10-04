@extends('layouts.pdf')

{{-- EWA (electricity & water) bill. Its meter-reading and cap anatomy lives
     under "TRANSACTIONAL DOCUMENTS" in public/css/pdf.css; everything else
     comes from the shared layout. --}}

@section('pdf-title', 'EWA bill')
@section('report-title', 'EWA Bill')
@section('letterhead-extra')
{{-- Glued: this lands at the end of the letterhead's contact line, and an
     unglued "#" or number can be stranded on its own by a wrap. --}}
TRN&nbsp;#&nbsp;200010076400002
@endsection
@section('report-sub')
{{ $bill->property_name ?? '' }}
@endsection

@section('content')

<div>

    {{-- LETTERHEAD --}}
    

    

    {{-- BILL NUMBER / DATE --}}
    <div class="ewa-meta-row">
        <div class="ewa-meta-left">Invoice No.: {{ $bill->bill_number }}</div>
        <div class="ewa-meta-right">Invoice Date: {{ ($bill->reading_date ?? $bill->created_at)->format('d/m/Y') }}</div>
    </div>

    {{-- TENANT INFO --}}
    @php
        $tenant = $bill->leaseContract?->tenant;
        $tenantAddress = $tenant?->address;
        if (! $tenantAddress && $bill->leaseContract?->property_code) {
            $tenantAddress = \App\Models\Building::where('property_code', $bill->leaseContract->property_code)->first()?->full_address;
        }
    @endphp
    <div class="ewa-tenant-title">Tenant Information</div>
    <div class="ewa-tenant-line"><b>Name:</b> &nbsp;{{ $bill->tenant_name }}</div>
    <div class="ewa-tenant-line"><b>Code:</b> &nbsp;{{ $tenant?->tenant_code ?: '—' }}</div>
    <div class="ewa-tenant-line"><b>Address:</b> &nbsp;{{ $tenantAddress ?: '—' }}</div>

    {{-- SUPPLY / ACCOUNT DETAILS --}}
    <div class="ewa-supply-wrap">
        <div class="ewa-supply-cell">
            <div class="ewa-supply-lbl">Property / Unit</div>
            <div class="ewa-supply-val">{{ $bill->property_name ?: '—' }}{{ $bill->unit ? ' / '.$bill->unit : '' }}</div>
        </div>
        <div class="ewa-supply-cell">
            <div class="ewa-supply-lbl">EWA Account No.</div>
            <div class="ewa-supply-val">{{ $bill->ewa_account_number ?: '—' }}</div>
        </div>
        <div class="ewa-supply-cell">
            <div class="ewa-supply-lbl">Billing Period</div>
            <div class="ewa-supply-val">{{ $bill->billing_period ?: '—' }}</div>
        </div>
        <div class="ewa-supply-cell" style="padding-right:0">
            <div class="ewa-supply-lbl">Reading Type</div>
            <div class="ewa-supply-val">{{ $bill->reading_type_label }}</div>
        </div>
    </div>

    {{-- CHARGES DETAILS --}}
    <div class="ewa-rental-wrap">
        <div class="ewa-rental-title">Charges Details</div>
        <table class="ewa-rental">
            <thead>
                <tr>
                    <th style="width:6%">S.No.</th>
                    <th style="width:44%">Description</th>
                    <th style="width:24%">Period</th>
                    <th class="right" style="width:26%">Amount (BD)</th>
                </tr>
            </thead>
            <tbody>
                @php $sno = 1; @endphp
                @if($bill->elec_charges)
                <tr>
                    <td>{{ $sno++ }}</td>
                    <td><b>Electricity Charges</b>{{ $bill->elec_consumption !== null ? ' ('.number_format($bill->elec_consumption, 0).' kWh)' : '' }}</td>
                    <td>{{ $bill->billing_period ?: '—' }}</td>
                    <td class="right">{{ number_format($bill->elec_charges, 3) }}</td>
                </tr>
                @endif
                @if($bill->water_charges)
                <tr>
                    <td>{{ $sno++ }}</td>
                    <td><b>Water Charges</b>{{ $bill->water_consumption !== null ? ' ('.number_format($bill->water_consumption, 3).' m&sup3;)' : '' }}</td>
                    <td>{{ $bill->billing_period ?: '—' }}</td>
                    <td class="right">{{ number_format($bill->water_charges, 3) }}</td>
                </tr>
                @endif
                @if($bill->hasCap())
                <tr class="ewa-deduction-row">
                    <td>{{ $sno++ }}</td>
                    <td>Less: Landlord-Covered Portion (within cap of {{ number_format($bill->ewa_cap, 3) }})</td>
                    <td>{{ $bill->billing_period ?: '—' }}</td>
                    <td class="right">&minus;{{ number_format($bill->landlord_portion, 3) }}</td>
                </tr>
                @endif
                @for($i = $sno; $i <= 5; $i++)
                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                @endfor
                <tr class="ewa-total-row">
                    <td></td><td></td>
                    <td class="right">Total</td>
                    <td class="right">{{ number_format($bill->effective_tenant_portion, 3) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- TOTALS --}}
    <div class="ewa-totals-box">
        <table>
            <tr><td>Total Excl. VAT (BD)</td><td class="right">{{ number_format($bill->effective_tenant_portion, 3) }}</td></tr>
            <tr><td>VAT (0%)</td><td class="right">-</td></tr>
            <tr><td>Total incl. VAT (BD)</td><td class="right">{{ number_format($bill->effective_tenant_portion, 3) }}</td></tr>
        </table>
    </div>

    <div class="ewa-spacer"></div>

    {{-- AMOUNT IN WORDS --}}
    <div class="ewa-words-label">Amount In Words</div>
    <div class="ewa-words-value">{{ $bill->amount_in_words }}</div>

    @if($bill->remarks)
    {{-- REMARKS --}}
    <div class="ewa-remarks-label">Remarks</div>
    <div class="ewa-remarks-value">{{ $bill->remarks }}</div>
    @endif

    {{-- BANK DETAILS --}}
    <div class="ewa-bank-label">Please remit your payment to our Bankers</div>
    <div class="ewa-bank-line">Al Salam Bank Bahrain</div>
    <div class="ewa-bank-line">In the name of: Promoseven Holdings BSC &copy;</div>
    <div class="ewa-bank-line">IBAN: BH26ALSA00280465160030</div>
    <div class="ewa-bank-line">Account No. 280465160030</div>
    <div class="ewa-bank-line">Swift Code: ALSABHBM</div>

    {{-- SIGNATURE --}}
    <div class="ewa-sign-block">
        For and on Behalf of<br>
        Promoseven Holdings BSC &copy;<br>
        Real Estate Division
    </div>

    

</div>

@endsection
