@extends('emails.layout')

@section('title', 'Your short link is ready!')

@section('header-subtitle', 'Link created successfully')

@section('content')
    <h2>Your short link is ready! 🔗</h2>

    <p>Hi {{ $user->name }},</p>

    <p>
        Great news! Your short link has been created successfully and is ready to use.
    </p>

    <div class="highlight-box">
        <p><strong>Your new short link:</strong></p>
        <div class="code-block">
            <a href="{{ $shortUrl }}" target="_blank">{{ $shortUrl }}</a>
        </div>
        <p><strong>Original URL:</strong></p>
        <div class="code-block" style="word-break: break-all;">
            {{ $originalUrl }}
        </div>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $shortUrl }}" class="button" target="_blank">
            Test Your Link
        </a>
    </div>

    <h2>📊 Track your link's performance</h2>

    <p>
        Your link is now being tracked! You can monitor its performance, see where clicks are 
        coming from, and get detailed analytics to understand your audience better.
    </p>

    <ul>
        <li><strong>Real-time analytics</strong> - See clicks as they happen</li>
        <li><strong>Geographic data</strong> - Know where your audience is located</li>
        <li><strong>Referrer tracking</strong> - Understand how people find your links</li>
        <li><strong>Device insights</strong> - See what devices your audience uses</li>
        <li><strong>Time-based data</strong> - Track performance over time</li>
    </ul>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $analyticsUrl }}" class="button button-secondary">
            View Analytics
        </a>
    </div>

    <div class="divider"></div>

    <h2>🛠️ Manage your link</h2>

    <p>Need to make changes to your link? You can easily:</p>

    <ul>
        <li><strong>Edit the destination</strong> - Update where your link points</li>
        <li><strong>Customize settings</strong> - Add password protection, expiration dates, and more</li>
        <li><strong>Update metadata</strong> - Change the title, description, or social media preview</li>
        <li><strong>Configure redirects</strong> - Set up device-specific or geo-targeted redirects</li>
    </ul>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $editUrl }}" class="button button-secondary">
            Edit Link Settings
        </a>
    </div>

    <h2>📋 Link Details</h2>

    <div class="highlight-box">
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>Short URL:</strong> {{ $shortUrl }}</li>
            <li><strong>Created:</strong> {{ $createdAt->format('M j, Y \a\t g:i A') }}</li>
            @if($link->title)
                <li><strong>Title:</strong> {{ $link->title }}</li>
            @endif
            @if($link->description)
                <li><strong>Description:</strong> {{ $link->description }}</li>
            @endif
            @if($link->expires_at)
                <li><strong>Expires:</strong> {{ $link->expires_at->format('M j, Y \a\t g:i A') }}</li>
            @endif
            @if($link->password)
                <li><strong>Password Protected:</strong> Yes</li>
            @endif
        </ul>
    </div>

    <h2>💡 Pro Tips</h2>

    <div class="highlight-box">
        <p><strong>Make the most of your short links:</strong></p>
        <ul style="margin: 8px 0; padding-left: 20px;">
            <li>Share your link on social media to track engagement</li>
            <li>Use UTM parameters in your original URL for even more detailed tracking</li>
            <li>Set up custom domains for branded links that build trust</li>
            <li>Create QR codes for offline marketing campaigns</li>
            <li>Use our API to automate link creation in your workflows</li>
        </ul>
    </div>

    <h2>🤝 Need help?</h2>

    <p>
        If you have any questions about your link or need help with {{ config('app.name') }}, 
        our support team is here to help. Contact us at 
        <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.
    </p>

    <p>
        You can also check out our <a href="{{ config('app.url') }}/docs">documentation</a> 
        for guides and tutorials on getting the most out of {{ config('app.name') }}.
    </p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $shortUrl }}" class="button" target="_blank">
            {{ $shortUrl }}
        </a>
    </div>

    <p>
        Happy link sharing!
    </p>

    <p>
        Best regards,<br>
        The {{ config('app.name') }} Team
    </p>
@endsection
