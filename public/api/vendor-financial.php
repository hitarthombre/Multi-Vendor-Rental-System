<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\InvoiceRepository;
use RentalPlatform\Repositories\PaymentRepository;
use RentalPlatform\Repositories\RefundRepository;
use RentalPlatform\Database\Connection;

header('Content-Type: application/json');

try {
    Session::start();
    Middleware::requireRole(User::ROLE_VENDOR);

    $userId = Session::getUserId();
    
    // Get vendor profile
    $vendorRepo = new VendorRepository();
    $vendor = $vendorRepo->findByUserId($userId);

    if (!$vendor) {
        http_response_code(404);
        echo json_encode(['error' => 'Vendor profile not found']);
        exit;
    }

    $vendorId = $vendor->getId();
    $db = Connection::getInstance();

    // Get financial summary
    $orderRepo = new OrderRepository();
    $invoiceRepo = new InvoiceRepository();
    $paymentRepo = new PaymentRepository();
    $refundRepo = new RefundRepository();

    // Get order statistics
    $orderStats = $orderRepo->getVendorStatistics($vendorId);
    
    // Calculate financial metrics
    $totalRevenue = 0;
    $totalDeposits = 0;
    $totalRefunds = 0;
    $completedOrders = 0;
    $activeOrders = 0;
    $pendingOrders = 0;

    foreach ($orderStats as $status => $data) {
        $totalRevenue += $data['total_amount'];
        $totalDeposits += $data['deposit_amount'] ?? 0;
        
        switch ($status) {
            case 'Completed':
                $completedOrders = $data['count'];
                break;
            case 'Active_Rental':
                $activeOrders = $data['count'];
                break;
            case 'Pending_Vendor_Approval':
                $pendingOrders = $data['count'];
                break;
        }
    }

    // Get refund statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as refund_count,
            COALESCE(SUM(r.amount), 0) as total_refunded
        FROM refunds r 
        JOIN orders o ON r.order_id = o.id 
        WHERE o.vendor_id = ?
    ");
    $stmt->execute([$vendorId]);
    $refundStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRefunds = $refundStats['total_refunded'] ?? 0;
    $refundCount = $refundStats['refund_count'] ?? 0;

    // Get invoice statistics
    $invoiceStats = $invoiceRepo->getVendorStatistics($vendorId);
    
    // Get recent invoices with order details
    $stmt = $db->prepare("
        SELECT 
            i.*,
            o.order_number,
            o.status as order_status,
            u.username as customer_name
        FROM invoices i 
        JOIN orders o ON i.order_id = o.id 
        JOIN users u ON i.customer_id = u.id
        WHERE o.vendor_id = ? 
        ORDER BY i.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$vendorId]);
    $recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent payments with order details
    $stmt = $db->prepare("
        SELECT 
            p.*,
            o.order_number,
            o.status as order_status,
            u.username as customer_name
        FROM payments p 
        JOIN orders o ON o.payment_id = p.id 
        JOIN users u ON p.customer_id = u.id
        WHERE o.vendor_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$vendorId]);
    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent refunds with order details
    $stmt = $db->prepare("
        SELECT 
            r.*,
            o.order_number,
            u.username as customer_name
        FROM refunds r 
        JOIN orders o ON r.order_id = o.id 
        JOIN users u ON o.customer_id = u.id
        WHERE o.vendor_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$vendorId]);
    $recentRefunds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate net revenue (total revenue - refunds)
    $netRevenue = $totalRevenue - $totalRefunds;

    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'net_revenue' => $netRevenue,
                'total_deposits' => $totalDeposits,
                'total_refunds' => $totalRefunds,
                'refund_count' => $refundCount,
                'completed_orders' => $completedOrders,
                'active_orders' => $activeOrders,
                'pending_orders' => $pendingOrders,
                'total_invoices' => $invoiceStats['total_invoices'] ?? 0,
                'finalized_revenue' => $invoiceStats['finalized_revenue'] ?? 0
            ],
            'recent_invoices' => array_map(function($invoice) {
                return [
                    'id' => $invoice['id'],
                    'invoice_number' => $invoice['invoice_number'],
                    'order_number' => $invoice['order_number'],
                    'customer_name' => $invoice['customer_name'],
                    'total_amount' => (float)$invoice['total_amount'],
                    'status' => $invoice['status'],
                    'order_status' => $invoice['order_status'],
                    'created_at' => $invoice['created_at'],
                    'finalized_at' => $invoice['finalized_at']
                ];
            }, $recentInvoices),
            'recent_payments' => array_map(function($payment) {
                return [
                    'id' => $payment['id'],
                    'order_number' => $payment['order_number'],
                    'customer_name' => $payment['customer_name'],
                    'amount' => (float)$payment['amount'],
                    'status' => $payment['status'],
                    'order_status' => $payment['order_status'],
                    'created_at' => $payment['created_at'],
                    'verified_at' => $payment['verified_at']
                ];
            }, $recentPayments),
            'recent_refunds' => array_map(function($refund) {
                return [
                    'id' => $refund['id'],
                    'order_number' => $refund['order_number'],
                    'customer_name' => $refund['customer_name'],
                    'amount' => (float)$refund['amount'],
                    'reason' => $refund['reason'],
                    'status' => $refund['status'],
                    'created_at' => $refund['created_at'],
                    'processed_at' => $refund['processed_at']
                ];
            }, $recentRefunds)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}