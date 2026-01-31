<?php

namespace RentalPlatform\Services;

use PDO;
use RentalPlatform\Models\AuditLog;
use RentalPlatform\Repositories\AuditLogRepository;
use RentalPlatform\Auth\Session;

/**
 * Audit Logger Service
 * 
 * Provides convenient methods for logging sensitive actions throughout the system.
 * Automatically captures user context from session and request information.
 * 
 * Requirements:
 * - 1.7: Log administrator privileged actions with timestamp
 * - 12.4: Log order status transitions with timestamp and actor
 * - 18.7: Log admin actions for audit purposes
 * - 21.6: Log all sensitive actions
 */
class AuditLogger
{
    private AuditLogRepository $repository;
    private ?string $currentUserId;

    /**
     * Entity type constants
     */
    public const ENTITY_USER = 'User';
    public const ENTITY_VENDOR = 'Vendor';
    public const ENTITY_PRODUCT = 'Product';
    public const ENTITY_ORDER = 'Order';
    public const ENTITY_PAYMENT = 'Payment';
    public const ENTITY_INVOICE = 'Invoice';
    public const ENTITY_REFUND = 'Refund';
    public const ENTITY_DOCUMENT = 'Document';
    public const ENTITY_CATEGORY = 'Category';
    public const ENTITY_PLATFORM_CONFIG = 'PlatformConfig';

    /**
     * Action constants
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_STATUS_CHANGE = 'status_change';
    public const ACTION_APPROVAL = 'approval';
    public const ACTION_REJECTION = 'rejection';
    public const ACTION_REFUND = 'refund';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_PERMISSION_DENIED = 'permission_denied';
    public const ACTION_PAYMENT_VERIFICATION = 'payment_verification';
    public const ACTION_INVENTORY_LOCK = 'inventory_lock';
    public const ACTION_INVENTORY_RELEASE = 'inventory_release';
    public const ACTION_DOCUMENT_UPLOAD = 'document_upload';
    public const ACTION_DOCUMENT_ACCESS = 'document_access';
    public const ACTION_INVOICE_FINALIZE = 'invoice_finalize';
    public const ACTION_VENDOR_SUSPEND = 'vendor_suspend';
    public const ACTION_VENDOR_ACTIVATE = 'vendor_activate';
    public const ACTION_CONFIG_CHANGE = 'config_change';

    public function __construct(PDO $db)
    {
        $this->repository = new AuditLogRepository($db);
        $this->currentUserId = $this->getCurrentUserIdFromSession();
    }

    /**
     * Log a generic action
     * 
     * @param string $entityType Type of entity affected
     * @param string $entityId ID of the affected entity
     * @param string $action Action performed
     * @param array|null $oldValue Previous state
     * @param array|null $newValue New state
     * @param string|null $userId User who performed the action (defaults to current user)
     * @param string|null $ipAddress IP address (defaults to current request IP)
     * @return bool True on success
     */
    public function log(
        string $entityType,
        string $entityId,
        string $action,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $userId = null,
        ?string $ipAddress = null
    ): bool {
        $userId = $userId ?? $this->currentUserId;
        
        $auditLog = AuditLog::create(
            $userId,
            $entityType,
            $entityId,
            $action,
            $oldValue,
            $newValue,
            $ipAddress
        );

        return $this->repository->save($auditLog);
    }

    /**
     * Log an order status change
     * 
     * @param string $orderId
     * @param string $oldStatus
     * @param string $newStatus
     * @param string|null $userId
     * @return bool
     */
    public function logOrderStatusChange(
        string $orderId,
        string $oldStatus,
        string $newStatus,
        ?string $userId = null
    ): bool {
        return $this->log(
            self::ENTITY_ORDER,
            $orderId,
            self::ACTION_STATUS_CHANGE,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $userId
        );
    }

    /**
     * Log an order approval
     * 
     * @param string $orderId
     * @param string $vendorId
     * @return bool
     */
    public function logOrderApproval(string $orderId, string $vendorId): bool
    {
        return $this->log(
            self::ENTITY_ORDER,
            $orderId,
            self::ACTION_APPROVAL,
            null,
            ['vendor_id' => $vendorId],
            $vendorId
        );
    }

