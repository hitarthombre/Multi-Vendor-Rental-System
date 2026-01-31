# Requirements Document: Multi-Vendor Rental Platform

## Introduction

This document specifies the requirements for a multi-vendor, time-based rental management platform that enables customers to rent physical products online while allowing vendors to maintain full control over their inventory, approvals, and fulfillment. The system is designed specifically for time-based rentals where duration, availability, and responsibility are critical factors, distinguishing it from traditional e-commerce platforms focused on ownership transfer.

The platform supports multiple independent vendors, each managing their own products, pricing, and rental operations, while customers interact with a single unified marketplace. The system ensures vendor isolation, accurate accounting, operational clarity, and operates continuously (24×7) to handle real-world rental complexities.

## Glossary

- **System**: The multi-vendor rental management platform
- **Customer**: An end user who rents products through the platform
- **Vendor**: A business entity or individual who owns rental assets and fulfills rentals
- **Administrator**: Platform operator responsible for governance and configuration
- **Rental_Order**: A formal business transaction representing a time-bound rental
- **Payment_Intent**: A request to collect a specific amount before order creation
- **Rental_Period**: The time duration from rental start to end date/time
- **Variant**: A specific configuration of a product based on attribute values
- **Verification_Requirement**: A product setting determining if manual approval is needed
- **Invoice**: An immutable financial record documenting a rental transaction
- **Security_Deposit**: A refundable amount collected to protect against damage or misuse
- **Inventory_Lock**: Temporary reservation of an asset for a specific time period
- **Backend**: The server-side system that serves as the single source of truth
- **Frontend**: The client-side interface that displays information and collects input

## Requirements

### Requirement 1: User Authentication and Role-Based Access Control

**User Story:** As a system user, I want secure authentication and role-appropriate access, so that I can perform only the actions permitted for my role.

#### Acceptance Criteria

1. THE System SHALL support three distinct user roles: Customer, Vendor, and Administrator
2. WHEN a user attempts to authenticate, THE System SHALL verify credentials against stored user records
3. WHEN authentication succeeds, THE System SHALL create a secure session for the authenticated user
4. THE System SHALL enforce role-based permissions at the backend level for all operations
5. WHEN a Customer attempts to access vendor or admin functions, THE System SHALL deny access
6. WHEN a Vendor attempts to access another vendor's data, THE System SHALL deny access
7. WHEN an Administrator performs privileged actions, THE System SHALL log the action with timestamp

### Requirement 2: Product Management and Catalog Structure

**User Story:** As a Vendor, I want to create and manage rental products with detailed configurations, so that customers can discover and rent my assets accurately.

#### Acceptance Criteria

1. THE System SHALL allow Vendors to create rental products with name, description, images, and category
2. WHEN a Vendor creates a product, THE System SHALL associate that product exclusively with the Vendor
3. THE System SHALL support product categorization for browsing and filtering
4. THE System SHALL allow products to define attributes such as brand, color, size, and model
5. WHEN a product has attributes, THE System SHALL allow creation of variants representing specific configurations
6. THE System SHALL allow Vendors to specify pricing per rental duration unit for each product or variant
7. THE System SHALL allow Vendors to set a Verification_Requirement flag indicating if manual approval is needed
8. WHEN a Vendor modifies a product, THE System SHALL update only that Vendor's product data

### Requirement 3: Rental Period and Time-Based Pricing

**User Story:** As a Customer, I want to select rental periods and see accurate time-based pricing, so that I understand the cost of my rental.

#### Acceptance Criteria

1. THE System SHALL require every rental transaction to include a start date/time and end date/time
2. WHEN a Customer selects a rental period, THE System SHALL validate that the end date/time is after the start date/time
3. THE System SHALL support multiple duration units including hourly, daily, weekly, and monthly rentals
4. WHEN a rental period is selected, THE Backend SHALL calculate pricing based on the duration and product pricing rules
5. THE System SHALL recalculate pricing whenever the rental period or product configuration changes
6. THE System SHALL enforce minimum rental duration rules when defined for a product

### Requirement 4: Customer Discovery and Browsing

**User Story:** As a Customer, I want to browse and filter available rental products, so that I can find suitable items for my needs.

#### Acceptance Criteria

