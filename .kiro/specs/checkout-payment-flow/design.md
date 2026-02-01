# Checkout and Payment Flow - Design

## Architecture Overview

The checkout and payment flow follows a sequential pipeline:

```
Cart → Checkout Page → Razorpay Payment → Payment Verification → Order Creation → Invoice Generation → Success Page
```

### Key Components

1. **Checkout Page** (`public/customer/checkout.php`)
   - Displays cart summary
   - Validates cart before payment
   - Integrates Razorpay payment button
   - Handles payment callbacks

2. **Payment API** (`public/api/payment.php`)
   - Creates Razorpay payment orders
   - Verifies payment signatures
   - Triggers order creation after verification

3. **Order Creation Service** (existing `OrderService`)
   - Creates orders from cart items
   - Splits orders by vendor
   - Creates inventory locks
   - Clears cart after success

4. **Invoice Generation Service** (existing `InvoiceService`)
   - Generates invoices for each order
   - Links invoices to payments and orders
   - Applies vendor branding

5. **Success/Failure Pages**
   - `public/customer/payment-success.php`
   - `public/customer/payment-failure.php`

## Data Flow

### 1. Checkout Initiation

```
Customer clicks "Proceed to Checkout"
  ↓
System validates cart (CartService::validateForCheckout)
  ↓
If valid: Redirect to checkout.php
If invalid: Show errors
```

### 2. Payment Order Creation

```
Customer clicks "Pay Now" on checkout page
  ↓
Frontend calls POST /api/payment.php?action=create_order
  ↓
Backend creates Razorpay order (RazorpayService::createPaymentOrder)
  ↓
Backend saves Payment record with status='created'
  ↓
Backend returns razorpay_order_id, amount, key_id to frontend
  ↓
Frontend opens Razorpay payment modal
```

### 3. Payment Processing

```
Customer completes payment in Razorpay modal
  ↓
Razorpay returns payment_id, order_id, signature
  ↓
Frontend calls POST /api/payment.php?action=verify_payment
  ↓
Backend verifies signature (RazorpayService::verifyPaymentSignature)
  ↓
If valid: Continue to order creation
If invalid: Return error, no orders created
```

### 4. Order Creation

```
Payment verified successfully
  ↓
Backend calls OrderService::createOrdersFromCart
  ↓
For each vendor in cart:
  - Create Order record
  - Create OrderItem records
  - Create InventoryLock records
  - Set order status (Pending_Vendor_Approval or Auto_Approved)
  ↓
All orders created successfully
  ↓
Clear customer's cart
```

### 5. Invoice Generation

```
Orders created successfully
  ↓
For each order:
  Backend calls InvoiceService::generateInvoice
    ↓
  Create Invoice record
    ↓
  Create InvoiceLineItem records
    - Rental charges
    - Security deposits
    - Service fees
    ↓
  Link invoice to order and payment
    ↓
  Apply vendor branding
```

### 6. Notifications

```
Orders and invoices created
  ↓
Send notifications:
  - Customer: Payment success email
  - Customer: Order confirmation emails (one per vendor)
  - Vendors: New order notification emails
  - Admin: New orders notification
```

### 7. Success Response

```
All processing complete
  ↓
Backend returns success response with order IDs
  ↓
Frontend redirects to payment-success.php?orders=id1,id2,id3
  ↓
Success page displays order confirmations
```

## Database Schema

### Existing Tables Used

**payments** (already exists)
- id (PK)
- razorpay_order_id
- razorpay_payment_id
- razorpay_signature
- amount
- currency
- customer_id
- status (created, captured, failed)
- verified_at
- created_at

**orders** (already exists)
- id (PK)
- customer_id
- vendor_id
- payment_id (FK)
- status
- total_amount
- created_at

**order_items** (already exists)
- id (PK)
- order_id (FK)
- product_id (FK)
- variant_id (FK)
- rental_period_id (FK)
- quantity
- price_per_unit

**invoices** (already exists)
- id (PK)
- order_id (FK)
- payment_id (FK)
- invoice_number
- total_amount
- status (draft, finalized)
- created_at

**invoice_line_items** (already exists)
- id (PK)
- invoice_id (FK)
- description
- quantity
- unit_price
- total_price
- item_type (rental, deposit, fee)

## API Endpoints

### POST /api/payment.php?action=create_order

**Request:**
```json
{
  "customer_id": "uuid"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "razorpay_order_id": "order_xxx",
    "amount": 10000,
    "currency": "INR",
    "key_id": "rzp_test_xxx",
    "payment_id": "uuid"
  }
}
```

### POST /api/payment.php?action=verify_payment

