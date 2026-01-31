# Notification System Documentation

## Overview

The Multi-Vendor Rental Platform includes a comprehensive notification system that handles email notifications for all major events in the rental lifecycle. The system provides reliable delivery, retry mechanisms, logging, and monitoring capabilities.

## Features

### ✅ Task 20.1: Email Notification Service
- **SMTP Integration**: Configured with Gmail SMTP (hitarththombre@gmail.com)
- **HTML Email Templates**: Professional email templates with platform branding
- **Email Service**: Robust email sending with error handling
- **Database Storage**: All notifications stored in database for tracking

### ✅ Task 20.2: Notification Triggers
- **Payment Success**: Triggered when orders are created after payment verification
- **Approval Request**: Sent to vendors when orders require manual approval
- **Order Approved**: Notifies customers when vendors approve orders
- **Order Rejected**: Notifies customers with refund information
- **Rental Activation**: Confirms rental start for both parties
- **Rental Completion**: Notifies completion and deposit status
- **Refund Processing**: Confirms refund initiation and timeline

### ✅ Task 20.3: Notification Logging and Retry
- **Comprehensive Logging**: All notification attempts logged with status
- **Retry Mechanism**: Failed notifications automatically retried with backoff
- **Batch Processing**: Cron job for processing pending and failed notifications
- **Statistics & Monitoring**: Real-time statistics and health monitoring
- **Cleanup**: Automatic cleanup of old notifications

## Architecture

### Components

1. **NotificationService** (`src/Services/NotificationService.php`)
   - Main service for sending notifications
   - Handles all notification types
   - Manages retry logic and error handling

2. **EmailService** (`src/Services/EmailService.php`)
   - SMTP email sending functionality
   - HTML email template generation
   - Gmail integration with app password

3. **Notification Model** (`src/Models/Notification.php`)
   - Database entity for notification records
   - Status management (Pending, Sent, Failed)
   - Timestamp tracking

4. **NotificationRepository** (`src/Repositories/NotificationRepository.php`)
   - Database operations for notifications
   - Query methods for statistics and monitoring
   - Batch operations for cleanup

### Database Schema

```sql
notifications (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) FOREIGN KEY,
    event_type VARCHAR(100),
    subject VARCHAR(500),
    body TEXT,
    sent_at TIMESTAMP NULL,
    status ENUM('Pending', 'Sent', 'Failed'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

## Configuration

### SMTP Settings
- **Host**: smtp.gmail.com
- **Port**: 587 (TLS)
- **Username**: hitarththombre@gmail.com
- **Password**: qpkykbbhmtorwtze (App Password)

### Email Templates
All emails use a consistent HTML template with:
- Platform branding and colors
- Responsive design
- Professional styling
- Clear call-to-action buttons

## Usage

### Sending Notifications

```php
use RentalPlatform\Services\NotificationService;

$notificationService = new NotificationService();

// Send order created notification
$notificationService->sendOrderCreatedNotification($customerId, $order);

// Send approval request
$notificationService->sendApprovalRequestNotification($vendorId, $order);

// Send test notification
$notificationService->sendTestNotification($userId);
```

### Processing Notifications

```php
// Process pending notifications
$processed = $notificationService->processPendingNotifications(100);

// Retry failed notifications
$retried = $notificationService->retryFailedNotifications(50, 30); // 30 min backoff

// Get statistics
$stats = $notificationService->getStatistics();

