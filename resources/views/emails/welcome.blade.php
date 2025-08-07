@extends('emails.layout')

@section('title', 'Welcome to ' . config('app.name'))

@section('header-subtitle', 'Welcome aboard!')

@section('content')
    <h2>Welcome to {{ config('app.name') }}, {{ $user->name }}! 🎉</h2>

    <p>
        Thank you for joining {{ config('app.name') }}, the modern URL shortener that helps you create, 
        manage, and track your short links with ease.
    </p>

    <div class="highlight-box">
        <p><strong>Your account is now active and ready to use!</strong></p>
        @if($workspace)
            <p>We've created your default workspace: <strong>{{ $workspace->name }}</strong></p>
        @endif
    </div>

    <h2>🚀 Get started in minutes</h2>

    <p>Here's what you can do with {{ config('app.name') }}:</p>

    <ul>
        <li><strong>Create short links</strong> - Turn long URLs into memorable short links</li>
        <li><strong>Track performance</strong> - Get detailed analytics on clicks, locations, and more</li>
        <li><strong>Customize domains</strong> - Use your own custom domain for branded links</li>
        <li><strong>Collaborate</strong> - Invite team members to your workspace</li>
        <li><strong>API access</strong> - Integrate with your existing tools and workflows</li>
    </ul>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $dashboardUrl }}" class="button">
            Access Your Dashboard
        </a>
    </div>

    <div class="divider"></div>

    <h2>📚 Helpful resources</h2>

    <p>To help you get the most out of {{ config('app.name') }}, here are some useful resources:</p>

    <ul>
        <li><a href="{{ config('app.url') }}/docs">Documentation</a> - Learn how to use all features</li>
        <li><a href="{{ config('app.url') }}/api">API Reference</a> - Integrate with your applications</li>
        <li><a href="{{ config('app.url') }}/examples">Examples</a> - See what others are building</li>
        <li><a href="{{ config('app.url') }}/support">Support Center</a> - Get help when you need it</li>
    </ul>

    <div class="highlight-box">
        <p><strong>💡 Pro tip:</strong> Start by creating your first short link! It only takes a few seconds and you'll see how powerful our analytics can be.</p>
    </div>

    <h2>🤝 Need help?</h2>

    <p>
        Our team is here to help you succeed. If you have any questions or need assistance getting started, 
        don't hesitate to reach out to us at 
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
    </p>

    <p>
        You can also join our community of users who are building amazing things with {{ config('app.name') }}.
    </p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $loginUrl }}" class="button button-secondary">
            Sign In to Your Account
        </a>
    </div>

    <p>
        Welcome to the {{ config('app.name') }} family! We're excited to see what you'll build.
    </p>

    <p>
        Best regards,<br>
        The {{ config('app.name') }} Team
    </p>
@endsection