    /**
     * Log an order rejection
     * 
     * @param string $orderId
     * @param string $vendorId
     * @param string $reason
     * @return bool
     */
    public function logOrderRejection(string $orderId, string $vendorId, string $reason): bool
    {
        return $this->log(
            self::ENTITY_ORDER,
            $orderId,
            self::ACTION_REJECTION,
            null,
            ['vendor_id' => $vendorId, 'reason' => $reason],
            $vendorId
        );
    }

    /**
     * Log a payment verification
     * 
     * @param string $paymentId
     * @param bool $success
     * @param array $details
     * @return bool
     */
    public function logPaymentVerification(
        string $paymentId,
        bool $success,
        array $details = []
    ): bool {
        return $this->log(
            self::ENTITY_PAYMENT,
            $paymentId,
            self::ACTION_PAYMENT_VERIFICATION,
            null,
            array_merge(['success' => $success], $details)
        );
    }

    /**
     * Log a refund action
     * 
     * @param string $refundId
     * @param string $orderId
     * @param float $amount
     * @param string $reason
     * @return bool
     */
    public function logRefund(
        string $refundId,
        string $orderId,
        float $amount,
        string $reason
    ): bool {
        return $this->log(
            self::ENTITY_REFUND,
            $refundId,
            self::ACTION_REFUND,
            null,
            [
                'order_id' => $orderId,
                'amount' => $amount,
                'reason' => $reason
            ]
        );
    }

    /**
     * Log a user login
     * 
     * @param string $userId
     * @param bool $success
     * @param string|null $username
     * @return bool
     */
    public function logLogin(string $userId, bool $success, ?string $username = null): bool
    {
        $action = $success ? self::ACTION_LOGIN : self::ACTION_LOGIN_FAILED;
        
        return $this->log(
            self::ENTITY_USER,
            $userId,
            $action,
            null,
            $username ? ['username' => $username] : null,
            $userId
        );
    }

    /**
     * Log a user logout
     * 
     * @param string $userId
     * @return bool
     */
    public function logLogout(string $userId): bool
    {
        return $this->log(
            self::ENTITY_USER,
            $userId,
            self::ACTION_LOGOUT,
            null,
            null,
            $userId
        );
    }

    /**
     * Log a permission denied event
     * 
     * @param string $resource
     * @param string $action
     * @param string|null $userId
     * @return bool
     */
    public function logPermissionDenied(
        string $resource,
        string $action,
        ?string $userId = null
    ): bool {
        return $this->log(
            'Permission',
            $resource,
            self::ACTION_PERMISSION_DENIED,
            null,
            ['attempted_action' => $action],
            $userId
        );
    }

    /**
     * Log an inventory lock
     * 
     * @param string $lockId
     * @param string $productId
     * @param string $orderId
     * @param array $rentalPeriod
     * @return bool
     */
    public function logInventoryLock(
        string $lockId,
        string $productId,
        string $orderId,
        array $rentalPeriod
    ): bool {
        return $this->log(
            'InventoryLock',
            $lockId,
            self::ACTION_INVENTORY_LOCK,
            null,
            [
                'product_id' => $productId,
                'order_id' => $orderId,
                'rental_period' => $rentalPeriod
            ]
        );
    }

    /**
     * Log an inventory release
     * 
     * @param string $lockId
     * @param string $orderId
     * @return bool
     */
    public function logInventoryRelease(string $lockId, string $orderId): bool
    {
        return $this->log(
            'InventoryLock',
            $lockId,
            self::ACTION_INVENTORY_RELEASE,
            null,
            ['order_id' => $orderId]
        );
    }

    /**
     * Log a document upload
     * 
     * @param string $documentId
     * @param string $orderId
     * @param string $documentType
     * @param string $customerId
     * @return bool
     */
    public function logDocumentUpload(
        string $documentId,
        string $orderId,
        string $documentType,
        string $customerId
    ): bool {
        return $this->log(
            self::ENTITY_DOCUMENT,
            $documentId,
            self::ACTION_DOCUMENT_UPLOAD,
            null,
            [
                'order_id' => $orderId,
                'document_type' => $documentType
            ],
            $customerId
        );
    }

    /**
     * Log a document access
     * 
     * @param string $documentId
     * @param string $accessorId
     * @return bool
     */
    public function logDocumentAccess(string $documentId, string $accessorId): bool
    {
        return $this->log(
            self::ENTITY_DOCUMENT,
            $documentId,
            self::ACTION_DOCUMENT_ACCESS,
            null,
            null,
            $accessorId
        );
    }

