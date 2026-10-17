<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunteerHub verification</title>
</head>
<body style="margin:0;padding:0;background:#eef3f8;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:560px;margin:0 auto;padding:32px 16px;">
        <div style="text-align:center;margin-bottom:16px;">
            <p style="margin:0;font-size:13px;font-weight:bold;letter-spacing:.12em;text-transform:uppercase;color:#0097d6;">VolunteerHub</p>
        </div>
        <div style="background:#fff;border:1px solid #dbe4ee;border-radius:14px;overflow:hidden;">
            <div style="background:#0097d6;color:#fff;padding:22px 24px;">
                <h1 style="margin:0;font-size:20px;">Verify your email</h1>
            </div>
            <div style="padding:24px;">
                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Hello {{ $name }},</p>
                <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#334155;">
                    Use this code to finish your {{ $accountType === 'organization' ? 'organization' : 'volunteer' }} registration on VolunteerHub.
                </p>
                <p style="margin:0 0 8px;font-size:11px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;color:#64748b;">Verification code</p>
                <p style="margin:0 0 18px;font-size:32px;font-weight:bold;letter-spacing:.28em;color:#0f172a;">{{ $code }}</p>
                <p style="margin:0;font-size:13px;line-height:1.55;color:#64748b;">
                    This code expires in 15 minutes. If you did not start a VolunteerHub signup, you can ignore this email.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
