# Razorpay 401 Unauthorized Error - Solution Guide

## Issue
The checkout page shows a Razorpay 401 Unauthorized error when clicking the Pay Now button. This means the Razorpay API keys are invalid or expired.

## Error Details
```
POST https://api.razorpay.com/v2/standard_checkout/preferences 401 (Unauthorized)
```

## Root Cause
The test API keys in `config/razorpay.php` are either:
- Expired or deactivated
- Invalid or incorrect
- From a deactivated Razorpay test account

## Solutions

### Solution 1: Get Valid Razorpay Test Keys (Recommended)

#### Step 1: Access Razorpay Dashboard
1. Go to https://dashboard.razorpay.com/
2. Sign in with your Razorpay account
3. If you don't have an account, sign up for free

#### Step 2: Switch to Test Mode
1. Look for the mode toggle in the top-right corner
2. Make sure it says **"Test Mode"** (not Live Mode)
3. Test mode allows you to test payments without real money

#### Step 3: Get API Keys
1. Go to **Settings** → **API Keys**
2. You'll see your Test Key ID and Key Secret
3. If no keys exist, click **"Generate Test Keys"**
4. Copy both:
   - **Key ID** (starts with `rzp_test_`)
   - **Key Secret** (long alphanumeric string)

#### Step 4: Update Your Project
Run the update script with your new keys:
```bash
php update-razorpay-keys.php YOUR_KEY_ID YOUR_KEY_SECRET
```

Example:
```bash
php update-razorpay-keys.php rzp_test_ABC123XYZ789 your_secret_key_here
```

#### Step 5: Test Payment
1. Refresh the checkout page
2. Click the Pay Now button
3. Use Razorpay test card details:
   - **Card Number**: 4111 1111 1111 1111
   - **CVV**: Any 3 digits
   - **Expiry**: Any future date

---

### Solution 2: Manual Key Update

If you prefer to update manually:

#### Update config/razorpay.php
```php
'test' => [
    'key_id' => 'YOUR_NEW_KEY_ID',      // Replace this
    'key_secret' => 'YOUR_NEW_SECRET',   // Replace this
    'webhook_secret' => '',
],
```

#### Update rzp-key.csv
```csv
key_id,key_secret
YOUR_NEW_KEY_ID,YOUR_NEW_SECRET
```

---

### Solution 3: Mock Payment Mode (For Testing Only)

If you want to test the checkout flow without valid Razorpay keys, I can create a mock payment mode that simulates successful payments. This is useful for:
- Testing the order creation flow
- Testing the payment success page
- Testing notifications and invoices
- Development without Razorpay account

**Note**: This won't test actual Razorpay integration, just the application flow.

---

## Current Keys Status

**Current Key ID**: `rzp_test_S6DaGQn3cdtVFp`
**Status**: ❌ Invalid/Expired (401 Unauthorized)

## Razorpay Test Card Details

Once you have valid keys, use these test cards:

### Successful Payment
- **Card**: 4111 1111 1111 1111
- **CVV**: 123
- **Expiry**: 12/25
- **OTP**: Any 6 digits

### Failed Payment (for testing)
- **Card**: 4000 0000 0000 0002
- **CVV**: 123
- **Expiry**: 12/25

### More Test Cards
Visit: https://razorpay.com/docs/payments/payments/test-card-details/

---

## Verification Steps

After updating keys:

1. **Check browser console** - Should not show 401 errors
2. **Razorpay modal should open** - Payment form appears
3. **Test payment** - Use test card details
4. **Check payment success page** - Should redirect after payment

---

## Need Help?

If you continue to face issues:
1. Verify you're in Test Mode on Razorpay dashboard
2. Check that keys are copied correctly (no extra spaces)
3. Ensure your Razorpay account is activated
4. Try generating new test keys

---

## Files Involved
- `config/razorpay.php` - Main configuration
- `rzp-key.csv` - Key backup
- `public/api/payment.php` - Payment API
- `src/Services/RazorpayService.php` - Razorpay integration
