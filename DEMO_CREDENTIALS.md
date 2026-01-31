# Demo Login Credentials

## Vendor Accounts

All vendor passwords: `vendor123`

| Business Name | Email | Category |
|--------------|-------|----------|
| TechRent Pro | techrentpro@vendor.com | Electronics |
| Furniture Hub | furniturehub@vendor.com | Furniture |
| ToolMaster Rentals | toolmasterrentals@vendor.com | Tools |
| DriveEasy | driveeasy@vendor.com | Vehicles |
| PhotoPro Gear | photoprogear@vendor.com | Photography |
| EventMagic | eventmagic@vendor.com | Events |
| SportZone | sportzone@vendor.com | Sports |
| MusicBox Rentals | musicboxrentals@vendor.com | Music |
| CampMasters | campmasters@vendor.com | Camping |
| BuildPro Equipment | buildproequipment@vendor.com | Construction |

## Customer Accounts

All customer passwords: `password123`

| Name | Email |
|------|-------|
| Rahul Sharma | rahul.sharma@example.com |
| Priya Patel | priya.patel@example.com |
| Amit Kumar | amit.kumar@example.com |
| Sneha Reddy | sneha.reddy@example.com |
| Vikram Singh | vikram.singh@example.com |
| Anjali Gupta | anjali.gupta@example.com |
| Rohan Mehta | rohan.mehta@example.com |
| Kavya Iyer | kavya.iyer@example.com |
| Arjun Nair | arjun.nair@example.com |
| Pooja Desai | pooja.desai@example.com |
| Karan Malhotra | karan.malhotra@example.com |
| Divya Krishnan | divya.krishnan@example.com |
| Siddharth Joshi | siddharth.joshi@example.com |
| Neha Agarwal | neha.agarwal@example.com |
| Aditya Rao | aditya.rao@example.com |
| Riya Kapoor | riya.kapoor@example.com |
| Varun Chopra | varun.chopra@example.com |
| Ishita Bansal | ishita.bansal@example.com |
| Nikhil Verma | nikhil.verma@example.com |
| Shreya Saxena | shreya.saxena@example.com |
| Manish Pandey | manish.pandey@example.com |
| Tanvi Shah | tanvi.shah@example.com |
| Harsh Sinha | harsh.sinha@example.com |
| Meera Kulkarni | meera.kulkarni@example.com |
| Gaurav Bhatia | gaurav.bhatia@example.com |

## Admin Account

If you need admin access, you can create one using:
```bash
php create-admin.php
```

## Login URL

http://localhost:8081/Multi-Vendor-Rental-System/public/login.php

## Troubleshooting

If you see "Vendor profile not found":
1. Make sure you're logging in with a vendor email (ends with @vendor.com)
2. Clear your browser cookies/session
3. Try logging out and logging in again
4. Verify the vendor profile exists by running: `php verify-vendors.php`

## Data Summary

- **10 Vendors** with 35 products each (350 total products)
- **25 Customers** with purchase history
- **137 Orders** spanning 3 months
- **200 Rental Periods** with various durations
- All orders include payments, invoices, and line items
