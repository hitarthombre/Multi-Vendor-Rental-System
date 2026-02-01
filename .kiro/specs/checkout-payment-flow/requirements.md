# Checkout and Payment Flow - Requirements

## Overview
Implement the complete checkout and payment flow that connects the shopping cart to the Razorpay payment gateway, creates orders after successful payment, and generates invoices. This builds on the existing cart, payment, order, and invoice infrastructure.

## User Stories

### US-1: Customer Checkout Flow
**As a** customer  
**I want to** proceed from my cart to payment  
**So that** I can complete my rental booking

**Acceptance Criteria:**
1.1. Customer can click "Proceed to Checkout" from cart page  
1.2. Checkout page displays cart summary with all items grouped by vendor  
1.3. Checkout page shows total amount including all charges  
1.4. Checkout page displays rental periods for each item  
1.5. Customer can review and confirm order details before payment  
1.6. System validates cart before allowing checkout (availability, pricing)  
1.7. If validation fails, customer sees clear error messages  

### US-2: Payment Processing
**As a** customer  
**I want to** pay securely using Razorpay  
**So that** I can confirm my rental booking

**Acceptance Criteria:**
2.1. Checkout page integrates Razorpay payment button  
2.2. Clicking "Pay Now" opens Razorpay payment modal  
2.3. Payment modal shows correct amount in INR (₹)  
2.4. Customer can pay using cards, UPI, netbanking, wallets  
2.5. Payment is processed securely through Razorpay  
2.6. System verifies payment signature on backend  
2.7. Failed payments do not create orders  
2.8. Payment verification happens before order creation  

### US-3: Order Creation After Payment
**As a** system  
**I want to** create orders only after verified payment  
**So that** no orders exist without payment

**Acceptance Criteria:**
3.1. Orders are created only after payment verification succeeds  
3.2. Cart items are split into separate orders per vendor  
3.3. Each order gets a unique order ID  
3.4. Order status is set based on vendor verification requirements  
3.5. Inventory locks are created for each order item  
3.6. Cart is cleared after successful order creation  
3.7. If order creation fails, payment is refunded automatically  

### US-4: Invoice Generation
**As a** system  
**I want to** generate invoices for each order  
**So that** customers and vendors have financial records

**Acceptance Criteria:**
4.1. Invoice is generated for each order after payment  
4.2. Invoice includes all line items (rental charges, deposits, fees)  
4.3. Invoice is linked to payment and order  
4.4. Invoice is immutable after creation  
4.5. Invoice includes vendor branding (logo, colors)  
4.6. Invoice can be downloaded as PDF  
4.7. Invoice number is unique and sequential  

### US-5: Payment Success Handling
**As a** customer  
**I want to** see confirmation after successful payment  
**So that** I know my booking is confirmed

**Acceptance Criteria:**
5.1. Customer is redirected to success page after payment  
5.2. Success page shows order confirmation details  
5.3. Success page displays order numbers for all created orders  
5.4. Success page shows next steps (vendor approval, document upload)  
5.5. Customer receives email confirmation with order details  
5.6. Success page has links to view orders in dashboard  

### US-6: Payment Failure Handling
**As a** customer  
**I want to** be informed if payment fails  
**So that** I can retry or use a different payment method

**Acceptance Criteria:**
6.1. Customer is redirected to failure page if payment fails  
6.2. Failure page shows clear error message  
6.3. Failure page preserves cart contents  
6.4. Customer can retry payment from failure page  
6.5. Failed payment attempts are logged  
6.6. No orders or inventory locks are created on failure  

### US-7: Multi-Vendor Order Splitting
**As a** system  
**I want to** split cart into separate orders per vendor  
**So that** each vendor manages their own orders independently

**Acceptance Criteria:**
7.1. Cart items are grouped by vendor during checkout  
7.2. One order is created per vendor  
7.3. Each order has its own invoice  
7.4. Payment is split proportionally across vendors  
7.5. All vendor orders are created in a single transaction  
7.6. If any order creation fails, all are rolled back  

### US-8: Notification System Integration
**As a** system  
**I want to** send notifications after payment and order creation  
**So that** all parties are informed

**Acceptance Criteria:**
8.1. Customer receives payment success email  
8.2. Customer receives order confirmation email for each order  
8.3. Vendors receive new order notification emails  
8.4. Admins receive notification of new orders  
8.5. Notifications include relevant order details and links  
8.6. Failed notifications are logged and retried  

## Non-Functional Requirements

### NFR-1: Security
- All payment data must be handled securely through Razorpay
- Payment signatures must be verified on backend
- No sensitive payment data stored in database
- CSRF protection on all payment forms
- Secure session management during checkout

### NFR-2: Performance
- Checkout page loads in under 2 seconds
- Payment verification completes in under 5 seconds
- Order creation completes in under 10 seconds
- Invoice generation completes in under 3 seconds

### NFR-3: Reliability
- Payment verification must be idempotent
- Order creation must be atomic (all or nothing)
- Failed payments must not create orders
- System must handle Razorpay webhook failures gracefully

### NFR-4: Usability
- Checkout flow is intuitive and requires minimal clicks
- Error messages are clear and actionable
- Payment modal is mobile-responsive
- Success/failure pages provide clear next steps

## Technical Constraints

- Backend: PHP 8.2+ on XAMPP
- Database: MySQL 8.0+
- Payment Gateway: Razorpay (test mode)
- Frontend: HTML/CSS/JavaScript with Tailwind CSS
- Currency: Indian Rupees (INR) only
- Email: SMTP for notifications

## Dependencies

This feature depends on:
- Existing Cart system (Task 8)
- Existing Payment models and RazorpayService (Task 10)
- Existing Order models and OrderService (Task 12)
- Existing Invoice models and InvoiceService (Task 17)
- Existing Notification system (Task 20)
- Existing Inventory management (Task 9)

## Out of Scope

- Guest checkout (users must be logged in)
- Multiple payment methods beyond Razorpay
- Partial payments or payment plans
- Discount codes or coupons
- Gift cards or store credit
- International payments (INR only)