    /**
     * Log an invoice finalization
     * 
     * @param string $invoiceId
     * @param string $orderId
     * @param float $totalAmount
     * @return bool
     */
    public function logInvoiceFinalize(
        string $invoiceId,
        string $orderId,
        float $totalAmount
    ): bool {
        return $this->log(
            self::ENTITY_INVOICE,
            $invoiceId,
            self::ACTION_INVOICE_FINALIZE,
            ['status' => 'Draft'],
            [
                'status' => 'Finalized',
                'order_id' => $orderId,
                'total_amount' => $totalAmount
            ]
        );
    }

    /**
     * Log a vendor suspension (admin action)
     * 
     * @param string $vendorId
     * @param string $reason
     * @param string $adminId
     * @return bool
     */
    public function logVendorSuspend(string $vendorId, string $reason, string $adminId): bool
    {
        return $this->log(
            self::ENTITY_VENDOR,
            $vendorId,
            self::ACTION_VENDOR_SUSPEND,
            ['status' => 'Active'],
            ['status' => 'Suspended', 'reason' => $reason],
            $adminId
        );
    }

    /**
     * Log a vendor activation (admin action)
     * 
     * @param string $vendorId
     * @param string $adminId
     * @return bool
     */
    public function logVendorActivate(string $vendorId, string $adminId): bool
    {
        return $this->log(
            self::ENTITY_VENDOR,
            $vendorId,
            self::ACTION_VENDOR_ACTIVATE,
            ['status' => 'Suspended'],
            ['status' => 'Active'],
            $adminId
        );
    }

    /**
     * Log a platform configuration change (admin action)
     * 
     * @param string $configKey
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string $adminId
     * @return bool
     */
    public function logConfigChange(
        string $configKey,
        $oldValue,
        $newValue,
        string $adminId
    ): bool {
        return $this->log(
            self::ENTITY_PLATFORM_CONFIG,
            $configKey,
            self::ACTION_CONFIG_CHANGE,
            ['value' => $oldValue],
            ['value' => $newValue],
            $adminId
        );
    }

    /**
     * Log entity creation
     * 
     * @param string $entityType
     * @param string $entityId
     * @param array $data
     * @param string|null $userId
     * @return bool
     */
    public function logCreate(
        string $entityType,
        string $entityId,
        array $data,
        ?string $userId = null
    ): bool {
        return $this->log(
            $entityType,
            $entityId,
            self::ACTION_CREATE,
            null,
            $data,
            $userId
        );
    }

    /**
     * Log entity update
     * 
     * @param string $entityType
     * @param string $entityId
     * @param array $oldData
     * @param array $newData
     * @param string|null $userId
     * @return bool
     */
    public function logUpdate(
        string $entityType,
        string $entityId,
        array $oldData,
        array $newData,
        ?string $userId = null
    ): bool {
        return $this->log(
            $entityType,
            $entityId,
            self::ACTION_UPDATE,
            $oldData,
            $newData,
            $userId
        );
    }

    /**
     * Log entity deletion
     * 
     * @param string $entityType
     * @param string $entityId
     * @param array $data
     * @param string|null $userId
     * @return bool
     */
    public function logDelete(
        string $entityType,
        string $entityId,
        array $data,
        ?string $userId = null
    ): bool {
        return $this->log(
            $entityType,
            $entityId,
            self::ACTION_DELETE,
            $data,
            null,
            $userId
        );
    }

    /**
     * Get the repository for direct access
     * 
     * @return AuditLogRepository
     */
    public function getRepository(): AuditLogRepository
    {
        return $this->repository;
    }

    /**
     * Get current user ID from session
     * 
     * @return string|null
     */
    private function getCurrentUserIdFromSession(): ?string
    {
        Session::start();
        return Session::getUserId();
    }

    /**
     * Log vendor approval
     *
     * @param string $vendorId
     * @param string $adminId
     * @return void
     */
    public function logVendorApproval(string $vendorId, string $adminId): void
    {
        $this->logAction(
            $adminId,
            self::ENTITY_VENDOR,
            $vendorId,
            self::ACTION_APPROVAL,
            ['status' => 'Pending'],
            ['status' => 'Active']
        );
    }
}
