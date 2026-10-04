{{-- Staff digest of leases running out. Internal, so it leads with the count
     and the deadline rather than a greeting, and every row carries the one
     thing the reader needs to act: which lease, whose, and when it ends. --}}
@extends('emails.layout', [
    'ribbonBg' => '#D9D2B0',
    'ribbonFg' => '#1E3A8A',
    'ribbonLabel' => 'Leases Ending Soon',
    'ribbonSummary' => 'On or before ' . $until->format('d M Y'),
    'ribbonAmount' => (string) $leases->count(),
    'ribbonAmountLabel' => $leases->count() === 1 ? 'Agreement' : 'Agreements',
])

@section('title', 'Leases ending in the next ' . $days . ' days')

@section('content')
<p style="font-size:13px; color:#111827; line-height:1.6; margin:0 0 16px; font-family:Arial, Helvetica, sans-serif;">
    {{ $leases->count() }} lease{{ $leases->count() === 1 ? '' : 's' }}
    {{ $leases->count() === 1 ? 'ends' : 'end' }} within the next {{ $days }} days.
    Each one needs a renewal or a decision to let it lapse.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:0 0 18px;">
    <tr>
        <th align="left" style="padding:8px 10px; background:#F1F5F9; border-bottom:1.5px solid #1E3A8A; font-size:10px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#1E3A8A; font-family:Arial, Helvetica, sans-serif;">Agreement</th>
        <th align="left" style="padding:8px 10px; background:#F1F5F9; border-bottom:1.5px solid #1E3A8A; font-size:10px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#1E3A8A; font-family:Arial, Helvetica, sans-serif;">Tenant</th>
        <th align="left" style="padding:8px 10px; background:#F1F5F9; border-bottom:1.5px solid #1E3A8A; font-size:10px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#1E3A8A; font-family:Arial, Helvetica, sans-serif;">Unit</th>
        <th align="right" style="padding:8px 10px; background:#F1F5F9; border-bottom:1.5px solid #1E3A8A; font-size:10px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#1E3A8A; font-family:Arial, Helvetica, sans-serif;">Ends</th>
    </tr>
    @foreach($leases as $lease)
    <tr>
        <td style="padding:9px 10px; border-bottom:1px solid #E2E8F0; font-size:12px; font-weight:700; color:#111827; font-family:Arial, Helvetica, sans-serif;">{{ $lease->lease_agreement_no ?: '#' . $lease->id }}</td>
        <td style="padding:9px 10px; border-bottom:1px solid #E2E8F0; font-size:12px; color:#111827; font-family:Arial, Helvetica, sans-serif;">{{ $lease->tenant_name }}</td>
        <td style="padding:9px 10px; border-bottom:1px solid #E2E8F0; font-size:12px; color:#475569; font-family:Arial, Helvetica, sans-serif;">{{ $lease->unit ?: '—' }}</td>
        <td align="right" style="padding:9px 10px; border-bottom:1px solid #E2E8F0; font-size:12px; font-weight:700; color:#111827; white-space:nowrap; font-family:Arial, Helvetica, sans-serif;">{{ $lease->lease_end_date->format('d M Y') }}</td>
    </tr>
    @endforeach
</table>

<p style="font-size:12px; color:#475569; line-height:1.6; margin:0; font-family:Arial, Helvetica, sans-serif;">
    Open <a href="{{ route('lease-contracts.index', ['status' => 'expiring']) }}" style="color:#1E3A8A;">Leases &rsaquo; Expiring</a>
    to renew or close any of these.
</p>
@endsection
