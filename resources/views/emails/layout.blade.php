<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Promoseven Holdings BSC')</title>
</head>
<body style="margin:0; padding:0; background:#F1F5F9; font-family:Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9; padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:100%; background:#FFFFFF; border:1.5px solid #1E3A8A;">
                @include('emails.partials.header')
                <tr>
                    <td style="padding:28px 32px;">
                        @yield('content')
                    </td>
                </tr>
                @include('emails.partials.footer')
            </table>
        </td>
    </tr>
</table>
</body>
</html>
