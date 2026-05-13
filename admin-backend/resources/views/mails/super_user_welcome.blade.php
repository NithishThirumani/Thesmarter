<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body { margin: 0; padding: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f5f5f5; color: #111; }
        .wrap { max-width: 560px; margin: 0 auto; background: #fff; padding: 32px 28px; }
        h1 { font-size: 20px; margin: 0 0 16px; }
        p { line-height: 1.55; font-size: 15px; margin: 0 0 14px; }
        .box { background: #f8f8f8; border: 1px solid #e5e5e5; border-radius: 8px; padding: 16px 18px; margin: 18px 0; }
        .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: #666; margin-bottom: 4px; }
        .mono { font-family: ui-monospace, Consolas, monospace; font-size: 18px; font-weight: 600; }
        .muted { color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Hello {{ $displayName }},</h1>
    <p>
        @if($plainPin)
            You have been set up as a <strong>Super User</strong> for <strong>{{ $companyName }}</strong>.
            Use the mobile number below as your login ID and the PIN we generated for you.
        @else
            You have been granted <strong>Super User</strong> access for <strong>{{ $companyName }}</strong>.
            Sign in with your existing mobile number and PIN for the app.
        @endif
    </p>
    <div class="box">
        <div class="label">Company</div>
        <p class="mono" style="margin:0 0 12px;">{{ $companyName }}</p>
        <div class="label">Mobile (login)</div>
        <p class="mono" style="margin:0;">{{ $mobile }}</p>
        @if($plainPin)
            <div style="margin-top:14px;"></div>
            <div class="label">Your PIN</div>
            <p class="mono" style="margin:0;">{{ $plainPin }}</p>
        @endif
    </div>
    <p class="muted">
        @if($plainPin)
            Open the app, enter your mobile number when prompted, then enter this PIN. Keep your PIN confidential and do not share it with anyone.
        @else
            If you forgot your PIN, use the app&rsquo;s reset flow or contact support.
        @endif
    </p>
</div>
</body>
</html>
