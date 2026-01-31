<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PDO;
use DateTime;
use RentalPlatform\Models\AuditLog;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\AuditLogRepository;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Services\AuditLogger;
use RentalPlatform\Database\Connection;

class AuditLogTest extends TestCase
{
    private PDO $db;
    private AuditLogRepository $repository;
    private AuditLogger $logger;
    private UserRepository $userRepository;
    private array $testUserIds = [];

    protected function setUp(): void
    {
        // Use the actual MySQL database connection
        $this->db = Connection::getInstance();

        $this->repository = new AuditLogRepository($this->db);
        $this->logger = new AuditLogger($this->db);
        $this->userRepository = new UserRepository($this->db);
        
        // Create test users for foreign key constraints
        $this->createTestUsers();
        
        // Clean up any existing test audit logs
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test data after each test
        $this->cleanupTestData();
        $this->deleteTestUsers();
        // Don't set $this->db to null as it's a singleton
    }
    
    private function createTestUsers(): void
    {
        // Create test users with unique identifiers
        $timestamp = time();
        
        $user1 = User::create("testuser1_{$timestamp}", "test1_{$timestamp}@example.com", 'password123', User::ROLE_CUSTOMER);
        $user2 = User::create("testuser2_{$timestamp}", "test2_{$timestamp}@example.com", 'password123', User::ROLE_VENDOR);
        $user3 = User::create("testuser3_{$timestamp}", "test3_{$timestamp}@example.com", 'password123', User::ROLE_ADMINISTRATOR);
        
        $this->userRepository->create($user1);
        $this->userRepository->create($user2);
        $this->userRepository->create($user3);
        
        $this->testUserIds = [
            'user-123' => $user1->getId(),
            'user-456' => $user2->getId(),
            'vendor-456' => $user2->getId(),
            'admin-123' => $user3->getId(),
            'admin-456' => $user3->getId(),
            'customer-789' => $user1->getId(),
        ];
    }
    
