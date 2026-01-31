# 🚀 Quick Start Guide - Multi-Vendor Rental Platform

## Step 1: Seed Demo Data

Choose one method:

### Option A: Command Line (Recommended)
```bash
php database/seed-demo-data.php
```

### Option B: Web Browser
Open: `http://localhost:8081/Multi-Vendor-Rental-System/seed-data.php`

## Step 2: Login

**URL**: http://localhost:8081/Multi-Vendor-Rental-System/public/login.php

**Password for all accounts**: `password123`

## 🏪 Vendor Accounts

| Username | Business Name | Category |
|----------|--------------|----------|
| `houserentals` | Premium House Rentals | Real Estate |
| `soundwave` | SoundWave Audio Rentals | Music Systems |
| `driveaway` | DriveAway Car Rentals | Vehicles |
| `furnishpro` | FurnishPro Rentals | Furniture |
| `techrent` | TechRent Computer Solutions | Computers |

## 👤 Customer Accounts

| Username | Name |
|----------|------|
| `john_doe` | John Doe |
| `jane_smith` | Jane Smith |

## 📊 What's Included

- ✅ 5 Vendors with complete business profiles
- ✅ 23 Products across 5 categories
- ✅ 2 Customer accounts
- ✅ High-quality product images
- ✅ Detailed product descriptions

## 🎯 Quick Test Scenarios

### As a Vendor:
1. Login with any vendor account
2. View your dashboard with statistics
3. Manage your products
4. Add new products
5. Edit existing products

### As a Customer:
1. Login with a customer account
2. Browse products (coming soon)
3. Add to cart (coming soon)
4. Place orders (coming soon)

## 📁 Important Files

- **Credentials**: `database/DEMO_CREDENTIALS.md` (generated after seeding)
- **Seeding Instructions**: `database/SEEDING_INSTRUCTIONS.md`
- **This Guide**: `QUICK_START.md`

## 🔧 Troubleshooting

### Can't login?
- Ensure you've run the seeder
- Check password is exactly: `password123`
- Verify database connection

### No products showing?
- Run the seeder again
- Check database has data in `products` table

### Seeder fails?
- Check database connection in `config/database.php`
- Ensure migrations are run: `php migrate.php`
- Check PHP error logs

## 🌐 Important URLs

| Page | URL |
|------|-----|
| Landing Page | http://localhost:8081/Multi-Vendor-Rental-System/public/ |
| Login | http://localhost:8081/Multi-Vendor-Rental-System/public/login.php |
| Register | http://localhost:8081/Multi-Vendor-Rental-System/public/register.php |
| Vendor Dashboard | http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/dashboard.php |
| Customer Dashboard | http://localhost:8081/Multi-Vendor-Rental-System/public/customer/dashboard.php |

## 💡 Tips

1. **First Time Setup**: Always run the seeder after setting up the database
2. **Testing**: Use different vendor accounts to see different product catalogs
3. **Development**: Check `database/DEMO_CREDENTIALS.md` for complete details
4. **Re-seeding**: Clear database tables before running seeder again

## 🎉 You're Ready!

Start exploring the platform by logging in as any vendor or customer account.

---

**Need Help?** Check the detailed documentation in `database/SEEDING_INSTRUCTIONS.md`
