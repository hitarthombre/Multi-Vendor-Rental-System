# Checkout and Payment Flow - Implementation Tasks

## Overview
This task list implements the complete checkout and payment flow, building on existing cart, payment, order, and invoice infrastructure.

## Tasks

- [x] 1. Payment API Endpoint
  - [x] 1.1 Create payment API file
    - Create `public/api/payment.php`
    - Implement action routing (create_order, verify_payment)
    - Add authentication check
    - Add error handling
    - **Validates: Requirements 2.1, 2.6**
  
  - [x] 1.2 Implement create_order action
    - Get customer's cart
    - Calculate total amount
    - Call RazorpayService::createPaymentOrder
    - Return razorpay_order_id, amount, key_id
    - **Validates: Requirements 2.1, 2.2, 2.3**
  
  - [x] 1.3 Implement verify_payment action
    - Receive razorpay_order_id, razorpay_payment_id, razorpay_signature
    - Call RazorpayService::verifyAndCapturePayment
    - If verified: Trigger order creation
    - If failed: Return error, no orders created
    - **Validates: Requirements 2.6, 2.7, 2.8, 3.1**
  
  - [x] 1.4 Integrate order creation after payment
    - Call OrderService::createOrdersFromCart
    - Handle multi-vendor order splitting
    - Create inventory locks
    - Clear cart after success
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
  
  - [x] 1.5 Integrate invoice generation
    - Call InvoiceService::generateInvoice for each order
    - Link invoices to orders and payment
    - Apply vendor branding
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**
  
  - [x] 1.6 Implement error handling and rollback
    - If order creation fails: Initiate refund
    - If invoice generation fails: Log error, continue
    - Return appropriate error responses
    - **Validates: Requirements 3.7, NFR-3**

- [x] 2. Checkout Page (Frontend)
  - [x] 2.1 Create checkout page file
    - Create `public/customer/checkout.php`
    - Add authentication check
    - Add modern layout with Tailwind CSS
    - **Validates: Requirements 1.1**
  
  - [x] 2.2 Implement cart summary display
    - Fetch cart contents via API
    - Display items grouped by vendor
    - Show rental periods for each item
    - Display pricing breakdown
    - Show total amount
    - **Validates: Requirements 1.2, 1.3, 1.4**
  
  - [x] 2.3 Implement cart validation
    - Call CartService::validateForCheckout
    - Display validation errors if any
    - Disable payment button if invalid
    - **Validates: Requirements 1.6, 1.7**
  
  - [x] 2.4 Integrate Razorpay payment button
    - Load Razorpay checkout script
    - Add "Pay Now" button
    - Style button with Tailwind CSS
    - **Validates: Requirements 2.1, 2.2**
  
  - [x] 2.5 Implement payment initiation
    - Call create_order API on button click
    - Open Razorpay modal with payment details
    - Configure modal options (amount, currency, theme)
    - **Validates: Requirements 2.2, 2.3, 2.4, 2.5**
  
  - [x] 2.6 Implement payment callback handling
    - Handle successful payment response
    - Call verify_payment API
    - Redirect to success page with order IDs
    - Handle payment cancellation
    - Redirect to failure page on error
    - **Validates: Requirements 2.6, 5.1, 6.1**
  
  - [x] 2.7 Add loading states and UX
    - Show loading spinner during API calls
    - Disable buttons during processing
    - Show progress indicators
    - **Validates: NFR-4**

- [x] 3. Payment Success Page
  - [x] 3.1 Create success page file
    - Create `public/customer/payment-success.php`
    - Add authentication check
    - Add modern layout with Tailwind CSS
    - **Validates: Requirements 5.1**
  
  - [x] 3.2 Display order confirmations
    - Parse order IDs from URL
    - Fetch order details via API
    - Display order numbers and statuses
    - Show vendor names
    - Display total amounts
    - **Validates: Requirements 5.2, 5.3**
  
  - [x] 3.3 Display next steps
    - Show vendor approval status
    - Show document upload requirements
    - Provide links to order details
    - Add link to customer dashboard
    - **Validates: Requirements 5.4, 5.6**
  
  - [x] 3.4 Add download invoice buttons
    - Add button for each order's invoice
    - Link to invoice download API
    - **Validates: Requirements 4.6**

- [ ] 4. Payment Failure Page
  - [ ] 4.1 Create failure page file
    - Create `public/customer/payment-failure.php`
    - Add authentication check
    - Add modern layout with Tailwind CSS
    - **Validates: Requirements 6.1**
  
  - [ ] 4.2 Display error information
    - Parse failure reason from URL
    - Display user-friendly error message
    - Show what went wrong
    - **Validates: Requirements 6.2**
  
  - [ ] 4.3 Provide retry options
    - Add "Return to Cart" button
    - Add "Try Again" button (redirects to checkout)
    - Ensure cart is preserved
    - **Validates: Requirements 6.3, 6.4**
  
  - [ ] 4.4 Add support information
    - Display support contact details
    - Add link to help/FAQ
    - **Validates: NFR-4**

- [x] 5. Update Cart Page
  - [x] 5.1 Update checkout button
    - Remove placeholder alert
    - Link to new checkout page
    - Add cart validation before redirect
    - **Validates: Requirements 1.1**
  
  - [x] 5.2 Add checkout validation
    - Validate cart before allowing checkout
    - Show validation errors inline
    - Disable checkout button if invalid
    - **Validates: Requirements 1.6, 1.7**

