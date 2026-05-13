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
        .muted { color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Welcome</h1>
    <p>
        <strong>{{ $companyName }}</strong>@if($legalName) <span class="muted">({{ $legalName }})</span>@endif has been added on {{ config('app.name') }}.
        You can sign in with the customer app using the credentials your administrator shared with you.
    </p>
    <p class="muted">
        If you did not expect this message, you can ignore it or contact support.
    </p>
</div>
</body>
</html>
