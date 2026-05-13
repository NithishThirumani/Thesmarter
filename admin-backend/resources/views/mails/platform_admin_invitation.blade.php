<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform admin access</title>
    <style>
        body { margin: 0; padding: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f5f5f5; color: #111; }
        .wrap { max-width: 560px; margin: 0 auto; background: #fff; padding: 32px 28px; }
        h1 { font-size: 20px; margin: 0 0 16px; }
        p { line-height: 1.55; font-size: 15px; margin: 0 0 14px; }
        .box { background: #f8f8f8; border: 1px solid #e5e5e5; border-radius: 8px; padding: 16px 18px; margin: 18px 0; }
        .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: #666; margin-bottom: 4px; }
        .mono { font-family: ui-monospace, Consolas, monospace; font-size: 16px; font-weight: 600; }
        .muted { color: #666; font-size: 14px; }
        a.btn { display: inline-block; margin-top: 12px; padding: 10px 18px; background: #111; color: #fff !important; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Welcome, {{ $displayName }}</h1>
    <p>You have been granted <strong>platform admin</strong> access to <strong>{{ config('app.name') }}</strong>.</p>
    <p><strong>Important:</strong> sign-in uses email + a one-time code sent to your inbox after you enter your PIN on the login screen.</p>
    <div class="box">
        <div class="label">Login URL</div>
        <p style="margin:0 0 12px;"><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
        <div class="label">Email (login)</div>
        <p class="mono" style="margin:0 0 12px;">{{ $loginEmail }}</p>
        <div class="label">Your initial PIN (4 digits)</div>
        <p class="mono" style="margin:0;">{{ $plainPin }}</p>
    </div>
    <p class="muted">Enter your email on the login page, confirm this PIN when prompted, then complete sign-in with the OTP emailed to you. Keep your PIN confidential.</p>
    <p><a class="btn" href="{{ $loginUrl }}">Open admin login</a></p>
</div>
</body>
</html>
