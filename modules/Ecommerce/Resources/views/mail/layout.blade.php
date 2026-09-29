<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>@yield('title')</title>
    <style>
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .card-pad { padding: 24px 20px !important; }
            .stack { display: block !important; width: 100% !important; padding: 0 0 16px 0 !important; }
            .headline { font-size: 22px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#F3F4F6; -webkit-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">@yield('preheader')</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F3F4F6;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">
                    <tr>
                        <td align="center" style="padding:0 0 20px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
                            <a href="{{ $shop['url'] }}" style="text-decoration:none; color:#2C4B60; font-size:20px; font-weight:700; letter-spacing:-0.2px;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:10px; background-color:#F27D42; margin-right:8px; vertical-align:middle;"></span>{{ $shop['name'] }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="card-pad" style="background-color:#FFFFFF; border-radius:20px; padding:36px 40px; border:1px solid #E5E7EB; font-family:'Segoe UI',Helvetica,Arial,sans-serif; color:#1F2937;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 16px 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:12px; line-height:18px; color:#9CA3AF;">
                            @yield('footer')
                            <p style="margin:8px 0 0 0;">
                                <a href="{{ $shop['url'] }}" style="color:#6B7280; text-decoration:underline;">{{ $shop['name'] }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
