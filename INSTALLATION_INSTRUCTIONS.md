# Quick Installation Instructions

## Prerequisites
- XAMPP 8.1+ installed on Windows
- Git installed

## Quick Setup (5 minutes)

### 1. Clone and Setup
```bash
cd C:\xampp\htdocs
git clone https://github.com/your-repo/Multi-Vendor-Rental-System.git
cd Multi-Vendor-Rental-System
```

### 2. Configure XAMPP
- Start XAMPP Control Panel
- Start Apache and MySQL services
- Configure Apache to use port 8081 (see DEPLOYMENT_GUIDE.md)

### 3. Database Setup
```bash
php setup-database.php
php seed-data.php
```

### 4. Storage and Logging
```bash
php setup-storage.php
php setup-logging.php
```

### 5. Create Admin User
```bash
php create-admin.php
```

### 6. Test Installation
- Open browser: `http://localhost:8081`
- Admin login: `http://localhost:8081/admin/login.php`

## Configuration Files to Update

### Email (config/email.php)
```php
'username' => 'your-email@gmail.com',
'password' => 'your-app-password',
```

### Test Email
```bash
php test-email-config.php
```

## Default Login Credentials
- Admin: admin@rentalhub.com / admin123
- Customer: customer@test.com / customer123
- Vendor: vendor@test.com / vendor123

## Troubleshooting
- Check logs in `logs/` directory
- Verify XAMPP services are running
- Ensure port 8081 is available
- Check file permissions on storage directories

For detailed instructions, see DEPLOYMENT_GUIDE.md