1. THE System SHALL display rental products from all vendors in a unified marketplace view
2. THE System SHALL provide filtering capabilities by category, attributes, price range, and availability
3. THE System SHALL provide search functionality to locate products by keywords
4. WHEN displaying products during browsing, THE System SHALL show base rental price and availability indicators
5. WHILE browsing, THE System SHALL NOT lock inventory or create reservations
6. THE System SHALL allow Customers to save products to a wishlist without affecting availability

### Requirement 5: Product Detail and Configuration Selection

**User Story:** As a Customer, I want to view detailed product information and select configurations, so that I can make informed rental decisions.

#### Acceptance Criteria

1. WHEN a Customer views a product detail page, THE System SHALL display comprehensive product information including images, description, specifications, and pricing
2. WHEN a product has variants, THE System SHALL require the Customer to select all mandatory attribute values before proceeding
3. WHEN a Customer selects a rental period and configuration, THE Backend SHALL calculate tentative pricing
4. IF a product requires verification, THEN THE System SHALL clearly inform the Customer of required documents
5. WHEN a Customer adds a configured product to cart, THE System SHALL store the selection without locking inventory

### Requirement 6: Shopping Cart and Checkout

**User Story:** As a Customer, I want to review my rental selections and proceed to checkout, so that I can confirm my rental intent.

#### Acceptance Criteria

1. THE System SHALL allow Customers to add multiple products from multiple vendors to a single cart
2. WHEN items are in the cart, THE System SHALL recalculate pricing dynamically when rental periods or configurations change
3. THE System SHALL allow Customers to apply discount coupons during checkout
4. WHEN a Customer proceeds to checkout, THE Backend SHALL revalidate availability for all cart items
5. WHEN a Customer proceeds to checkout, THE Backend SHALL recalculate final pricing including taxes and additional charges
6. WHILE items are in cart or checkout, THE System SHALL NOT lock inventory

### Requirement 7: Payment Integration and Verification

**User Story:** As a Customer, I want to complete secure payment for my rental, so that my rental order can be created.

#### Acceptance Criteria

1. WHEN a Customer initiates payment, THE Backend SHALL create a Payment_Intent with the exact payable amount
2. THE System SHALL integrate with Razorpay payment gateway for payment processing
3. WHEN payment is attempted, THE Frontend SHALL present the Razorpay payment interface to the Customer
4. WHEN payment processing completes, THE Backend SHALL verify the payment by checking payment signature, amount, and Payment_Intent match
5. IF payment verification fails, THEN THE System SHALL NOT create any Rental_Order
6. WHEN payment verification succeeds, THE System SHALL mark the payment as successful and trigger order creation
7. IF payment fails, THEN THE System SHALL notify the Customer and preserve the cart for retry

### Requirement 8: Order Creation and Vendor-Wise Splitting

**User Story:** As the System, I want to create vendor-specific rental orders after payment verification, so that each vendor can manage their rentals independently.

#### Acceptance Criteria

1. THE System SHALL create Rental_Orders only after payment verification succeeds
2. WHEN a checkout contains products from multiple vendors, THE System SHALL split the checkout into separate Rental_Orders per vendor
3. WHEN creating a Rental_Order, THE System SHALL associate it with exactly one Vendor, one Customer, and one verified payment
4. WHEN a Rental_Order is created, THE System SHALL assign it a unique order identifier
5. WHEN a Rental_Order is created, THE System SHALL set its initial status based on the product's Verification_Requirement setting
6. IF Verification_Requirement is true, THEN THE System SHALL set order status to Pending_Vendor_Approval
7. IF Verification_Requirement is false, THEN THE System SHALL set order status to Auto_Approved

### Requirement 9: Inventory Management and Availability

**User Story:** As a Vendor, I want the system to prevent overlapping rentals of my assets, so that I can fulfill rentals reliably.

#### Acceptance Criteria

1. THE System SHALL evaluate product availability based on rental time periods, not just quantity
2. WHEN a Rental_Order is created, THE System SHALL lock inventory for the specified Rental_Period
3. THE System SHALL prevent two Rental_Orders from having overlapping time periods for the same product variant
4. WHEN a Rental_Order is rejected or refunded, THE System SHALL release the Inventory_Lock immediately
5. WHEN a Rental_Order is completed, THE System SHALL release the Inventory_Lock and make the asset available
6. WHEN evaluating availability, THE System SHALL check for time conflicts with existing active and pending orders