    private function deleteTestUsers(): void
    {
        foreach (array_unique($this->testUserIds) as $userId) {
            try {
                $this->userRepository->delete($userId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }
    
    private function cleanupTestData(): void
    {
        // Delete test audit logs (those with test entity IDs)
        $stmt = $this->db->prepare("
            DELETE FROM audit_logs 
            WHERE entity_id LIKE 'order-%' 
            OR entity_id LIKE 'user-%' 
            OR entity_id LIKE 'payment-%'
            OR entity_id LIKE 'product-%'
            OR entity_id LIKE 'vendor-%'
            OR entity_id LIKE 'invoice-%'
            OR entity_id LIKE 'refund-%'
            OR entity_id LIKE 'doc-%'
            OR entity_id LIKE 'lock-%'
            OR entity_id LIKE 'system-%'
            OR entity_id = 'max_rental_days'
            OR entity_type = 'Permission'
        ");
        $stmt->execute();
    }
    
    private function getUserId(string $key): string
    {
        return $this->testUserIds[$key] ?? $this->testUserIds['user-123'];
    }

    // AuditLog Model Tests

    public function testCreateAuditLog()
    {
        $auditLog = AuditLog::create(
            'user-123',
            'Order',
            'order-456',
            'status_change',
            ['status' => 'Pending'],
            ['status' => 'Approved'],
            '192.168.1.1'
        );

        $this->assertNotEmpty($auditLog->getId());
        $this->assertEquals('user-123', $auditLog->getUserId());
        $this->assertEquals('Order', $auditLog->getEntityType());
        $this->assertEquals('order-456', $auditLog->getEntityId());
        $this->assertEquals('status_change', $auditLog->getAction());
        $this->assertEquals(['status' => 'Pending'], $auditLog->getOldValue());
        $this->assertEquals(['status' => 'Approved'], $auditLog->getNewValue());
        $this->assertInstanceOf(DateTime::class, $auditLog->getTimestamp());
        $this->assertEquals('192.168.1.1', $auditLog->getIpAddress());
    }

    public function testCreateAuditLogWithNullUser()
    {
        $auditLog = AuditLog::create(
            null,
            'System',
            'system-1',
            'automated_action'
        );

        $this->assertNull($auditLog->getUserId());
        $this->assertEquals('System', $auditLog->getEntityType());
    }

    public function testAuditLogToArray()
    {
        $auditLog = AuditLog::create(
            'user-123',
            'Order',
            'order-456',
            'status_change',
            ['status' => 'Pending'],
            ['status' => 'Approved']
        );

        $array = $auditLog->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('entity_type', $array);
        $this->assertArrayHasKey('entity_id', $array);
        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('old_value', $array);
        $this->assertArrayHasKey('new_value', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('ip_address', $array);
    }

    public function testAuditLogGetDescription()
    {
        $auditLog = AuditLog::create(
            'user-123',
            'Order',
            'order-456',
            'status_change'
        );

        $description = $auditLog->getDescription();
        $this->assertStringContainsString('user-123', $description);
        $this->assertStringContainsString('status_change', $description);
        $this->assertStringContainsString('Order', $description);
        $this->assertStringContainsString('order-456', $description);
    }

    public function testAuditLogHasChange()
    {
        $auditLogWithChange = AuditLog::create(
            'user-123',
            'Order',
            'order-456',
            'status_change',
            ['status' => 'Pending'],
            ['status' => 'Approved']
        );

        $auditLogWithoutChange = AuditLog::create(
            'user-123',
            'Order',
            'order-456',
            'view'
        );

        $this->assertTrue($auditLogWithChange->hasChange());
        $this->assertFalse($auditLogWithoutChange->hasChange());
    }

    public function testAuditLogGetChanges()
    {
        $auditLog = AuditLog::create(
            'user-123',
            'Order',
            'order-456',
            'update',
            ['status' => 'Pending', 'amount' => 100],
            ['status' => 'Approved', 'amount' => 100]
        );

        $changes = $auditLog->getChanges();

        $this->assertIsArray($changes);
        $this->assertArrayHasKey('status', $changes);
        $this->assertEquals('Pending', $changes['status']['old']);
        $this->assertEquals('Approved', $changes['status']['new']);
        $this->assertArrayNotHasKey('amount', $changes); // No change in amount
    }

    // AuditLogRepository Tests

    public function testSaveAuditLog()
    {
        $auditLog = AuditLog::create(
            $this->getUserId('user-123'),
            'Order',
            'order-456',
            'status_change',
            ['status' => 'Pending'],
            ['status' => 'Approved']
        );

        $result = $this->repository->save($auditLog);
        $this->assertTrue($result);

        // Verify it was saved
        $found = $this->repository->findById($auditLog->getId());
        $this->assertNotNull($found);
        $this->assertEquals($auditLog->getId(), $found->getId());
    }

    public function testFindById()
    {
        $auditLog = AuditLog::create(
            $this->getUserId('user-123'),
            'Order',
            'order-456',
            'status_change'
        );

        $this->repository->save($auditLog);
        $found = $this->repository->findById($auditLog->getId());

        $this->assertNotNull($found);
        $this->assertEquals($auditLog->getId(), $found->getId());
        $this->assertEquals($auditLog->getUserId(), $found->getUserId());
        $this->assertEquals($auditLog->getEntityType(), $found->getEntityType());
    }

    public function testFindByIdNotFound()
    {
        $found = $this->repository->findById('non-existent-id');
        $this->assertNull($found);
    }

    public function testFindByUserId()
    {
        $userId = $this->getUserId('user-123');
        
        // Create multiple audit logs for the same user
        for ($i = 0; $i < 3; $i++) {
            $auditLog = AuditLog::create(
                $userId,
                'Order',
                "order-{$i}",
                'action'
            );
            $this->repository->save($auditLog);
        }

        // Create one for a different user
        $auditLog = AuditLog::create(
            $this->getUserId('user-456'),
            'Order',
            'order-999',
            'action'
        );
        $this->repository->save($auditLog);

        $logs = $this->repository->findByUserId($userId);
        $this->assertCount(3, $logs);
        
        foreach ($logs as $log) {
            $this->assertEquals($userId, $log->getUserId());
        }
    }

    public function testFindByEntity()
    {
        // Create multiple audit logs for the same entity
        for ($i = 0; $i < 3; $i++) {
            $auditLog = AuditLog::create(
                $this->getUserId('user-123'),
                'Order',
                'order-123',
                'action'
            );
            $this->repository->save($auditLog);
        }

        // Create one for a different entity
        $auditLog = AuditLog::create(
            $this->getUserId('user-456'),
            'Order',
            'order-456',
            'action'
        );
        $this->repository->save($auditLog);

        $logs = $this->repository->findByEntity('Order', 'order-123');
        $this->assertCount(3, $logs);
        
        foreach ($logs as $log) {
            $this->assertEquals('Order', $log->getEntityType());
            $this->assertEquals('order-123', $log->getEntityId());
        }
    }

    public function testFindByAction()
    {
        // Create multiple audit logs with the same action
        for ($i = 0; $i < 3; $i++) {
            $auditLog = AuditLog::create(
                $this->getUserId('user-123'),
                'Order',
                "order-{$i}",
                'status_change'
            );
            $this->repository->save($auditLog);
        }

        // Create one with a different action
        $auditLog = AuditLog::create(
            $this->getUserId('user-123'),
            'Order',
            'order-999',
            'approval'
        );
        $this->repository->save($auditLog);

        $logs = $this->repository->findByAction('status_change');
        $this->assertCount(3, $logs);
        
        foreach ($logs as $log) {
            $this->assertEquals('status_change', $log->getAction());
        }
    }

    public function testFindByDateRange()
    {
        // Create audit logs
        $auditLog1 = AuditLog::create($this->getUserId('user-123'), 'Order', 'order-1', 'action');
        $this->repository->save($auditLog1);

        sleep(1); // Ensure different timestamps

        $startDate = new DateTime();
        
        $auditLog2 = AuditLog::create($this->getUserId('user-123'), 'Order', 'order-2', 'action');
        $this->repository->save($auditLog2);

        $endDate = new DateTime();
        $endDate->modify('+1 hour');

        $logs = $this->repository->findByDateRange($startDate, $endDate);
        
        // Should find at least the second log
        $this->assertGreaterThanOrEqual(1, count($logs));
    }

    public function testFindRecent()
    {
        // Create multiple audit logs
        for ($i = 0; $i < 5; $i++) {
            $auditLog = AuditLog::create(
                $this->getUserId('user-123'),
                'Order',
                "order-{$i}",
                'action'
            );
            $this->repository->save($auditLog);
        }

        $logs = $this->repository->findRecent(3);
        $this->assertCount(3, $logs);
    }

    public function testSearch()
    {
        $userId1 = $this->getUserId('user-123');
        $userId2 = $this->getUserId('user-456');
        
        // Clean up first to ensure clean state
        $this->cleanupTestData();
        
        // Create various audit logs with unique entity IDs
        $testPrefix = 'search-test-' . time();
        $this->repository->save(AuditLog::create($userId1, 'Order', "{$testPrefix}-order-1", 'status_change'));
        $this->repository->save(AuditLog::create($userId1, 'Order', "{$testPrefix}-order-2", 'approval'));
        $this->repository->save(AuditLog::create($userId2, 'Payment', "{$testPrefix}-payment-1", 'verification'));

        // Search by user_id
        $logs = $this->repository->search(['user_id' => $userId1]);
        $this->assertGreaterThanOrEqual(2, count($logs));

        // Search by entity_type
        $logs = $this->repository->search(['entity_type' => 'Order']);
        $this->assertGreaterThanOrEqual(2, count($logs));

        // Search by action
        $logs = $this->repository->search(['action' => 'status_change']);
        $this->assertGreaterThanOrEqual(1, count($logs));

        // Search with multiple filters
        $logs = $this->repository->search([
            'user_id' => $userId1,
            'entity_type' => 'Order',
            'action' => 'approval'
        ]);
        $this->assertCount(1, $logs);
    }

    public function testCount()
    {
        $userId = $this->getUserId('user-123');
        
        // Clean up first to ensure clean state
        $this->cleanupTestData();
        
        // Create multiple audit logs with unique entity IDs
        $testEntityPrefix = 'test-count-' . time();
        for ($i = 0; $i < 5; $i++) {
            $auditLog = AuditLog::create(
                $userId,
                'Order',
                "{$testEntityPrefix}-{$i}",
                'action'
            );
            $this->repository->save($auditLog);
        }

        // Count with filter for our specific test entities
        $count = $this->repository->count(['user_id' => $userId]);
        $this->assertGreaterThanOrEqual(5, $count);

        $count = $this->repository->count(['user_id' => $this->getUserId('user-456')]);
        $this->assertEquals(0, $count);
    }

    // AuditLogger Service Tests

    public function testLogGenericAction()
    {
        $userId = $this->getUserId('user-123');
        
        $result = $this->logger->log(
            'Order',
            'order-123',
            'status_change',
            ['status' => 'Pending'],
            ['status' => 'Approved'],
            $userId
        );

        $this->assertTrue($result);

        // Verify it was logged
        $logs = $this->repository->findByEntity('Order', 'order-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('status_change', $logs[0]->getAction());
    }

    public function testLogOrderStatusChange()
    {
        $userId = $this->getUserId('user-123');
        
        $result = $this->logger->logOrderStatusChange(
            'order-123',
            'Pending',
            'Approved',
            $userId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Order', 'order-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('status_change', $logs[0]->getAction());
        $this->assertEquals(['status' => 'Pending'], $logs[0]->getOldValue());
        $this->assertEquals(['status' => 'Approved'], $logs[0]->getNewValue());
    }

    public function testLogOrderApproval()
    {
        $vendorId = $this->getUserId('vendor-456');
        
        $result = $this->logger->logOrderApproval('order-123', $vendorId);

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Order', 'order-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('approval', $logs[0]->getAction());
        $this->assertEquals($vendorId, $logs[0]->getUserId());
    }

    public function testLogOrderRejection()
    {
        $vendorId = $this->getUserId('vendor-456');
        
        $result = $this->logger->logOrderRejection(
            'order-123',
            $vendorId,
            'Insufficient documentation'
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Order', 'order-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('rejection', $logs[0]->getAction());
        $this->assertArrayHasKey('reason', $logs[0]->getNewValue());
    }

    public function testLogPaymentVerification()
    {
        $result = $this->logger->logPaymentVerification(
            'payment-123',
            true,
            ['amount' => 100.00]
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Payment', 'payment-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('payment_verification', $logs[0]->getAction());
        $this->assertTrue($logs[0]->getNewValue()['success']);
    }

    public function testLogRefund()
    {
        $result = $this->logger->logRefund(
            'refund-123',
            'order-456',
            100.00,
            'Order rejected'
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Refund', 'refund-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('refund', $logs[0]->getAction());
    }

    public function testLogLogin()
    {
        $userId = $this->getUserId('user-123');
        
        $result = $this->logger->logLogin($userId, true, 'john_doe');

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('User', $userId);
        $this->assertCount(1, $logs);
        $this->assertEquals('login', $logs[0]->getAction());
    }

    public function testLogLoginFailed()
    {
        $userId = $this->getUserId('user-123');
        
        $result = $this->logger->logLogin($userId, false, 'john_doe');

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('User', $userId);
        $this->assertCount(1, $logs);
        $this->assertEquals('login_failed', $logs[0]->getAction());
    }

    public function testLogPermissionDenied()
    {
        $userId = $this->getUserId('user-123');
        
        $result = $this->logger->logPermissionDenied(
            'product',
            'delete',
            $userId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByAction('permission_denied');
        $this->assertCount(1, $logs);
    }

    public function testLogInventoryLock()
    {
        $result = $this->logger->logInventoryLock(
            'lock-123',
            'product-456',
            'order-789',
            ['start' => '2024-01-01', 'end' => '2024-01-07']
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByAction('inventory_lock');
        $this->assertCount(1, $logs);
    }

    public function testLogDocumentUpload()
    {
        $customerId = $this->getUserId('customer-789');
        
        $result = $this->logger->logDocumentUpload(
            'doc-123',
            'order-456',
            'ID Proof',
            $customerId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Document', 'doc-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('document_upload', $logs[0]->getAction());
    }

    public function testLogInvoiceFinalize()
    {
        $result = $this->logger->logInvoiceFinalize(
            'invoice-123',
            'order-456',
            150.00
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Invoice', 'invoice-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('invoice_finalize', $logs[0]->getAction());
    }

    public function testLogVendorSuspend()
    {
        $adminId = $this->getUserId('admin-456');
        $vendorId = $this->getUserId('vendor-456');
        
        $result = $this->logger->logVendorSuspend(
            $vendorId,
            'Policy violation',
            $adminId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Vendor', $vendorId);
        $this->assertCount(1, $logs);
        $this->assertEquals('vendor_suspend', $logs[0]->getAction());
        $this->assertEquals($adminId, $logs[0]->getUserId());
    }

    public function testLogConfigChange()
    {
        $adminId = $this->getUserId('admin-123');
        
        $result = $this->logger->logConfigChange(
            'max_rental_days',
            30,
            60,
            $adminId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('PlatformConfig', 'max_rental_days');
        $this->assertCount(1, $logs);
        $this->assertEquals('config_change', $logs[0]->getAction());
    }

    public function testLogCreate()
    {
        $vendorId = $this->getUserId('vendor-456');
        
        $result = $this->logger->logCreate(
            'Product',
            'product-123',
            ['name' => 'Test Product', 'price' => 50.00],
            $vendorId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Product', 'product-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('create', $logs[0]->getAction());
    }

    public function testLogUpdate()
    {
        $vendorId = $this->getUserId('vendor-456');
        
        $result = $this->logger->logUpdate(
            'Product',
            'product-123',
            ['name' => 'Old Name', 'price' => 50.00],
            ['name' => 'New Name', 'price' => 50.00],
            $vendorId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Product', 'product-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('update', $logs[0]->getAction());
        
        $changes = $logs[0]->getChanges();
        $this->assertArrayHasKey('name', $changes);
    }

    public function testLogDelete()
    {
        $vendorId = $this->getUserId('vendor-456');
        
        $result = $this->logger->logDelete(
            'Product',
            'product-123',
            ['name' => 'Test Product', 'price' => 50.00],
            $vendorId
        );

        $this->assertTrue($result);

        $logs = $this->repository->findByEntity('Product', 'product-123');
        $this->assertCount(1, $logs);
        $this->assertEquals('delete', $logs[0]->getAction());
    }
}
