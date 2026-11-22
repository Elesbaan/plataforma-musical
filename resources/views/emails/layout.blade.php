<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $settings->brand_name }}</title>
</head>
<body style="margin: 0; padding: 0; background: #f4f4f5; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f4f4f5;">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background: #ffffff; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background: {{ $primary }}; padding: 20px 28px; color: #ffffff; font-size: 18px; font-weight: bold;">
                            {{ $settings->brand_name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px; font-size: 15px; line-height: 1.55;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 18px 28px; background: #fafafa; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            {{ $settings->brand_name }}
                            @if ($settings->phone_number)<br>Tel. {{ $settings->phone_number }}@endif
                            @if ($settings->contact_email)<br>{{ $settings->contact_email }}@endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