// Health check
$health = $notificationService->healthCheck();
```

## Cron Jobs

### Notification Processor
Run every 5-10 minutes:
```bash
*/5 * * * * php /path/to/project/cron/process-notifications.php
```

The cron job:
- Processes up to 100 pending notifications
- Retries up to 50 failed notifications (with 30-minute backoff)
- Cleans up notifications older than 30 days
- Logs all activities

## Monitoring

### Admin Interface
Access the notification management dashboard at:
`/admin/notifications.php`

Features:
- Real-time system health status
- Notification statistics by status
- Manual processing controls
- Test notification sending

### API Endpoints
`/api/notifications.php` provides:

**GET Actions:**
- `statistics` - Get notification counts by status
- `health` - System health check
- `by_event_type` - Filter notifications by event type
- `user_notifications` - Get notifications for specific user

**POST Actions:**
- `process_pending` - Manually process pending notifications
- `retry_failed` - Manually retry failed notifications
- `cleanup_old` - Clean up old notifications
- `send_test` - Send test notification

### Health Monitoring

The system monitors:
- **Pending Queue**: Alerts if > 100 pending notifications
- **Failed Queue**: Warns if > 50 failed notifications
- **Email Service**: Tests SMTP connectivity
- **Processing Time**: Tracks notification processing performance

## Event Types

| Event Type | Trigger | Recipients |
|------------|---------|------------|
| `order_created` | Order created after payment | Customer |
| `approval_request` | Order requires vendor approval | Vendor |
| `order_approved` | Vendor approves order | Customer |
| `order_rejected` | Vendor rejects order | Customer |
| `rental_activated` | Order becomes active | Vendor |
| `rental_completed` | Rental marked complete | Customer & Vendor |
| `refund_processed` | Refund initiated | Customer |
| `test_notification` | Manual test | Specified user |

## Error Handling

### Retry Logic
- Failed notifications are retried with exponential backoff
- Minimum 30-minute delay between retry attempts
- Maximum retry attempts: Unlimited (until successful or manually removed)

### Logging
All notification activities are logged:
- Successful sends
- Failed attempts with error details
- Retry attempts
- Processing statistics

### Fallback Mechanisms
- Database storage ensures no notifications are lost
- Cron job provides automatic recovery
- Manual processing available via admin interface
- Health monitoring alerts to issues

## Security

### Email Security
- Uses Gmail App Password (not regular password)
- TLS encryption for SMTP connection
- No sensitive data in email content

### Access Control
- Admin interface requires admin permissions
- User notifications filtered by user ID
- API endpoints validate permissions

### Data Protection
- Notification content stored securely
- Old notifications automatically cleaned up
- No PII in logs (only user IDs)

## Performance

### Optimization
- Batch processing for efficiency
- Database indexes on key columns
- Configurable processing limits
- Automatic cleanup of old data

### Scalability
- Stateless design allows horizontal scaling
- Database-backed queue handles high volume
- Configurable retry intervals
- Monitoring for performance tuning

## Troubleshooting

### Common Issues

1. **Emails not sending**
   - Check SMTP credentials
   - Verify Gmail app password
   - Check network connectivity
   - Review error logs

2. **High pending queue**
   - Run manual processing
   - Check cron job status
   - Verify database connectivity
   - Review processing limits

3. **High failure rate**
   - Check email service health
   - Verify recipient email addresses
   - Review SMTP configuration
   - Check for rate limiting

### Debugging

1. **Check notification status**:
   ```php
   $stats = $notificationService->getStatistics();
   ```

2. **Review health status**:
   ```php
   $health = $notificationService->healthCheck();
   ```

3. **Test email service**:
   ```php
   $success = $notificationService->sendTestNotification($userId);
   ```

4. **Check logs**:
   - Application logs: `error_log()`
   - Cron logs: `logs/notification-cron.log`
   - Database: `notifications` table

## Maintenance

### Regular Tasks
- Monitor notification statistics
- Review failed notification reasons
- Update email templates as needed
- Verify cron job execution
- Clean up old logs

### Updates
- SMTP credentials rotation
- Template updates for new features
- Performance optimization
- Security patches

## Integration

### Order Service Integration
The notification system is fully integrated with the OrderService:
- Automatic triggers on status changes
- Proper error handling
- Audit logging integration

### Future Enhancements
- SMS notifications
- Push notifications
- Email preferences per user
- Advanced templates
- A/B testing for email content
- Webhook notifications for external systems

## Compliance

### Requirements Satisfied
- **Requirement 19.7**: SMTP configuration with provided credentials ✅
- **Requirements 19.1-19.6**: All notification triggers implemented ✅
- **Task 20.1**: Email notification service complete ✅
- **Task 20.2**: Notification triggers implemented ✅
- **Task 20.3**: Logging and retry mechanisms complete ✅

The notification system provides a robust, scalable, and maintainable solution for all platform communication needs.