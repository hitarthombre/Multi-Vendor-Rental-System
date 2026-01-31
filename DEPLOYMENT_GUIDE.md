# Multi-Vendor Rental Platform - Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the Multi-Vendor Rental Platform in a XAMPP environment on Windows.

## Prerequisites

### System Requirements
- Windows 10/11
- XAMPP 8.1+ with PHP 8.1+, Apache, and MySQL
- At least 2GB RAM
- 5GB free disk space
- Internet connection for external services

### Required PHP Extensions
- PDO and PDO_MySQL
- mbstring
- openssl
- curl
- gd
- fileinfo
- json

## Installation Steps

### 1. XAMPP Setup

1. **Download and Install XAMPP**
   - Download XAMPP from https://www.apachefriends.org/
   - Install with PHP 8.1+, Apache, and MySQL components
   - Start Apache and MySQL services

2. **Configure Apache Port**
   - Open XAMPP Control Panel
   - Click "Config" next to Apache
   - Edit `httpd.conf` and change `Listen 80` to `Listen 8081`
   - Edit `httpd-ssl.conf` and change `Listen 443` to `Listen 8444`
   - Restart Apache

3. **Verify Installation**
   - Open browser and navigate to `http://localhost:8081`
   - You should see the XAMPP dashboard

### 2. Project Deployment

1. **Clone Repository**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/your-repo/Multi-Vendor-Rental-System.git
   cd Multi-Vendor-Rental-System
   ```

2. **Set Directory Permissions**
   - Ensure the web server has read/write access to:
     - `storage/` directory
     - `logs/` directory
     - `public/storage/` directory

### 3. Database Setup

1. **Create Database**
   - Open phpMyAdmin at `http://localhost/phpmyadmin`
   - Create a new database named `rental_platform`
   - Set collation to `utf8mb4_unicode_ci`

2. **Import Database Schema**
   ```bash
   php setup-database.php
   ```

3. **Seed Initial Data**
   ```bash
   php seed-data.php
   php seed-pricing.php
   ```

### 4. Configuration Setup

1. **Run Storage Setup**
   ```bash
   php setup-storage.php
   ```

2. **Run Logging Setup**
   ```bash
   php setup-logging.php
   ```

3. **Configure Email Service**
   - Edit `config/email.php`
   - Update SMTP credentials:
     ```php
     'username' => 'your-email@gmail.com',
     'password' => 'your-app-password',
     ```
   - Test configuration:
     ```bash
     php test-email-config.php
     ```

4. **Configure Razorpay**
   - Razorpay test credentials are already configured
   - For production, update `config/razorpay.php` with live credentials

### 5. Verification

1. **Test Database Connection**
   ```bash
   php test-connection.php
   ```

2. **Verify Application Access**
   - Navigate to `http://localhost:8081`
   - You should see the rental platform homepage

3. **Test Admin Access**
   - Create admin user:
     ```bash
     php create-admin.php
     ```
   - Login at `http://localhost:8081/admin/login.php`

## Configuration Files

### Database Configuration
File: `config/database.php`
```php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'rental_platform',
    'username' => 'root',
    'password' => '', // Default XAMPP password
];
```

### XAMPP Configuration
File: `config/xampp.php`
- Apache port: 8081
- MySQL port: 3306
- Document root: `/xampp/htdocs/Multi-Vendor-Rental-System/public`

### Email Configuration
File: `config/email.php`
- SMTP host: smtp.gmail.com
- Port: 587 (TLS)
- Update username and password

### Razorpay Configuration
File: `config/razorpay.php`
- Test credentials are pre-configured
- Webhook endpoint: `/api/webhooks/razorpay.php`

## Security Setup

### File Permissions
- `storage/private/` - No web access (protected by .htaccess)
- `logs/` - No web access (protected by .htaccess)
- `config/` - No direct web access
- `public/storage/` - Web accessible for images

### Security Headers
The `.htaccess` file includes:
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block

## Cron Jobs Setup

### Error Handling Monitor
Add to Windows Task Scheduler or use XAMPP cron:
```bash
# Run every hour
0 * * * * php C:\xampp\htdocs\Multi-Vendor-Rental-System\cron\error-handling-monitor.php
```

### Auto-Approval Processing
```bash
# Run every 15 minutes
*/15 * * * * php C:\xampp\htdocs\Multi-Vendor-Rental-System\cron\process-auto-approvals.php
```

### Log Rotation
```bash
# Run daily at 2 AM
0 2 * * * php C:\xampp\htdocs\Multi-Vendor-Rental-System\cron\rotate-logs.php
```

## Monitoring and Maintenance

### Log Files
- Application logs: `logs/application.log`
- Error logs: `logs/error.log`
- Security logs: `logs/security.log`
- Payment logs: `logs/payment.log`
- PHP errors: `logs/php_errors.log`

### Admin Dashboard
- Access: `http://localhost:8081/admin/dashboard.php`
- Error handling: `http://localhost:8081/admin/error-handling.php`
- System monitoring and statistics available

### Database Maintenance
- Regular backups recommended
- Monitor database size and performance
- Use phpMyAdmin for database administration

## Troubleshooting

### Common Issues

1. **Apache Won't Start**
   - Check if port 8081 is available
   - Verify httpd.conf configuration
   - Check Windows Firewall settings

2. **Database Connection Failed**
   - Ensure MySQL service is running
   - Verify database credentials in config/database.php
   - Check if database exists

3. **File Upload Issues**
   - Check directory permissions
   - Verify PHP upload settings in php.ini
   - Ensure storage directories exist

4. **Email Not Sending**
   - Verify SMTP credentials
   - Check Gmail App Password setup
   - Test with test-email-config.php

5. **Payment Integration Issues**
   - Verify Razorpay credentials
   - Check webhook configuration
   - Monitor payment logs

### Debug Mode
For development, enable debug mode:
1. Edit `config/logging.php`
2. Set `display_errors` to `true`
3. Set log level to `debug`

### Performance Optimization
1. Enable PHP OPcache in php.ini
2. Configure Apache compression
3. Optimize database queries
4. Use CDN for static assets (production)

## Production Deployment

### Additional Steps for Production
1. **Security Hardening**
   - Change default passwords
   - Enable HTTPS with SSL certificate
   - Configure firewall rules
   - Regular security updates

2. **Performance Optimization**
   - Enable caching mechanisms
   - Optimize database indexes
   - Configure load balancing (if needed)

3. **Monitoring**
   - Set up application monitoring
   - Configure alerting for critical errors
   - Regular backup procedures

4. **Environment Configuration**
   - Update Razorpay to live credentials
   - Configure production email settings
   - Set appropriate error reporting levels

## Support and Maintenance

### Regular Tasks
- Monitor log files for errors
- Check system performance
- Update dependencies
- Backup database regularly
- Review security logs

### Contact Information
- System Administrator: admin@rentalhub.com
- Technical Support: support@rentalhub.com

## Version Information
- Platform Version: 1.0.0
- PHP Version: 8.1+
- MySQL Version: 8.0+
- Last Updated: February 1, 2026

---

**Note**: This deployment guide is specifically for XAMPP development environment. For production deployment, additional security and performance considerations apply.