# URL Access Guide

## Current Setup

Your project is located at: `C:\xampp\htdocs\Multi-Vendor-Rental-System`

## Option 1: Use Full Path URLs (Current Setup)

Access your application using the full path:

### Main URLs:
- **Homepage**: `http://localhost:8081/Multi-Vendor-Rental-System/public/`
- **Login**: `http://localhost:8081/Multi-Vendor-Rental-System/public/login.php`
- **Register**: `http://localhost:8081/Multi-Vendor-Rental-System/public/register.php`

### Customer URLs:
- **Dashboard**: `http://localhost:8081/Multi-Vendor-Rental-System/public/customer/dashboard.php`

### Vendor URLs:
- **Dashboard**: `http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/dashboard.php`
- **Products**: `http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/products.php`
- **Create Product**: `http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/product-create.php`

### Admin URLs:
- **Dashboard**: `http://localhost:8081/Multi-Vendor-Rental-System/public/admin/dashboard.php`
- **Categories**: `http://localhost:8081/Multi-Vendor-Rental-System/public/admin/categories.php`
- **Audit Logs**: `http://localhost:8081/Multi-Vendor-Rental-System/public/admin/audit-logs.php`

## Option 2: Configure Virtual Host (Recommended for Cleaner URLs)

To use cleaner URLs like `http://rental.local/`, follow these steps:

### Step 1: Edit Apache Configuration

1. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Add this virtual host configuration at the end:

```apache
<VirtualHost *:8081>
    ServerName rental.local
    DocumentRoot "C:/xampp/htdocs/Multi-Vendor-Rental-System/public"
    
    <Directory "C:/xampp/htdocs/Multi-Vendor-Rental-System/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/rental-error.log"
    CustomLog "logs/rental-access.log" common
</VirtualHost>
```

### Step 2: Edit Hosts File

1. Open Notepad as Administrator
2. Open file: `C:\Windows\System32\drivers\etc\hosts`
3. Add this line at the end:

```
127.0.0.1    rental.local
```

### Step 3: Restart Apache

1. Open XAMPP Control Panel
2. Stop Apache
3. Start Apache

### Step 4: Access Your Application

Now you can use clean URLs:
- **Homepage**: `http://rental.local:8081/`
- **Login**: `http://rental.local:8081/login.php`
- **Register**: `http://rental.local:8081/register.php`

## Option 3: Update All Internal Links (Quick Fix)

If you want to keep using the current setup but fix the 404 errors, you need to ensure all internal links in your PHP files use the correct base path.

### Current Issue:
Some files might have links like `/login.php` which resolves to `http://localhost:8081/login.php` (404 error)

### Solution:
All links should be like `/Multi-Vendor-Rental-System/public/login.php` which resolves to the correct path.

## Recommended Approach

For development, I recommend **Option 1** (use full path URLs) as it requires no configuration changes.

For production or cleaner development experience, use **Option 2** (virtual host).

## Quick Test

Try accessing the homepage first:
```
http://localhost:8081/Multi-Vendor-Rental-System/public/
```

If this works, then all other pages should work with the full path.

## Demo Credentials

Once you can access the login page, use these credentials:

### Vendors:
- **Premium House Rentals**: `premium_house` / `password123`
- **SoundWave Audio**: `soundwave_audio` / `password123`
- **DriveAway Cars**: `driveaway_cars` / `password123`
- **FurnishPro**: `furnishpro` / `password123`
- **TechRent**: `techrent` / `password123`

### Customers:
- **John Doe**: `john_doe` / `password123`
- **Jane Smith**: `jane_smith` / `password123`

### Admin:
- **Admin**: `admin` / `admin123`
