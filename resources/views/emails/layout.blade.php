<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('app.name'))</title>
    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 32px 24px;
            text-align: center;
        }

        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .email-header p {
            color: #d1fae5;
            font-size: 16px;
        }

        .email-body {
            padding: 32px 24px;
        }

        .email-content h2 {
            color: #111827;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .email-content p {
            margin-bottom: 16px;
            font-size: 16px;
            line-height: 1.6;
        }

        .email-content ul {
            margin-bottom: 16px;
            padding-left: 20px;
        }

        .email-content li {
            margin-bottom: 8px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #10b981;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 16px 0;
            transition: background-color 0.2s;
        }

        .button:hover {
            background-color: #059669;
        }

        .button-secondary {
            background-color: #6b7280;
        }

        .button-secondary:hover {
            background-color: #4b5563;
        }

        .button-danger {
            background-color: #ef4444;
        }

        .button-danger:hover {
            background-color: #dc2626;
        }

        .code-block {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            margin: 16px 0;
            word-break: break-all;
        }

        .email-footer {
            background-color: #f9fafb;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .email-footer p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .email-footer a {
            color: #10b981;
            text-decoration: none;
        }

        .email-footer a:hover {
            text-decoration: underline;
        }

        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 24px 0;
        }

        .highlight-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
        }

        .warning-box {
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
        }

        /* Responsive styles */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .email-header,
            .email-body,
            .email-footer {
                padding: 24px 16px;
            }

            .button {
                display: block;
                text-align: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{{ config('app.name') }}</h1>
            <p>@yield('header-subtitle', 'Your URL shortener')</p>
        </div>

        <div class="email-body">
            <div class="email-content">
                @yield('content')
            </div>
        </div>

        <div class="email-footer">
            <p>
                This email was sent by {{ config('app.name') }}.
                <br>
                If you have any questions, please contact us at 
                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            </p>
            <p>
                <a href="{{ config('app.url') }}">Visit our website</a> |
                <a href="{{ config('app.url') }}/privacy">Privacy Policy</a> |
                <a href="{{ config('app.url') }}/terms">Terms of Service</a>
            </p>
        </div>
    </div>
</body>
</html>
