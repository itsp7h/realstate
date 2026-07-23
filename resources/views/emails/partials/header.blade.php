<!-- LETTERHEAD -->
<tr>
    <td style="padding:28px 32px 20px; text-align:center; border-bottom:1.5px solid #1E3A8A;">
        <div style="font-size:16px; font-weight:700; color:#1E3A8A; font-family:Arial, Helvetica, sans-serif;">Promoseven Holdings BSC &copy;</div>
        <div style="font-size:12.5px; font-weight:700; color:#1E3A8A; margin-top:2px; font-family:Arial, Helvetica, sans-serif;">Real Estate Division</div>
        <div style="font-size:10.5px; color:#64748B; margin-top:8px; font-family:Arial, Helvetica, sans-serif;">Office 27, Building 1130M Road 1531, Muharraq, Kingdom of Bahrain</div>
        <div style="font-size:10.5px; color:#64748B; margin-top:2px; font-family:Arial, Helvetica, sans-serif;">CR # 21534-1 &nbsp;&middot;&nbsp; Tel +973 17500787 &nbsp;&middot;&nbsp; TRN # 200010076400002</div>
    </td>
</tr>

<!-- STATUS RIBBON -->
<tr>
    <td style="background:{{ $ribbonBg }}; padding:16px 32px; border-bottom:1.5px solid #1E3A8A;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:middle;">
                    <div style="font-size:10.5px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:{{ $ribbonFg }}; font-family:Arial, Helvetica, sans-serif;">{{ $ribbonLabel }}</div>
                    <div style="font-size:12.5px; color:#1E3A8A; margin-top:4px; font-family:Arial, Helvetica, sans-serif;">{{ $ribbonSummary }}</div>
                </td>
                @if(isset($ribbonAmount))
                <td style="vertical-align:middle; text-align:right;">
                    <div style="font-size:10px; color:{{ $ribbonFg }}; font-family:Arial, Helvetica, sans-serif;">{{ $ribbonAmountLabel ?? 'Amount' }}</div>
                    <div style="font-size:20px; font-weight:700; color:#1E3A8A; font-family:Arial, Helvetica, sans-serif;">{{ $ribbonAmount }}</div>
                </td>
                @endif
            </tr>
        </table>
    </td>
</tr>
