# Troubleshooting Guide

## Common Issues and Solutions

### 1. Apache/XAMPP Issues

#### Apache Won't Start
**Symptoms**: Apache service fails to start in XAMPP Control Panel

**Solutions**:
1. **Port Conflict**
   - Check if port 8081 is in use: `netstat -an | findstr :8081`
   - Kill conflicting processes or change Apache port
   - Edit `C:\xampp\apache\conf\httpd.conf` and change `Listen 80` to `Listen 8081`

2. **Windows Firewall**
   - Add Apache to Windows Firewall exceptions
   - Allow port 8081 through firewall

3. **Antivirus Software**
   - Add XAMPP directory to antivirus exclusions
   - Temporarily disable real-time protection during setup

#### MySQL Won't Start
**Symptoms**: MySQL service fails to start

**Solutions**:
1. **Port 3306 in use**
   - Check: `netstat -an | findstr :3306`
   - Stop other MySQL services
   - Change MySQL port in XAMPP config

2. **Corrupted Database**
   - Backup data directory
   - Repair MySQL installation
   - Restore from backup if needed

### 2. Database Issues

#### Connection Failed
**Error**: `SQLSTATE[HY000] [2002] No connection could be made`

**Solutions**:
1. Verify MySQL service is running in XAMPP
2. Check database credentials in `config/database.php`
3. Ensure database `rental_platform` exists
4. Test connection: `php test-connection.php`

#### Database Not Found
**Error**: `SQLSTATE[HY000] [1049] Unknown database 'rental_platform'`

**Solutions**:
1. Create database manually in phpMyAdmin
2. Run setup script: `php setup-database.php`
3. Check database name in configuration

#### Permission Denied
**Error**: `Access denied for user 'root'@'localhost'`

**Solutions**:
1. Reset MySQL root password in XAMPP
2. Update password in `config/database.php`
3. Grant proper permissions to database user

### 3. File System Issues

#### Storage Directory Not Writable
**Error**: `Permission denied` when uploading files

**Solutions**:
1. Run storage setup: `php setup-storage.php`
2. Check directory permissions (should be 755)
3. Ensure web server has write access
4. Create directories manually if needed:
   ```bash
   mkdir storage
   mkdir storage/private
   mkdir public/storage
   ```

#### Log Files Not Created
**Error**: Logs not appearing in `logs/` directory

**Solutions**:
1. Run logging setup: `php setup-logging.php`
2. Check directory permissions
3. Verify PHP error_log configuration
4. Create logs directory manually: `mkdir logs`

### 4. Email Issues

#### SMTP Connection Failed
**Error**: `SMTP connect() failed`

**Solutions**:
1. **Gmail Configuration**
   - Enable 2-factor authentication
   - Generate App Password
   - Use App Password instead of regular password

2. **SMTP Settings**
   - Host: smtp.gmail.com
   - Port: 587
   - Encryption: TLS
   - Test: `php test-email-config.php`

3. **Firewall Issues**
   - Allow outbound connections on port 587
   - Check corporate firewall settings

#### Authentication Failed
**Error**: `SMTP Error: Could not authenticate`

**Solutions**:
1. Verify email and password in `config/email.php`
2. Use Gmail App Password (not regular password)
3. Enable "Less secure app access" (not recommended)

### 5. Payment Integration Issues

#### Razorpay Webhook Not Working
**Error**: Webhook calls failing or not received

**Solutions**:
1. Verify webhook URL: `http://localhost:8081/api/webhooks/razorpay.php`
2. Check webhook secret in `config/razorpay.php`
3. Test webhook endpoint manually
4. Check payment logs: `logs/payment.log`

#### Payment Verification Failed
**Error**: Payment signature verification fails

**Solutions**:
1. Verify Razorpay key_id and key_secret
2. Check signature calculation
3. Ensure webhook secret matches
4. Monitor payment logs for details

### 6. Session Issues

#### Session Not Starting
**Error**: `session_start(): Cannot send session cookie`

**Solutions**:
1. Check if output sent before session_start()
2. Verify session directory permissions
3. Clear browser cookies
4. Check PHP session configuration

#### Login Not Working
**Error**: User cannot login or session expires immediately

**Solutions**:
1. Check session configuration in PHP
2. Verify password hashing/verification
3. Clear browser cache and cookies
4. Check user credentials in database

### 7. Performance Issues

#### Slow Page Loading
**Symptoms**: Pages take long time to load

**Solutions**:
1. **Database Optimization**
   - Check slow query log
   - Add missing indexes
   - Optimize large queries

2. **PHP Configuration**
   - Increase memory_limit in php.ini
   - Enable OPcache
   - Optimize autoloading

3. **File System**
   - Check disk space
   - Optimize file operations
   - Use SSD if possible

#### High Memory Usage
**Error**: `Fatal error: Allowed memory size exhausted`

**Solutions**:
1. Increase memory_limit in php.ini
2. Optimize code to use less memory
3. Check for memory leaks
4. Use pagination for large datasets

### 8. Security Issues

#### File Upload Vulnerabilities
**Error**: Malicious files being uploaded

**Solutions**:
1. Verify file type validation
2. Check file size limits
3. Scan uploaded files
4. Store uploads outside web root

#### SQL Injection Attempts
**Error**: Suspicious database queries in logs

**Solutions**:
1. Use prepared statements everywhere
2. Validate all input data
3. Check security logs regularly
4. Update to latest PHP version

### 9. Error Logging and Debugging

#### No Error Logs
**Problem**: Errors not being logged

**Solutions**:
1. Check PHP error_log configuration
2. Verify log directory permissions
3. Enable error logging: `ini_set('log_errors', 1)`
4. Run: `php setup-logging.php`

#### Debug Information Not Showing
**Problem**: Need more detailed error information

**Solutions**:
1. Enable display_errors in development:
   ```php
   ini_set('display_errors', 1);
   ini_set('error_reporting', E_ALL);
   ```
2. Check application logs in `logs/application.log`
3. Enable debug mode in configuration
4. Use error_log() for custom debugging

### 10. Cron Job Issues

#### Cron Jobs Not Running
**Problem**: Automated tasks not executing

**Solutions**:
1. **Windows Task Scheduler**
   - Create scheduled task for PHP scripts
   - Set correct working directory
   - Use full path to PHP executable

2. **Manual Testing**
   - Test scripts manually: `php cron/error-handling-monitor.php`
   - Check script permissions
   - Verify script paths

3. **Logging**
   - Check cron job logs
   - Add logging to cron scripts
   - Monitor execution times

## Getting Help

### Log Files to Check
1. `logs/error.log` - Application errors
2. `logs/php_errors.log` - PHP errors
3. `logs/security.log` - Security events
4. `logs/payment.log` - Payment issues
5. `C:\xampp\apache\logs\error.log` - Apache errors
6. `C:\xampp\mysql\data\*.err` - MySQL errors

### Diagnostic Commands
```bash
# Test database connection
php test-connection.php

# Test email configuration
php test-email-config.php

# Check system status
php -v
mysql --version

# Check PHP extensions
php -m

# Test file permissions
php setup-storage.php
```

### Contact Support
- Create GitHub issue with:
  - Error message
  - Steps to reproduce
  - System information
  - Relevant log entries

### Emergency Recovery
1. **Database Backup**
   - Export database from phpMyAdmin
   - Keep regular backups

2. **File Backup**
   - Backup entire project directory
   - Include configuration files

3. **Clean Installation**
   - Fresh XAMPP installation
   - Restore from backup
   - Reconfigure services

---

**Remember**: Always backup your data before making significant changes!