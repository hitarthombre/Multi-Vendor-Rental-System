# Design Document: Multi-Vendor Rental Platform

## Overview

The multi-vendor rental platform is a time-based rental management system that enables customers to rent physical products from multiple vendors through a unified marketplace. The system is architected around strict separation of concerns: payment confirms intent, verification confirms trust, and the backend enforces responsibility through structured, rule-driven workflows.

The platform operates on a payment-first model where customers pay before orders are created, but payment alone does not guarantee fulfillment. Conditional approval workflows ensure that high-risk rentals require vendor verification while low-risk rentals can be auto-approved for instant activation. All financial records are vendor-specific and immutable, ensuring legal compliance and audit readiness.

Key architectural principles:
- Backend as single source of truth for all critical decisions
- Vendor-wise order splitting and financial isolation
- Time-based inventory management with strict locking rules
- Immutable invoices and financial records
- Role-based access control enforced at system level
- Graceful failure handling with consistent state recovery

## Architecture

### System Architecture

The platform follows a three-tier architecture:

**Presentation Layer (Frontend)**
- Customer web application (platform-branded)
- Vendor dashboard (vendor-branded)
- Administrator dashboard (platform-branded)
- Displays information and collects user input
- Never makes authoritative decisions
- Reflects backend state changes

**Application Layer (Backend)**
- Single source of truth for all business logic
- Handles authentication and authorization
- Manages rental lifecycle and state transitions
- Performs payment verification
- Enforces inventory locking rules
- Generates invoices and financial records
- Coordinates notifications

**Data Layer**
- MySQL database for persistent storage
- Stores users, products, orders, payments, invoices
- Maintains audit logs
- Supports transactional integrity

### Integration Architecture

**Payment Gateway Integration (Razorpay)**
- Backend creates payment intents
- Frontend presents payment UI
- Backend verifies payment signatures
- Prevents frontend payment manipulation

**Email Service Integration**
- SMTP-based notification delivery
- Configured with provided credentials
- Sends order confirmations, approvals, rejections, refunds

**File Storage**
- Secure document storage for verification uploads
- Access control based on roles
- Supports PDF, JPG, PNG formats

### Deployment Architecture

**XAMPP Environment**
- Apache web server on port 8081
- MySQL database via phpMyAdmin
- PHP backend application
- File system for document storage

## Components and Interfaces

### Authentication and Authorization Module

**Responsibilities:**
- User registration and login
- Session management
- Role-based permission enforcement
- Password hashing and security

**Interfaces:**
- `authenticate(username, password) -> Session`
- `authorize(session, resource, action) -> boolean`
- `createUser(userData, role) -> User`
- `logout(session) -> void`

**Key Rules:**
- All permissions checked at backend level
- Sessions expire after inactivity
- Failed login attempts are logged

### Product Management Module

**Responsibilities:**
- Product CRUD operations
- Attribute and variant management
- Pricing configuration
- Verification requirement settings

**Interfaces:**
- `createProduct(vendorId, productData) -> Product`
- `updateProduct(productId, productData) -> Product`
- `defineVariant(productId, attributeValues) -> Variant`
- `setPricing(productId, durationUnit, price) -> void`
- `setVerificationRequirement(productId, required) -> void`

**Key Rules:**
- Products belong to exactly one vendor
- Variants must have all required attributes
- Pricing must be positive values

### Rental Period and Pricing Module

**Responsibilities:**
- Rental period validation
- Duration calculation
- Price computation based on time
- Discount application

**Interfaces:**
- `validateRentalPeriod(startDateTime, endDateTime) -> boolean`
- `calculateDuration(startDateTime, endDateTime, unit) -> number`
- `calculatePrice(productId, variantId, rentalPeriod) -> Price`
- `applyDiscount(price, couponCode) -> Price`

**Key Rules:**
- End date must be after start date
- Duration must meet minimum requirements
- All pricing calculated by backend

### Inventory Management Module

**Responsibilities:**
- Time-based availability checking
- Inventory locking after payment
- Conflict detection for overlapping rentals
- Inventory release on completion or rejection