**Request:**
```json
{
  "razorpay_order_id": "order_xxx",
  "razorpay_payment_id": "pay_xxx",
  "razorpay_signature": "signature_xxx"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "order_id": "uuid",
        "vendor_name": "Vendor A",
        "amount": 5000,
        "status": "Pending_Vendor_Approval"
      },
      {
        "order_id": "uuid",
        "vendor_name": "Vendor B",
        "amount": 5000,
        "status": "Auto_Approved"
      }
    ],
    "total_amount": 10000
  }
}
```

## Frontend Integration

### Razorpay Checkout Integration

```javascript
// Load Razorpay script
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

// Create payment order
async function initiatePayment() {
  const response = await fetch('/api/payment.php?action=create_order', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ customer_id: customerId })
  });
  
  const result = await response.json();
  
  if (result.success) {
    openRazorpayModal(result.data);
  }
}

// Open Razorpay modal
function openRazorpayModal(paymentData) {
  const options = {
    key: paymentData.key_id,
    amount: paymentData.amount,
    currency: paymentData.currency,
    order_id: paymentData.razorpay_order_id,
    name: 'Multi-Vendor Rental Platform',
    description: 'Rental Booking Payment',
    handler: function(response) {
      verifyPayment(response);
    },
    modal: {
      ondismiss: function() {
        // Handle payment cancellation
        window.location.href = '/customer/payment-failure.php?reason=cancelled';
      }
    },
    theme: {
      color: '#3B82F6'
    }
  };
  
  const rzp = new Razorpay(options);
  rzp.open();
}

// Verify payment
async function verifyPayment(response) {
  const result = await fetch('/api/payment.php?action=verify_payment', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      razorpay_order_id: response.razorpay_order_id,
      razorpay_payment_id: response.razorpay_payment_id,
      razorpay_signature: response.razorpay_signature
    })
  });
  
  const data = await result.json();
  
  if (data.success) {
    const orderIds = data.data.orders.map(o => o.order_id).join(',');
    window.location.href = `/customer/payment-success.php?orders=${orderIds}`;
  } else {
    window.location.href = '/customer/payment-failure.php?reason=verification_failed';
  }
}
```

## Error Handling

### Payment Verification Failure
- Payment signature doesn't match
- Action: Mark payment as failed, don't create orders
- Response: Error message to customer
- Customer can retry payment

### Order Creation Failure
- Database error during order creation
- Action: Rollback transaction, initiate refund
- Response: Error message, refund initiated
- Customer notified via email

### Invoice Generation Failure
- Error creating invoice
- Action: Log error, orders still created
- Response: Success with warning
- Invoice can be regenerated later

### Inventory Conflict
- Product no longer available for selected dates
- Action: Detected during order creation
- Response: Error message, refund initiated
- Customer must modify cart and retry

## Security Considerations

### Payment Signature Verification
- All payment signatures verified on backend
- Use hash_equals() for timing-safe comparison
- Never trust frontend payment data

### CSRF Protection
- All payment forms include CSRF tokens
- Tokens validated on backend

### Session Security
- Customer must be authenticated
- Session validated before payment
- Session regenerated after payment

### Data Validation
- All amounts validated against cart
- Cart validated before payment
- Inventory checked before order creation

## Testing Strategy

### Unit Tests
- Payment signature verification
- Order creation from cart
- Invoice generation
- Vendor order splitting

### Integration Tests
- Complete checkout flow
- Payment verification → Order creation
- Order creation → Invoice generation
- Multi-vendor order splitting

### Property-Based Tests
- **Property 16**: Payment Intent Creation
- **Property 17**: Payment Verification Completeness
- **Property 18**: No Orders Without Verified Payment
- **Property 19**: Vendor-Wise Order Splitting
- **Property 35**: One Invoice Per Order
- **Property 37**: Invoice-Order-Payment Linkage

### Manual Testing
- Test with Razorpay test cards
- Test payment success flow
- Test payment failure flow
- Test multi-vendor checkout
- Test inventory conflicts
- Test notification delivery

## Rollback Strategy

If order creation fails after payment:
1. Mark payment as refund_pending
2. Create refund record
3. Initiate Razorpay refund
4. Send notification to customer
5. Log error for admin review

## Performance Considerations

- Cart validation cached for 30 seconds
- Payment verification timeout: 30 seconds
- Order creation timeout: 60 seconds
- Invoice generation async (doesn't block response)
- Notifications sent async (doesn't block response)

## Monitoring and Logging

### Metrics to Track
- Payment success rate
- Payment verification time
- Order creation time
- Invoice generation time
- Refund rate
- Error rate by type

### Logs to Capture
- All payment attempts
- Payment verification results
- Order creation events
- Invoice generation events
- Refund initiations
- All errors with stack traces

## Future Enhancements

- Webhook handler for Razorpay events
- Retry mechanism for failed notifications
- Partial refunds for order modifications
- Payment analytics dashboard
- Fraud detection integration
- Multiple payment methods
