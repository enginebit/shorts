# Email Integration with Resend

This document describes the email service integration for our Laravel URL shortener application using Resend.

## Overview

Our application uses Resend as the primary email service provider for reliable email delivery. The integration includes:

- **Welcome emails** for new user registrations
- **Workspace invitation emails** for team collaboration
- **Link creation notifications** (optional)
- **Comprehensive email templates** with responsive design
- **Queue-based processing** for better performance
- **Error handling and logging** for reliability

## Configuration

### Environment Variables

Add the following variables to your `.env` file:

```env
# Email Configuration
MAIL_MAILER=resend
MAIL_FROM_ADDRESS="onboarding@resend.dev"
MAIL_FROM_NAME="${APP_NAME}"

# Resend Configuration
RESEND_API_KEY=your_resend_api_key_here

# Queue Configuration (recommended for email processing)
QUEUE_CONNECTION=redis
```

### Services Configuration

The Resend service is configured in `config/services.php`:

```php
'resend' => [
    'key' => env('RESEND_API_KEY'),
],
```

## Email Templates

### Available Templates

1. **Welcome Email** (`resources/views/emails/welcome.blade.php`)
   - Sent to new users after registration
   - Includes getting started guide and helpful resources
   - Workspace information if applicable

2. **Workspace Invitation** (`resources/views/emails/workspace-invitation.blade.php`)
   - Sent when users are invited to join workspaces
   - Includes role information and invitation details
   - Accept/decline links with expiration handling

3. **Link Created Notification** (`resources/views/emails/link-created.blade.php`)
   - Optional notification for new link creation
   - Includes short URL, analytics links, and management options
   - Disabled by default to prevent spam

### Template Features

- **Responsive design** that works on all devices
- **Consistent branding** with your application
- **Professional styling** following modern email best practices
- **Accessibility support** with proper ARIA attributes
- **Dark mode considerations** for better user experience

## Email Service

### EmailService Class

The `App\Services\EmailService` class provides centralized email functionality:

```php
use App\Services\EmailService;

$emailService = new EmailService();

// Send welcome email
$emailService->sendWelcomeEmail($user, $defaultWorkspace);

// Send workspace invitation
$emailService->sendWorkspaceInvitation($invite, $invitedBy);

// Send link creation notification
$emailService->sendLinkCreatedNotification($link, $user);
```

### Features

- **Automatic queuing** for better performance
- **Error handling** with comprehensive logging
- **Bulk operations** for sending multiple emails
- **User preferences** respect (for optional notifications)
- **Statistics and monitoring** capabilities

## Integration Points

### User Registration

Email integration is automatically triggered during user registration in:

- `AuthController::register()` - API registration
- `RegisteredUserController::store()` - Web registration  
- `OAuthController::callback()` - OAuth registration

### Workspace Management

Workspace invitation emails are sent when:

- Users are invited to join workspaces
- Invitations are resent
- Bulk invitations are processed

## Management Commands

### Email Statistics

View email service configuration and statistics:

```bash
php artisan email:stats
```

### Test Email

Send a test email to verify configuration:

```bash
php artisan email:test your@email.com
```

### Test Welcome Email

Send a test welcome email with full template:

```bash
php artisan email:test-welcome your@email.com
```

## Queue Processing

Emails are processed asynchronously using Laravel queues for better performance:

```bash
# Start queue worker
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=emails
```

## Domain Verification

### Using Custom Domains

To use custom domains (e.g., `@yourdomain.com`), you need to:

1. Add your domain in the Resend dashboard
2. Configure DNS records as provided by Resend
3. Wait for domain verification
4. Update `MAIL_FROM_ADDRESS` to use your verified domain

### Default Domain

For testing and development, use the default Resend domain:

```env
MAIL_FROM_ADDRESS="onboarding@resend.dev"
```

## Error Handling

### Logging

All email operations are logged with appropriate context:

- Successful sends are logged at INFO level
- Failures are logged at ERROR level with full stack traces
- User preferences and rate limiting are logged at DEBUG level

### Graceful Degradation

Email failures don't break the user experience:

- Registration continues even if welcome email fails
- Workspace invitations can be resent if delivery fails
- Optional notifications fail silently with logging

## Security Considerations

### API Key Protection

- Store API keys in environment variables only
- Never commit API keys to version control
- Use different keys for different environments
- Rotate keys regularly for security

### Email Content

- All user input is properly escaped in templates
- No sensitive information is included in email content
- Invitation tokens are cryptographically secure
- Email addresses are validated before sending

## Performance Optimization

### Queue Configuration

- Use Redis for queue backend for better performance
- Configure appropriate queue workers for your load
- Monitor queue length and processing times
- Set up queue failure handling

### Rate Limiting

- Resend has built-in rate limiting
- Implement application-level rate limiting for bulk operations
- Add delays between bulk email sends
- Monitor sending quotas and limits

## Monitoring and Analytics

### Email Metrics

Track email performance through:

- Resend dashboard analytics
- Application logs and metrics
- Queue processing statistics
- User engagement tracking

### Health Checks

Regular health checks should verify:

- API key validity
- Domain verification status
- Queue processing health
- Email delivery rates

## Troubleshooting

### Common Issues

1. **Domain not verified**
   - Solution: Verify domain in Resend dashboard or use default domain

2. **API key invalid**
   - Solution: Check API key in environment variables

3. **Queue not processing**
   - Solution: Start queue worker with `php artisan queue:work`

4. **Templates not rendering**
   - Solution: Check template syntax and variable availability

### Debug Commands

```bash
# Check email configuration
php artisan email:stats

# Test basic email sending
php artisan email:test your@email.com

# Check queue status
php artisan queue:monitor

# View recent logs
tail -f storage/logs/laravel.log
```

## Best Practices

### Development

- Use test email addresses during development
- Test all email templates thoroughly
- Verify responsive design on multiple devices
- Test with different email clients

### Production

- Use verified custom domains for branding
- Monitor email delivery rates and bounces
- Set up proper queue workers with supervision
- Implement email preference management
- Regular backup of email templates and configuration

### Security

- Regularly rotate API keys
- Monitor for suspicious email activity
- Implement proper access controls
- Keep email content minimal and secure

## Support

For issues with the email integration:

1. Check the troubleshooting section above
2. Review application logs for error details
3. Consult Resend documentation: https://resend.com/docs
4. Contact the development team with specific error messages

## Future Enhancements

Planned improvements include:

- Email preference management UI
- Advanced email analytics integration
- A/B testing for email templates
- Automated email campaigns
- Enhanced personalization features
- Multi-language email support