**Interfaces:**
- `checkAvailability(productId, variantId, rentalPeriod) -> boolean`
- `lockInventory(orderId, productId, variantId, rentalPeriod) -> void`
- `releaseInventory(orderId) -> void`
- `getAvailableTimeSlots(productId, variantId, dateRange) -> TimeSlot[]`

**Key Rules:**
- Availability evaluated in context of time, not just quantity
- Inventory locked only after payment verification
- No overlapping rentals for same variant
- Locks released on rejection or completion

### Payment Processing Module

**Responsibilities:**
- Payment intent creation
- Razorpay integration
- Payment signature verification
- Refund processing

**Interfaces:**
- `createPaymentIntent(amount, currency, metadata) -> PaymentIntent`
- `verifyPayment(paymentId, signature, intentId) -> boolean`
- `processRefund(paymentId, amount, reason) -> Refund`
- `getPaymentStatus(paymentId) -> PaymentStatus`

**Key Rules:**
- All payments verified on backend
- Frontend success signals not trusted
- Payment intent locks the amount
- Refunds linked to original payments

### Order Management Module

**Responsibilities:**
- Order creation after payment verification
- Vendor-wise order splitting
- Order lifecycle state management
- Status transition validation

**Interfaces:**
- `createOrders(paymentId, cartItems) -> Order[]`
- `splitOrdersByVendor(cartItems) -> Map<VendorId, CartItem[]>`
- `transitionOrderStatus(orderId, newStatus) -> void`
- `getOrdersByCustomer(customerId) -> Order[]`
- `getOrdersByVendor(vendorId) -> Order[]`

**Key Rules:**
- Orders created only after payment verification
- One order per vendor per checkout
- Status transitions follow lifecycle rules
- All transitions logged with timestamp

### Vendor Approval Module

**Responsibilities:**
- Routing orders to approval queue
- Document verification support
- Approval/rejection processing
- Auto-approval for non-verified products

**Interfaces:**
- `routeToApproval(orderId) -> void`
- `approveOrder(orderId, vendorId) -> void`
- `rejectOrder(orderId, vendorId, reason) -> void`
- `autoApproveOrder(orderId) -> void`
- `getPendingApprovals(vendorId) -> Order[]`

**Key Rules:**
- Routing based on verification requirement flag
- Only owning vendor can approve/reject
- Rejection triggers refund
- Approval activates rental

### Document Management Module

**Responsibilities:**
- Document upload handling
- Secure storage
- Access control
- Document retrieval for verification

**Interfaces:**
- `uploadDocument(orderId, customerId, documentType, file) -> Document`
- `getDocuments(orderId, requestorId, requestorRole) -> Document[]`
- `deleteDocument(documentId, requestorId) -> void`

**Key Rules:**
- Documents accessible only to customer, vendor, and admin
- Supported formats: PDF, JPG, PNG
- File size limits enforced
- Secure storage with access logging

### Invoice Generation Module

**Responsibilities:**
- Vendor-specific invoice creation
- Line item calculation
- Tax computation
- Invoice immutability enforcement

**Interfaces:**
- `generateInvoice(orderId) -> Invoice`
- `addLineItem(invoiceId, item, amount) -> void`
- `calculateTax(invoiceId) -> TaxAmount`
- `finalizeInvoice(invoiceId) -> void`
- `getInvoice(invoiceId, requestorId) -> Invoice`

**Key Rules:**
- One invoice per order
- Invoices generated after payment verification
- Finalized invoices are immutable
- Vendor branding applied to invoices
- Service charges as separate line items

### Notification Module

**Responsibilities:**
- Event-driven notification triggering
- Email composition and sending
- Notification logging
- Retry handling for failures

**Interfaces:**
- `sendNotification(userId, eventType, data) -> void`
- `composeEmail(template, data) -> Email`
- `sendEmail(email) -> boolean`
- `getNotificationHistory(userId) -> Notification[]`

