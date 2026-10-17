<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your VolunteerHub password</title>
</head>
<body style="margin:0;padding:0;background:#eef3f8;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:560px;margin:0 auto;padding:32px 16px;">
        <div style="text-align:center;margin-bottom:16px;">
            <p style="margin:0;font-size:13px;font-weight:bold;letter-spacing:.12em;text-transform:uppercase;color:#0097d6;">VolunteerHub</p>
        </div>
        <div style="background:#fff;border:1px solid #dbe4ee;border-radius:14px;overflow:hidden;">
            <div style="background:#0097d6;color:#fff;padding:22px 24px;">
                <h1 style="margin:0;font-size:20px;">Reset your password</h1>
            </div>
            <div style="padding:24px;">
                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Hello {{ $name }},</p>
                <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#334155;">
                    We received a request to reset your VolunteerHub password. Use the button below to choose a new one.
                </p>
                <p style="margin:0 0 22px;text-align:center;">
                    <a href="{{ $resetUrl }}" style="display:inline-block;background:#0097d6;color:#fff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:10px;">
                        Reset password
                    </a>
                </p>
                <p style="margin:0 0 12px;font-size:12px;line-height:1.55;color:#64748b;word-break:break-all;">
                    Or paste this link into your browser:<br>{{ $resetUrl }}
                </p>
                <p style="margin:0;font-size:13px;line-height:1.55;color:#64748b;">
                    This link expires in 60 minutes. If you did not request a reset, you can ignore this email.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
