<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #1E3A8A; margin:18px 0;">
    <tr>
        <td style="background:#D9D2B0; padding:8px 14px; font-size:11px; font-weight:700; color:#1E3A8A; text-transform:uppercase; letter-spacing:0.04em; font-family:Arial, Helvetica, sans-serif;">{{ $boxTitle }}</td>
    </tr>
    @foreach($rows as $row)
    <tr>
        <td style="padding:9px 14px; border-top:1px solid #E2E8F0; font-family:Arial, Helvetica, sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="font-size:12px; color:#64748B;">{{ $row['label'] }}</td>
                    <td style="font-size:12.5px; color:#111827; font-weight:700; text-align:right;">{{ $row['value'] }}</td>
                </tr>
            </table>
        </td>
    </tr>
    @endforeach
</table>