### Requirement 10: Vendor Approval Workflow

**User Story:** As a Vendor, I want to review and approve rental requests requiring verification, so that I can ensure responsible asset handover.

#### Acceptance Criteria

1. WHEN a Rental_Order requires verification, THE System SHALL place it in the Vendor's approval queue
2. WHEN reviewing an order, THE Vendor SHALL be able to view Customer details, rental period, and uploaded documents
3. THE System SHALL allow the Vendor to approve or reject the Rental_Order
4. WHEN a Vendor approves an order, THE System SHALL transition the order status to Active_Rental
5. WHEN a Vendor rejects an order, THE System SHALL transition the order status to Rejected
6. WHEN an order is rejected, THE System SHALL initiate the refund process and release inventory
7. WHEN an order is auto-approved, THE System SHALL transition directly to Active_Rental without vendor intervention

### Requirement 11: Document Upload and Verification

**User Story:** As a Customer, I want to upload required verification documents, so that my rental can be approved by the vendor.

#### Acceptance Criteria

1. WHEN a product requires verification, THE System SHALL display the required document types to the Customer
2. THE System SHALL allow Customers to upload documents in common formats (PDF, JPG, PNG)
3. WHEN documents are uploaded, THE System SHALL store them securely and associate them with the Rental_Order
4. THE System SHALL allow only the associated Vendor and Administrators to access uploaded documents
5. WHEN a Vendor reviews an order, THE System SHALL display all uploaded documents for that order

### Requirement 12: Order Lifecycle and Status Management

**User Story:** As a system stakeholder, I want rental orders to follow a clear lifecycle, so that order state is always unambiguous.

#### Acceptance Criteria

1. THE System SHALL maintain each Rental_Order in exactly one status at any given time
2. THE System SHALL support the following order statuses: Payment_Successful, Pending_Vendor_Approval, Auto_Approved, Active_Rental, Completed, Rejected, Refunded
3. WHEN an order status changes, THE System SHALL validate that the transition is allowed by lifecycle rules
4. WHEN an order status changes, THE System SHALL log the transition with timestamp and actor
5. THE System SHALL prevent status transitions that violate lifecycle rules (e.g., Completed to Pending_Vendor_Approval)
6. WHEN an order status changes, THE System SHALL trigger appropriate notifications to affected parties

### Requirement 13: Invoicing and Financial Records

**User Story:** As a Vendor, I want accurate invoices for my rentals, so that I can meet legal and tax obligations.

#### Acceptance Criteria

1. THE System SHALL generate a separate Invoice for each Rental_Order
2. WHEN generating an Invoice, THE System SHALL include Vendor legal details, Customer billing information, rental period, line items, taxes, and total amount
3. THE System SHALL generate Invoices only after payment verification succeeds
4. WHEN an Invoice is confirmed, THE System SHALL make it immutable
5. THE System SHALL link each Invoice to exactly one Rental_Order and one verified payment
6. THE System SHALL include service charges (deposits, delivery fees) as separate Invoice line items
7. WHEN a refund occurs, THE System SHALL create a financial reversal record without modifying the original Invoice

### Requirement 14: Deposits and Additional Charges

**User Story:** As a Vendor, I want to collect security deposits and additional charges, so that I can protect my assets and cover operational costs.

#### Acceptance Criteria

1. THE System SHALL allow Vendors to define Security_Deposit requirements for products
2. WHEN a Security_Deposit is required, THE System SHALL collect it along with the rental payment
3. THE System SHALL record deposits separately from rental revenue in financial records
4. THE System SHALL support additional charges including delivery fees, late fees, and damage penalties
5. THE System SHALL treat all additional charges as service-type products for accounting consistency
6. WHEN a rental is completed without issues, THE System SHALL allow the Vendor to release the Security_Deposit
7. IF damage or violations occur, THEN THE System SHALL allow the Vendor to apply penalties against the Security_Deposit

### Requirement 15: Refund Processing

**User Story:** As a Customer, I want to receive refunds when my rental is rejected, so that I am not charged for unfulfilled rentals.

#### Acceptance Criteria