**Key Rules:**
- Notifications sent for all major lifecycle events
- Email delivery failures logged
- Retry logic for transient failures
- Notification preferences respected

### Reporting and Analytics Module

**Responsibilities:**
- Role-based report generation
- Data aggregation and filtering
- Export functionality
- Performance metrics calculation

**Interfaces:**
- `generateVendorReport(vendorId, reportType, dateRange) -> Report`
- `generateAdminReport(reportType, dateRange) -> Report`
- `exportReport(reportId, format) -> File`
- `getMetrics(scope, metricType) -> Metrics`

**Key Rules:**
- Reports filtered by role permissions
- Data sourced from verified records
- Vendor isolation maintained in reports
- Export formats: PDF, CSV, Excel

## Data Models

### User

```
User {
  id: UUID (primary key)
  username: string (unique)
  email: string (unique)
  passwordHash: string
  role: enum (Customer, Vendor, Administrator)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### Vendor

```
Vendor {
  id: UUID (primary key)
  userId: UUID (foreign key -> User.id)
  businessName: string
  legalName: string
  taxId: string
  logo: string (file path)
  brandColor: string (hex color)
  contactEmail: string
  contactPhone: string
  status: enum (Active, Suspended, Pending)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### Product

```
Product {
  id: UUID (primary key)
  vendorId: UUID (foreign key -> Vendor.id)
  name: string
  description: text
  categoryId: UUID (foreign key -> Category.id)
  images: string[] (file paths)
  verificationRequired: boolean
  status: enum (Active, Inactive, Deleted)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### Attribute

```
Attribute {
  id: UUID (primary key)
  name: string (e.g., "Color", "Size")
  type: enum (Select, Text, Number)
  createdAt: timestamp
}
```

### AttributeValue

```
AttributeValue {
  id: UUID (primary key)
  attributeId: UUID (foreign key -> Attribute.id)
  value: string (e.g., "Red", "Large")
  createdAt: timestamp
}
```

### Variant

```
Variant {
  id: UUID (primary key)
  productId: UUID (foreign key -> Product.id)
  attributeValues: Map<AttributeId, AttributeValueId>
  sku: string (unique)
  quantity: integer (if applicable)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### Pricing

```
Pricing {
  id: UUID (primary key)
  productId: UUID (foreign key -> Product.id)
  variantId: UUID (nullable, foreign key -> Variant.id)
  durationUnit: enum (Hourly, Daily, Weekly, Monthly)
  pricePerUnit: decimal
  minimumDuration: integer
  createdAt: timestamp
  updatedAt: timestamp
}
```

### RentalPeriod

```
RentalPeriod {
  id: UUID (primary key)
  startDateTime: timestamp
  endDateTime: timestamp
  durationValue: integer
  durationUnit: enum (Hourly, Daily, Weekly, Monthly)
}
```

### Cart

```
Cart {
  id: UUID (primary key)
  customerId: UUID (foreign key -> User.id)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### CartItem

```
CartItem {
  id: UUID (primary key)
  cartId: UUID (foreign key -> Cart.id)
  productId: UUID (foreign key -> Product.id)
  variantId: UUID (nullable, foreign key -> Variant.id)
  rentalPeriodId: UUID (foreign key -> RentalPeriod.id)
  quantity: integer
  tentativePrice: decimal
  createdAt: timestamp
  updatedAt: timestamp
}
```

### Payment

```
Payment {
  id: UUID (primary key)
  customerId: UUID (foreign key -> User.id)
  razorpayPaymentId: string
  razorpayOrderId: string
  razorpaySignature: string
  amount: decimal
  currency: string
  status: enum (Pending, Verified, Failed)
  verifiedAt: timestamp (nullable)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### Order

```
Order {
  id: UUID (primary key)
  orderNumber: string (unique, human-readable)
  customerId: UUID (foreign key -> User.id)
  vendorId: UUID (foreign key -> Vendor.id)
  paymentId: UUID (foreign key -> Payment.id)
  status: enum (Payment_Successful, Pending_Vendor_Approval, Auto_Approved, Active_Rental, Completed, Rejected, Refunded)
  totalAmount: decimal
  depositAmount: decimal
  createdAt: timestamp
  updatedAt: timestamp
}
```

### OrderItem

```
OrderItem {
  id: UUID (primary key)
  orderId: UUID (foreign key -> Order.id)
  productId: UUID (foreign key -> Product.id)
  variantId: UUID (nullable, foreign key -> Variant.id)
  rentalPeriodId: UUID (foreign key -> RentalPeriod.id)
  quantity: integer
  unitPrice: decimal
  totalPrice: decimal
  createdAt: timestamp
}
```

### InventoryLock

```
InventoryLock {
  id: UUID (primary key)
  orderId: UUID (foreign key -> Order.id)
  productId: UUID (foreign key -> Product.id)
  variantId: UUID (nullable, foreign key -> Variant.id)
  rentalPeriodId: UUID (foreign key -> RentalPeriod.id)
  lockedAt: timestamp
  releasedAt: timestamp (nullable)
}
```

### Document

```
Document {
  id: UUID (primary key)
  orderId: UUID (foreign key -> Order.id)
  customerId: UUID (foreign key -> User.id)
  documentType: string (e.g., "ID Proof", "License")
  filePath: string
  fileSize: integer
  mimeType: string
  uploadedAt: timestamp
}
```

### Invoice

```
Invoice {
  id: UUID (primary key)
  invoiceNumber: string (unique, human-readable)
  orderId: UUID (foreign key -> Order.id)
  vendorId: UUID (foreign key -> Vendor.id)
  customerId: UUID (foreign key -> User.id)
  subtotal: decimal
  taxAmount: decimal
  totalAmount: decimal
  status: enum (Draft, Finalized)
  finalizedAt: timestamp (nullable)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### InvoiceLineItem

```
InvoiceLineItem {
  id: UUID (primary key)
  invoiceId: UUID (foreign key -> Invoice.id)
  description: string
  itemType: enum (Rental, Deposit, Delivery, Fee, Penalty)
  quantity: integer
  unitPrice: decimal
  totalPrice: decimal
  taxRate: decimal
  taxAmount: decimal
  createdAt: timestamp
}
```

### Refund

```
Refund {
  id: UUID (primary key)
  orderId: UUID (foreign key -> Order.id)
  paymentId: UUID (foreign key -> Payment.id)
  razorpayRefundId: string
  amount: decimal
  reason: string
  status: enum (Initiated, In_Progress, Completed, Failed)
  initiatedAt: timestamp
  completedAt: timestamp (nullable)
  createdAt: timestamp
  updatedAt: timestamp
}
```

### AuditLog

```
AuditLog {
  id: UUID (primary key)
  userId: UUID (foreign key -> User.id)
  entityType: string (e.g., "Order", "Payment")
  entityId: UUID
  action: string (e.g., "status_change", "approval", "refund")
  oldValue: json (nullable)
  newValue: json (nullable)
  timestamp: timestamp
  ipAddress: string
}
```

### Notification

```
Notification {
  id: UUID (primary key)
  userId: UUID (foreign key -> User.id)
  eventType: string (e.g., "order_created", "order_approved")
  subject: string
  body: text
  sentAt: timestamp (nullable)
  status: enum (Pending, Sent, Failed)
  createdAt: timestamp
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Property Reflection

After analyzing all acceptance criteria, I've identified several areas where properties can be consolidated:

**Authentication and Authorization:**
- Properties 1.2, 1.4, 1.5, 1.6 all relate to access control and can be consolidated into comprehensive access control properties

**Order Creation and Status:**
- Properties 8.1, 8.5, 8.6, 8.7 relate to order creation rules and can be combined
- Properties 10.4, 10.5, 10.6, 10.7 relate to approval outcomes and can be consolidated

**Inventory Management:**
- Properties 9.2, 9.3, 9.4, 9.5 all relate to inventory locking lifecycle and can be combined into fewer comprehensive properties

**Notifications:**
- Properties 19.1-19.6 all follow the same pattern and can be consolidated into a single comprehensive notification property

**Data Isolation:**
- Properties 21.3 and 21.4 are similar and can be combined into a single vendor/customer isolation property

After reflection, I'll focus on the most critical and non-redundant properties that provide unique validation value.

### Correctness Properties

Property 1: Authentication Credential Validation
*For any* username and password combination, authentication should succeed if and only if the credentials match a stored user record with correct password hash
**Validates: Requirements 1.2**

Property 2: Session Creation on Successful Authentication
*For any* successful authentication, a secure session should be created and associated with the authenticated user
**Validates: Requirements 1.3**

Property 3: Role-Based Access Control Enforcement
*For any* user and any operation, the system should allow the operation if and only if the user's role has permission for that operation
**Validates: Requirements 1.4, 1.5, 1.6, 21.2**

Property 4: Vendor Data Isolation
*For any* two distinct vendors, neither vendor should be able to access the other vendor's products, orders, invoices, or reports
**Validates: Requirements 1.6, 21.3**

Property 5: Customer Data Isolation
*For any* two distinct customers, neither customer should be able to access the other customer's orders, payments, or documents
**Validates: Requirements 21.4**

Property 6: Admin Action Audit Logging
*For any* administrator action that modifies system state, an audit log entry should exist with timestamp, actor, and action details
**Validates: Requirements 1.7, 18.7, 21.6**

Property 7: Product-Vendor Association
*For any* product created by a vendor, the product should be associated exclusively with that vendor and no other vendor should be able to modify it
**Validates: Requirements 2.2, 2.8**

Property 8: Variant Attribute Completeness
*For any* product with mandatory attributes, creating a variant should succeed if and only if all mandatory attribute values are provided
**Validates: Requirements 2.5, 5.2**

Property 9: Rental Period Temporal Validity
*For any* rental period, the system should accept it if and only if the end date/time is strictly after the start date/time
**Validates: Requirements 3.2**

Property 10: Time-Based Price Calculation
*For any* product, variant, and rental period, the calculated price should equal the product's price per duration unit multiplied by the duration in that unit
**Validates: Requirements 3.4, 3.5**

Property 11: Minimum Duration Enforcement
*For any* product with a defined minimum rental duration, rental requests with duration less than the minimum should be rejected
**Validates: Requirements 3.6**

Property 12: Search Result Relevance
*For any* search query with keywords, all returned products should contain at least one of the keywords in their name, description, or attributes
**Validates: Requirements 4.3**

Property 13: Browsing Inventory Non-Locking
*For any* browsing session, no inventory locks should be created regardless of how many products are viewed or added to wishlist
**Validates: Requirements 4.5, 4.6, 5.5, 6.6**

Property 14: Cart Price Recalculation
*For any* cart with items, modifying the rental period or configuration of any item should trigger a price recalculation that reflects the new selection
**Validates: Requirements 6.2**

Property 15: Checkout Availability Revalidation
*For any* checkout attempt, the system should revalidate availability for all cart items against current inventory locks before proceeding
**Validates: Requirements 6.4**

Property 16: Payment Intent Creation
*For any* payment initiation, a payment intent should be created with an amount that exactly matches the checkout total including all taxes and charges
**Validates: Requirements 7.1**

Property 17: Payment Verification Completeness
*For any* payment, backend verification should succeed if and only if the payment signature is valid, the amount matches the payment intent, and the payment intent ID matches
**Validates: Requirements 7.4**

Property 18: No Orders Without Verified Payment
*For any* rental order in the system, there should exist a corresponding payment record with status "Verified"
**Validates: Requirements 7.5, 7.6, 8.1, 13.3**

Property 19: Vendor-Wise Order Splitting
*For any* checkout containing products from N distinct vendors, exactly N rental orders should be created, each containing only products from one vendor
**Validates: Requirements 8.2**

Property 20: Order Unique Associations
*For any* rental order, it should be associated with exactly one vendor, exactly one customer, and exactly one verified payment
**Validates: Requirements 8.3**

Property 21: Order Identifier Uniqueness
*For any* two distinct rental orders, their order identifiers should be different
**Validates: Requirements 8.4**

Property 22: Initial Order Status Based on Verification Requirement
*For any* newly created rental order, if the product requires verification then initial status should be Pending_Vendor_Approval, otherwise it should be Auto_Approved
**Validates: Requirements 8.5, 8.6, 8.7**

Property 23: Time-Based Availability Evaluation
*For any* product variant and rental period, availability should be determined by checking for time period overlaps with existing active and pending orders, not just quantity
**Validates: Requirements 9.1, 9.6**

Property 24: Inventory Lock on Order Creation
*For any* created rental order, an inventory lock should exist for the product variant covering the exact rental period
**Validates: Requirements 9.2**

Property 25: No Overlapping Rentals
*For any* two rental orders for the same product variant, their rental periods should not overlap if both orders are in active or pending status
**Validates: Requirements 9.3**

Property 26: Inventory Release on Rejection or Completion
*For any* rental order that transitions to Rejected, Refunded, or Completed status, the associated inventory lock should be released
**Validates: Requirements 9.4, 9.5, 25.2**

Property 27: Approval Transition to Active
*For any* rental order in Pending_Vendor_Approval status, when the vendor approves it, the order status should transition to Active_Rental
**Validates: Requirements 10.4**

Property 28: Rejection Triggers Refund and Inventory Release
*For any* rental order that is rejected by a vendor, the order status should transition to Rejected, a refund should be initiated, and the inventory lock should be released
**Validates: Requirements 10.5, 10.6, 15.1**

Property 29: Auto-Approval Immediate Activation
*For any* rental order with Auto_Approved initial status, it should transition directly to Active_Rental without requiring vendor action
**Validates: Requirements 10.7**

Property 30: Document Access Control
*For any* uploaded document, only the customer who uploaded it, the vendor associated with the order, and administrators should be able to access it
**Validates: Requirements 11.4, 21.5**

Property 31: Order Single Status Invariant
*For any* rental order at any point in time, it should have exactly one status value
**Validates: Requirements 12.1**

Property 32: Valid Status Transitions
*For any* order status transition, the transition should be one of the allowed transitions defined by the lifecycle rules (e.g., Pending_Vendor_Approval can transition to Active_Rental or Rejected, but not to Completed)
**Validates: Requirements 12.3, 12.5**

Property 33: Status Transition Audit Logging
*For any* order status change, an audit log entry should exist with the old status, new status, timestamp, and the actor who initiated the change
**Validates: Requirements 12.4**

Property 34: Status Change Notification
*For any* order status change, appropriate notifications should be sent to affected parties (customer and/or vendor depending on the transition)
**Validates: Requirements 12.6, 19.1, 19.2, 19.3, 19.4, 19.5, 19.6**

Property 35: One Invoice Per Order
*For any* rental order, there should exist exactly one invoice associated with it
**Validates: Requirements 13.1**

Property 36: Invoice Immutability After Finalization
*For any* invoice with status "Finalized", any attempt to modify its line items, amounts, or other financial data should fail
**Validates: Requirements 13.4**

Property 37: Invoice-Order-Payment Linkage
*For any* invoice, it should be linked to exactly one rental order and exactly one verified payment
**Validates: Requirements 13.5**

Property 38: Refund Preserves Original Invoice
*For any* refund processed for an order, the original invoice for that order should remain unchanged in all its fields
**Validates: Requirements 13.7**

Property 39: Deposit Collection with Rental Payment
*For any* rental order where the product requires a security deposit, the total payment amount should include both the rental price and the deposit amount
**Validates: Requirements 14.2**

Property 40: Deposit Separate Recording
*For any* invoice with a security deposit, the deposit should appear as a separate line item distinct from rental charges
**Validates: Requirements 14.3, 13.6**

Property 41: Refund Initiation on Rejection
*For any* rental order that transitions to Rejected status, a refund record should be created with status "Initiated"
**Validates: Requirements 15.1**

Property 42: Refund Status Update on Initiation
*For any* refund initiation, the associated order status should be updated to Refunded
**Validates: Requirements 15.3**

Property 43: Refund-Payment-Order Linkage
*For any* refund record, it should be linked to both the original payment and the rental order
**Validates: Requirements 15.5**

Property 44: Customer Dashboard Order Visibility
*For any* customer, their dashboard should display all and only the rental orders where they are the customer
**Validates: Requirements 16.1**

Property 45: Vendor Dashboard Order Isolation
*For any* vendor, their dashboard should display all and only the rental orders where they are the vendor
**Validates: Requirements 17.1**

Property 46: Approval Queue Contains Pending Orders
*For any* rental order with status Pending_Vendor_Approval, it should appear in the approval queue of the associated vendor
**Validates: Requirements 17.2**

Property 47: Role-Based Report Filtering
*For any* report generated for a vendor, it should contain only data related to that vendor's products and orders, and for any report generated for a customer, it should contain only that customer's data
**Validates: Requirements 20.1, 20.6**

Property 48: Payment Verification Failure Prevents Order Creation
*For any* payment where backend verification fails (invalid signature, amount mismatch, or intent mismatch), no rental orders should be created
**Validates: Requirements 24.1**

Property 49: Inventory Conflict Rejection
*For any* order creation attempt that would create an overlapping rental period for a product variant that is already locked, the order creation should fail
**Validates: Requirements 24.2**

Property 50: Error Logging on System Errors
*For any* system error or exception, an error log entry should be created with timestamp, error details, and context information
**Validates: Requirements 24.3, 24.7**

Property 51: Rental Completion Enables Deposit Processing
*For any* rental order that transitions to Completed status, the vendor should be able to either release the security deposit or apply penalties against it
**Validates: Requirements 25.3, 25.4**

## Error Handling

### Payment Verification Failures

**Scenario:** Payment gateway reports success but backend verification fails

**Handling:**
- Payment marked as "Failed" status
- No rental orders created
- Customer notified of failure with clear message
- Cart preserved for retry
- Audit log created with failure reason

**Prevention:** Backend always performs independent verification of payment signature, amount, and intent ID

### Inventory Conflicts

**Scenario:** Two customers attempt to book the same product variant for overlapping periods

**Handling:**
- First verified payment locks inventory
- Second order creation attempt detects conflict
- Second order rejected before creation
- Second customer notified of unavailability
- Second customer's payment refunded

**Prevention:** Inventory locks created atomically during order creation with database-level conflict detection

### Vendor Approval Timeout

**Scenario:** Vendor does not respond to approval request within reasonable time

**Handling:**
- System sends reminder notifications at defined intervals
- After extended timeout, order may be auto-cancelled (configurable)
- If auto-cancelled, refund initiated automatically
- Customer notified of cancellation and refund

**Prevention:** Clear SLA communication to vendors, escalation to admin if pattern of delays

### Document Upload Failures

**Scenario:** Customer unable to upload required verification documents

**Handling:**
- Order remains in Pending_Vendor_Approval
- Customer can retry upload multiple times
- System provides clear error messages for file size/format issues
- After extended period without documents, order may be cancelled with refund

**Prevention:** Clear document requirements displayed upfront, file validation before upload attempt

### Refund Processing Failures

**Scenario:** Refund initiation succeeds but payment gateway processing fails

**Handling:**
- Refund status set to "Failed"
- Error logged with gateway response
- Admin notified for manual intervention
- Customer notified of delay
- Retry mechanism for transient failures

**Prevention:** Robust error handling in payment gateway integration, retry logic with exponential backoff

### Database Transaction Failures

**Scenario:** System crash or network failure during critical operation

**Handling:**
- All critical operations wrapped in database transactions
- Rollback on failure ensures consistent state
- No partial orders or payments
- Audit logs help identify incomplete operations
- Idempotent operations allow safe retry

**Prevention:** Proper transaction boundaries, connection pooling, timeout handling

### Concurrent Modification Conflicts

**Scenario:** Two admins or vendors attempt to modify same entity simultaneously

**Handling:**
- Optimistic locking with version numbers
- Second modification attempt detects conflict
- User notified to refresh and retry
- No data loss or corruption

**Prevention:** Version tracking on all mutable entities, conflict detection before commit

## Testing Strategy

### Dual Testing Approach

The testing strategy employs both unit testing and property-based testing as complementary approaches:

**Unit Tests:**
- Verify specific examples and edge cases
- Test error conditions and boundary values
- Validate integration points between components
- Focus on concrete scenarios that demonstrate correct behavior
- Examples: empty cart checkout, invalid date ranges, missing required fields

**Property-Based Tests:**
- Verify universal properties across all inputs
- Use randomized input generation to explore input space
- Catch edge cases that might not be considered in unit tests
- Validate invariants and business rules
- Each property test runs minimum 100 iterations

### Property-Based Testing Configuration

**Library Selection:**
- For PHP backend: Use PHPUnit with faker library for data generation
- Alternatively: Use Infection for mutation testing to validate test quality

**Test Configuration:**
- Minimum 100 iterations per property test
- Randomized but reproducible (seeded random generation)
- Each test tagged with feature name and property number
- Tag format: `@feature multi-vendor-rental-platform @property N: [property text]`

**Property Test Structure:**
```php
/**
 * @test
 * @feature multi-vendor-rental-platform
 * @property 18: No Orders Without Verified Payment
 */
public function property_no_orders_without_verified_payment() {
    for ($i = 0; $i < 100; $i++) {
        // Generate random order
        $order = $this->generateRandomOrder();
        
        // Verify property holds
        $payment = $this->paymentRepository->findById($order->paymentId);
        $this->assertEquals('Verified', $payment->status);
    }
}
```

### Unit Testing Focus Areas

**Authentication and Authorization:**
- Valid/invalid credentials
- Session creation and expiration
- Permission checks for each role
- Cross-role access attempts

**Product Management:**
- Product CRUD operations
- Variant creation with various attribute combinations
- Pricing configuration edge cases

**Rental Period Validation:**
- Invalid date ranges (end before start)
- Minimum duration enforcement
- Duration calculation accuracy

**Payment Integration:**
- Payment intent creation
- Signature verification (valid and invalid)
- Refund processing

**Order Lifecycle:**
- Status transitions (valid and invalid)
- Vendor approval/rejection
- Auto-approval flow

**Inventory Management:**
- Availability checking
- Lock creation and release
- Overlap detection

**Invoice Generation:**
- Line item calculation
- Tax computation
- Immutability enforcement

### Integration Testing

**End-to-End Flows:**
- Complete rental flow: browse → cart → checkout → payment → approval → active → complete
- Rejection flow: order → pending approval → rejection → refund
- Multi-vendor checkout: cart with multiple vendors → separate orders → separate invoices

**External Integration Testing:**
- Razorpay payment gateway (using test credentials)
- Email notification delivery
- Document upload and storage

### Test Data Management

**Test Database:**
- Separate test database for all tests
- Database reset between test runs
- Seed data for common scenarios

**Test Fixtures:**
- Predefined users (customer, vendor, admin)
- Sample products with variants
- Test payment credentials

### Performance Testing

**Load Testing:**
- Concurrent user browsing
- Simultaneous checkout attempts
- Inventory conflict scenarios under load

**Stress Testing:**
- Large number of products and orders
- Complex multi-vendor checkouts
- Report generation with large datasets

### Security Testing

**Access Control Testing:**
- Attempt unauthorized access to resources
- Cross-vendor data access attempts
- Privilege escalation attempts

**Payment Security Testing:**
- Invalid signature attempts
- Amount manipulation attempts
- Replay attack prevention

### Continuous Testing

**Automated Test Execution:**
- All tests run on every code commit
- Property tests run in CI/CD pipeline
- Test coverage monitoring (target: >80%)

**Regression Testing:**
- Full test suite run before deployment
- Critical path tests run on production-like environment
- Smoke tests after deployment