- [ ] 6. Notification Integration
  - [ ] 6.1 Implement payment success notifications
    - Send email to customer after payment
    - Include payment details and amount
    - **Validates: Requirements 8.1**
  
  - [ ] 6.2 Implement order confirmation notifications
    - Send email to customer for each order
    - Include order number, vendor, items, total
    - Add link to order details
    - **Validates: Requirements 8.2**
  
  - [ ] 6.3 Implement vendor notifications
    - Send email to vendor for new orders
    - Include order details and customer info
    - Add link to approval queue
    - **Validates: Requirements 8.3**
  
  - [ ] 6.4 Implement admin notifications
    - Send email to admin for new orders
    - Include summary of all orders
    - **Validates: Requirements 8.4**
  
  - [ ] 6.5 Add notification error handling
    - Log failed notifications
    - Implement retry mechanism
    - **Validates: Requirements 8.6, NFR-3**

- [ ] 7. OrderService Enhancements
  - [ ] 7.1 Implement createOrdersFromCart method
    - Accept customer_id and payment_id
    - Fetch cart items
    - Group items by vendor
    - Create one order per vendor
    - Create order items for each cart item
    - Set order status based on verification requirements
    - Create inventory locks
    - Clear cart after success
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 7.1, 7.2, 7.5**
  
  - [ ] 7.2 Implement transaction handling
    - Wrap order creation in database transaction
    - Rollback on any error
    - **Validates: Requirements 7.6, NFR-3**
  
  - [ ] 7.3 Add error handling
    - Handle inventory conflicts
    - Handle database errors
    - Return detailed error information
    - **Validates: Requirements 3.7**

- [ ] 8. Testing
  - [ ] 8.1 Test payment order creation
    - Test with valid cart
    - Test with empty cart
    - Test with invalid customer
    - Verify payment record created
    - **Validates: Requirements 2.1**
  
  - [ ] 8.2 Test payment verification
    - Test with valid signature
    - Test with invalid signature
    - Test with non-existent order
    - Verify orders created only on success
    - **Validates: Requirements 2.6, 2.7, 3.1**
  
  - [ ] 8.3 Test multi-vendor order splitting
    - Create cart with items from multiple vendors
    - Complete payment
    - Verify separate orders created per vendor
    - Verify each order has correct items
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
  
  - [ ] 8.4 Test invoice generation
    - Verify invoice created for each order
    - Verify invoice linked to payment and order
    - Verify line items correct
    - Verify vendor branding applied
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.5**
  
  - [ ] 8.5 Test error scenarios
    - Test payment failure
    - Test order creation failure
    - Test inventory conflict
    - Verify refunds initiated
    - Verify no orders created on failure
    - **Validates: Requirements 2.7, 3.7, 6.6**
  
  - [ ] 8.6 Test notification delivery
    - Verify customer receives emails
    - Verify vendors receive emails
    - Verify admin receives emails
    - Test notification retry on failure
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.6**
  
  - [ ] 8.7 Test complete checkout flow
    - Add items to cart
    - Proceed to checkout
    - Complete payment with test card
    - Verify orders created
    - Verify invoices generated
    - Verify cart cleared
    - Verify notifications sent
    - Verify success page displays correctly
    - **Validates: All requirements**

- [ ] 9. Documentation
  - [ ] 9.1 Update user documentation
    - Document checkout process
    - Document payment methods
    - Add screenshots
    - **Validates: NFR-4**
  
  - [ ] 9.2 Update developer documentation
    - Document API endpoints
    - Document payment flow
    - Add code examples
    - **Validates: NFR-4**
  
  - [ ] 9.3 Create troubleshooting guide
    - Common payment errors
    - How to handle refunds
    - How to retry failed orders
    - **Validates: NFR-4**

## Testing Checklist

### Manual Testing
- [ ] Test with Razorpay test card: 4111 1111 1111 1111
- [ ] Test payment success flow
- [ ] Test payment failure (use invalid card)
- [ ] Test payment cancellation (close modal)
- [ ] Test with single vendor cart
- [ ] Test with multi-vendor cart
- [ ] Test with empty cart
- [ ] Test with invalid cart items
- [ ] Test inventory conflict scenario
- [ ] Verify email notifications received
- [ ] Verify invoices downloadable
- [ ] Test on mobile devices
- [ ] Test with slow network

### Razorpay Test Cards
- Success: 4111 1111 1111 1111
- Failure: 4000 0000 0000 0002
- 3D Secure: 5104 0600 0000 0008

### Expected Behavior
- ✅ Payment success → Orders created → Invoices generated → Cart cleared → Notifications sent
- ✅ Payment failure → No orders → Cart preserved → Error shown
- ✅ Payment cancelled → No orders → Cart preserved → Redirect to failure page
- ✅ Inventory conflict → Refund initiated → Error shown → Cart preserved

## Dependencies

This implementation depends on:
- ✅ CartService (Task 8) - Already implemented
- ✅ RazorpayService (Task 10) - Already implemented
- ✅ OrderService (Task 12) - Needs createOrdersFromCart method
- ✅ InvoiceService (Task 17) - Already implemented
- ✅ NotificationService (Task 20) - Already implemented
- ✅ InventoryLockRepository (Task 9) - Already implemented

## Notes

- Use Razorpay test mode credentials
- All amounts in paise (multiply by 100 for Razorpay)
- Currency is INR only
- Customer must be authenticated
- Cart must be validated before payment
- Payment verification must happen on backend
- Orders created only after verified payment
- Invoices generated after orders
- Notifications sent asynchronously
- Failed payments do not create orders
- Order creation failures trigger refunds