1. WHEN a Rental_Order is rejected by a Vendor, THE System SHALL initiate a refund process
2. THE System SHALL process refunds through the Razorpay payment gateway
3. WHEN a refund is initiated, THE System SHALL update the order status to Refunded
4. THE System SHALL track refund status separately (initiated, in progress, completed)
5. THE System SHALL link refund records to the original payment and Rental_Order
6. WHEN a refund is completed, THE System SHALL notify the Customer
7. THE System SHALL support full refunds for rejected orders and partial refunds when applicable

### Requirement 16: Customer Dashboard and Order Tracking

**User Story:** As a Customer, I want to view my rental orders and their status, so that I can track my rentals and understand next steps.

#### Acceptance Criteria

1. THE System SHALL provide a Customer dashboard displaying all Rental_Orders for the authenticated Customer
2. WHEN displaying orders, THE System SHALL show order reference, product details, vendor name, rental period, payment status, and current order status
3. THE System SHALL allow Customers to view detailed order information including pricing breakdown and Invoice
4. WHEN an order requires document upload, THE System SHALL display upload status and allow document submission
5. THE System SHALL display clear, human-readable status labels for each order lifecycle state
6. THE System SHALL allow Customers to download Invoices for completed and active rentals
7. THE System SHALL preserve completed rental records for historical reference

### Requirement 17: Vendor Dashboard and Operations

**User Story:** As a Vendor, I want a dashboard to manage my rental operations, so that I can approve orders, track active rentals, and manage my business.

#### Acceptance Criteria

1. THE System SHALL provide a Vendor dashboard displaying only that Vendor's Rental_Orders
2. THE System SHALL display incoming orders requiring approval in a dedicated approval queue
3. WHEN a Vendor views an order, THE System SHALL display Customer details, rental period, payment confirmation, and uploaded documents
4. THE System SHALL allow Vendors to approve or reject orders from the dashboard
5. THE System SHALL display active rentals with rental start and end dates
6. THE System SHALL allow Vendors to mark rentals as completed when the rental period ends
7. THE System SHALL allow Vendors to view their Invoices, payment status, and refund records
8. THE System SHALL provide Vendor-specific reports including rental volume, revenue, and product performance

### Requirement 18: Administrator Dashboard and Platform Control

**User Story:** As an Administrator, I want platform-wide visibility and control, so that I can govern the marketplace and ensure compliance.

#### Acceptance Criteria

1. THE System SHALL provide an Administrator dashboard with visibility into all Customers, Vendors, products, and Rental_Orders
2. THE System SHALL allow Administrators to manage Vendor accounts including approval, suspension, and profile updates
3. THE System SHALL allow Administrators to manage product categories, attributes, and variants
4. THE System SHALL allow Administrators to configure platform-wide settings including verification requirements and rental period definitions
5. THE System SHALL provide Administrators with platform-wide analytics including total rentals, vendor activity, payment trends, and refund frequency
6. THE System SHALL allow Administrators to monitor order flows and identify approval delays or bottlenecks
7. WHEN an Administrator performs privileged actions, THE System SHALL log the action for audit purposes

### Requirement 19: Notification System

**User Story:** As a system user, I want to receive notifications about important events, so that I stay informed about rental status changes.

#### Acceptance Criteria

1. WHEN payment verification succeeds, THE System SHALL notify the Customer of order creation
2. WHEN a Rental_Order requires vendor approval, THE System SHALL notify the Vendor
3. WHEN a Vendor approves or rejects an order, THE System SHALL notify the Customer
4. WHEN a rental becomes active, THE System SHALL notify both Customer and Vendor
5. WHEN a rental is completed, THE System SHALL notify both Customer and Vendor
6. WHEN a refund is initiated or completed, THE System SHALL notify the Customer
7. THE System SHALL send notifications via email using configured SMTP settings

### Requirement 20: Reporting and Analytics

**User Story:** As a system stakeholder, I want role-appropriate reports and analytics, so that I can understand performance and make informed decisions.

#### Acceptance Criteria

1. THE System SHALL provide role-based reporting where Customers see only their data, Vendors see only their business data, and Administrators see platform-wide data
2. THE System SHALL allow Vendors to generate reports on rental volume, revenue, product performance, and approval rates
3. THE System SHALL allow Administrators to generate platform-wide reports on total rentals, vendor activity, payment success rates, and refund frequency
4. THE System SHALL support exporting reports in PDF, CSV, and Excel formats
5. THE System SHALL generate all reports from verified payment records, confirmed Rental_Orders, and immutable Invoices
6. THE System SHALL ensure exported reports maintain role-based data filtering and vendor isolation

