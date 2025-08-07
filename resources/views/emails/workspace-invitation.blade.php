@extends('emails.layout')

@section('title', 'Workspace Invitation - ' . $workspace->name)

@section('header-subtitle', 'You\'ve been invited!')

@section('content')
    <h2>You're invited to join {{ $workspace->name }}! 🎉</h2>

    <p>
        <strong>{{ $invitedBy->name }}</strong> ({{ $invitedBy->email }}) has invited you to collaborate 
        in the <strong>{{ $workspace->name }}</strong> workspace on {{ config('app.name') }}.
    </p>

    <div class="highlight-box">
        <p><strong>Invitation Details:</strong></p>
        <ul style="margin: 8px 0; padding-left: 20px;">
            <li><strong>Workspace:</strong> {{ $workspace->name }}</li>
            <li><strong>Role:</strong> {{ ucfirst($role) }}</li>
            <li><strong>Invited by:</strong> {{ $invitedBy->name }}</li>
            @if($expiresAt)
                <li><strong>Expires:</strong> {{ $expiresAt->format('M j, Y \a\t g:i A') }}</li>
            @endif
        </ul>
    </div>

    <h2>🚀 What you'll be able to do</h2>

    <p>As a <strong>{{ ucfirst($role) }}</strong> in this workspace, you'll be able to:</p>

    @if($role === 'owner' || $role === 'admin')
        <ul>
            <li><strong>Create and manage links</strong> - Full access to all link management features</li>
            <li><strong>View analytics</strong> - Access detailed performance metrics and insights</li>
            <li><strong>Manage team members</strong> - Invite and manage other workspace members</li>
            <li><strong>Configure settings</strong> - Customize workspace settings and preferences</li>
            <li><strong>API access</strong> - Use API keys for integrations and automation</li>
        </ul>
    @elseif($role === 'member')
        <ul>
            <li><strong>Create and manage links</strong> - Create short links and manage your own links</li>
            <li><strong>View analytics</strong> - Access performance metrics for your links</li>
            <li><strong>Collaborate</strong> - Work with other team members on shared projects</li>
            <li><strong>API access</strong> - Use API keys for integrations (with workspace limits)</li>
        </ul>
    @else
        <ul>
            <li><strong>View links</strong> - See links created by the team</li>
            <li><strong>View analytics</strong> - Access basic performance metrics</li>
            <li><strong>Collaborate</strong> - Participate in team discussions and planning</li>
        </ul>
    @endif

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $acceptUrl }}" class="button">
            Accept Invitation
        </a>
        <br>
        <a href="{{ $declineUrl }}" class="button button-secondary" style="margin-top: 12px;">
            Decline Invitation
        </a>
    </div>

    <div class="divider"></div>

    <h2>📋 About {{ $workspace->name }}</h2>

    @if($workspace->description)
        <p>{{ $workspace->description }}</p>
    @else
        <p>
            This workspace is where {{ $invitedBy->name }} and their team collaborate on URL shortening 
            and link management projects.
        </p>
    @endif

    <div class="highlight-box">
        <p><strong>💡 New to {{ config('app.name') }}?</strong></p>
        <p>
            {{ config('app.name') }} is a powerful URL shortener that helps teams create, manage, 
            and track short links with detailed analytics and collaboration features.
        </p>
    </div>

    <h2>🔒 Security & Privacy</h2>

    <p>
        Your privacy and security are important to us. This invitation is secure and can only be 
        used once. If you don't recognize the person who invited you or believe this invitation 
        was sent in error, you can safely ignore this email.
    </p>

    @if($expiresAt)
        <div class="warning-box">
            <p><strong>⏰ Time-sensitive invitation</strong></p>
            <p>
                This invitation will expire on <strong>{{ $expiresAt->format('M j, Y \a\t g:i A') }}</strong>. 
                Please accept or decline before then.
            </p>
        </div>
    @endif

    <h2>🤝 Need help?</h2>

    <p>
        If you have any questions about this invitation or need help getting started with 
        {{ config('app.name') }}, feel free to reach out to us at 
        <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.
    </p>

    <p>
        You can also contact {{ $invitedBy->name }} directly at 
        <a href="mailto:{{ $invitedBy->email }}">{{ $invitedBy->email }}</a> if you have 
        questions about the workspace or your role.
    </p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $acceptUrl }}" class="button">
            Join {{ $workspace->name }}
        </a>
    </div>

    <p>
        We're excited to have you join the {{ config('app.name') }} community!
    </p>

    <p>
        Best regards,<br>
        The {{ config('app.name') }} Team
    </p>
@endsection