### Requirement 21: Security and Data Isolation

**User Story:** As a system stakeholder, I want strong security and data isolation, so that sensitive information is protected and vendors remain independent.

#### Acceptance Criteria

1. THE Backend SHALL serve as the single source of truth for all critical decisions including payment validation, order creation, and status transitions
2. THE System SHALL enforce role-based access control at the backend level for all operations
3. THE System SHALL ensure Vendors can access only their own products, orders, Invoices, and reports
4. THE System SHALL ensure Customers can access only their own rentals and payments
5. THE System SHALL store uploaded documents securely with access restricted to authorized Vendors and Administrators
6. THE System SHALL log all sensitive actions including order status changes, vendor decisions, admin overrides, and refund actions
7. THE System SHALL validate payment signatures and amounts on the backend to prevent fraud

### Requirement 22: Vendor Branding and Visual Identity

**User Story:** As a Vendor, I want my dashboard and invoices to reflect my brand, so that I feel ownership of my rental business.

#### Acceptance Criteria

1. THE System SHALL allow Vendors to configure a primary brand color for their dashboard
2. THE System SHALL allow Vendors to upload a logo for their business
3. WHEN a Vendor accesses their dashboard, THE System SHALL apply the Vendor's brand color to UI elements
4. WHEN generating an Invoice for a Vendor, THE System SHALL include the Vendor's logo and brand color
5. THE System SHALL display platform branding on Customer-facing pages and the Customer dashboard
6. THE System SHALL use standardized status colors (green for active, yellow for pending, red for rejected) regardless of vendor branding
7. THE System SHALL ensure Vendor theming is scoped only to Vendor UI and cannot affect platform or other Vendor interfaces

### Requirement 23: System Configuration and Environment

**User Story:** As a system administrator, I want the system to integrate with the XAMPP environment and configured services, so that the platform operates correctly.

#### Acceptance Criteria

1. THE System SHALL run on Apache web server on port 8081
2. THE System SHALL use MySQL database accessible via phpMyAdmin at http://localhost:8081/phpmyadmin
3. THE System SHALL use configured SMTP settings for email notifications (MAIL_USER: hitarththombre@gmail.com)
4. THE System SHALL use Razorpay test credentials (key_id: rzp_test_S6DaGQn3cdtVFp, key_secret: OiZT21gCnxns0Gk5rND4P9W4) for payment integration
5. THE System SHALL store uploaded documents in a secure directory accessible only to authorized users
6. THE System SHALL maintain database connection pooling for performance under load

### Requirement 24: Error Handling and Edge Cases

**User Story:** As a system stakeholder, I want the system to handle failures gracefully, so that data integrity is maintained and users are informed.

#### Acceptance Criteria

1. IF payment verification fails, THEN THE System SHALL NOT create any Rental_Order and SHALL notify the Customer
2. IF inventory conflicts occur during order creation, THEN THE System SHALL reject the conflicting order and notify the Customer
3. IF a refund initiation fails, THEN THE System SHALL log the error and allow Administrator intervention
4. IF a Vendor does not respond to approval requests within a defined time, THEN THE System SHALL send reminders
5. IF a rental period ends but the asset is not returned, THEN THE System SHALL allow the Vendor to apply late fees
6. IF a Customer does not upload required documents within a defined time, THEN THE System SHALL allow order cancellation with refund
7. WHEN any system error occurs, THE System SHALL log the error with timestamp and context for debugging

### Requirement 25: Rental Completion and Asset Return

**User Story:** As a Vendor, I want to mark rentals as completed when assets are returned, so that inventory becomes available and deposits can be processed.

#### Acceptance Criteria

1. WHEN a Rental_Period ends, THE System SHALL allow the Vendor to mark the Rental_Order as Completed
2. WHEN a rental is marked as Completed, THE System SHALL release the Inventory_Lock
3. WHEN a rental is marked as Completed, THE System SHALL allow the Vendor to release or withhold the Security_Deposit
4. IF the Vendor withholds a Security_Deposit, THEN THE System SHALL require a reason and allow penalty application
5. WHEN a rental is completed, THE System SHALL update the order status to Completed
6. WHEN a rental is completed, THE System SHALL notify the Customer of completion and deposit status
7. THE System SHALL preserve completed rental records for reporting and audit purposes
