<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
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

    // Get request parameters
    $reportType = $_GET['type'] ?? 'rental_volume';
    $startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
    $endDate = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
    $period = $_GET['period'] ?? 'monthly'; // daily, weekly, monthly

    // Validate date range
    if (!strtotime($startDate) || !strtotime($endDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid date format. Please use YYYY-MM-DD format.']);
        exit;
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Start date must be before end date.']);
        exit;
    }

    // Validate date range is not too large (max 2 years)
    $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
    if ($daysDiff > 730) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Date range cannot exceed 2 years.']);
        exit;
    }

    // Validate report type
    $validReportTypes = ['rental_volume', 'revenue', 'product_performance'];
    if (!in_array($reportType, $validReportTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid report type. Must be one of: ' . implode(', ', $validReportTypes)]);
        exit;
    }

    // Validate period
    $validPeriods = ['daily', 'weekly', 'monthly'];
    if ($reportType !== 'product_performance' && !in_array($period, $validPeriods)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid period. Must be one of: ' . implode(', ', $validPeriods)]);
        exit;
    }

    $response = ['success' => true, 'data' => []];

    switch ($reportType) {
        case 'rental_volume':
            $response['data'] = generateRentalVolumeReport($db, $vendorId, $startDate, $endDate, $period);
            break;
            
        case 'revenue':
            $response['data'] = generateRevenueReport($db, $vendorId, $startDate, $endDate, $period);
            break;
            
        case 'product_performance':
            $response['data'] = generateProductPerformanceReport($db, $vendorId, $startDate, $endDate);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid report type: ' . $reportType]);
            exit;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}

/**
 * Generate rental volume report
 */
function generateRentalVolumeReport(PDO $db, string $vendorId, string $startDate, string $endDate, string $period): array
{
    // Get date format based on period
    $dateFormat = match($period) {
        'daily' => '%Y-%m-%d',
        'weekly' => '%Y-%u',
        'monthly' => '%Y-%m',
        default => '%Y-%m'
    };

    $sql = "
        SELECT 
            DATE_FORMAT(o.created_at, ?) as period_label,
            COUNT(*) as total_orders,
            COUNT(CASE WHEN o.status = 'Completed' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN o.status = 'Active_Rental' THEN 1 END) as active_orders,
            COUNT(CASE WHEN o.status = 'Rejected' THEN 1 END) as rejected_orders,
            COUNT(CASE WHEN o.status = 'Pending_Vendor_Approval' THEN 1 END) as pending_orders,
            AVG(CASE WHEN o.status = 'Completed' THEN 
                TIMESTAMPDIFF(DAY, 
                    (SELECT MIN(rp.start_datetime) FROM order_items oi 
                     JOIN rental_periods rp ON oi.rental_period_id = rp.id 
                     WHERE oi.order_id = o.id),
                    (SELECT MAX(rp.end_datetime) FROM order_items oi 
                     JOIN rental_periods rp ON oi.rental_period_id = rp.id 
                     WHERE oi.order_id = o.id)
                ) 
            END) as avg_rental_duration_days
        FROM orders o
        WHERE o.vendor_id = ? 
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(o.created_at, ?)
        ORDER BY period_label ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$dateFormat, $vendorId, $startDate, $endDate, $dateFormat]);
    
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'period' => $row['period_label'],
            'total_orders' => (int)$row['total_orders'],
            'completed_orders' => (int)$row['completed_orders'],
            'active_orders' => (int)$row['active_orders'],
            'rejected_orders' => (int)$row['rejected_orders'],
            'pending_orders' => (int)$row['pending_orders'],
            'avg_rental_duration_days' => round((float)$row['avg_rental_duration_days'], 1),
            'completion_rate' => $row['total_orders'] > 0 ? 
                round(($row['completed_orders'] / $row['total_orders']) * 100, 1) : 0,
            'rejection_rate' => $row['total_orders'] > 0 ? 
                round(($row['rejected_orders'] / $row['total_orders']) * 100, 1) : 0
        ];
    }

    // Get summary statistics
    $summaryStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN status = 'Active_Rental' THEN 1 END) as active_orders,
            COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected_orders,
            COUNT(CASE WHEN status = 'Pending_Vendor_Approval' THEN 1 END) as pending_orders
        FROM orders 
        WHERE vendor_id = ? 
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $summaryStmt->execute([$vendorId, $startDate, $endDate]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    return [
        'report_type' => 'rental_volume',
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'summary' => [
            'total_orders' => (int)$summary['total_orders'],
            'completed_orders' => (int)$summary['completed_orders'],
            'active_orders' => (int)$summary['active_orders'],
            'rejected_orders' => (int)$summary['rejected_orders'],
            'pending_orders' => (int)$summary['pending_orders'],
            'completion_rate' => $summary['total_orders'] > 0 ? 
                round(($summary['completed_orders'] / $summary['total_orders']) * 100, 1) : 0,
            'rejection_rate' => $summary['total_orders'] > 0 ? 
                round(($summary['rejected_orders'] / $summary['total_orders']) * 100, 1) : 0
        ],
        'data' => $data
    ];
}

/**
 * Generate revenue report
 */
function generateRevenueReport(PDO $db, string $vendorId, string $startDate, string $endDate, string $period): array
{
    // Get date format based on period
    $dateFormat = match($period) {
        'daily' => '%Y-%m-%d',
        'weekly' => '%Y-%u',
        'monthly' => '%Y-%m',
        default => '%Y-%m'
    };

    $sql = "
        SELECT 
            DATE_FORMAT(o.created_at, ?) as period_label,
            COUNT(*) as total_orders,
            SUM(o.total_amount) as gross_revenue,
            SUM(CASE WHEN o.status IN ('Completed', 'Active_Rental') THEN o.total_amount ELSE 0 END) as net_revenue,
            SUM(o.deposit_amount) as total_deposits,
            SUM(CASE WHEN o.deposit_status = 'released' THEN o.deposit_amount ELSE 0 END) as released_deposits,
            SUM(CASE WHEN o.deposit_status IN ('partially_withheld', 'fully_withheld') THEN o.deposit_withheld_amount ELSE 0 END) as withheld_deposits,
            SUM(CASE WHEN o.status = 'Refunded' THEN 
                (SELECT COALESCE(SUM(r.amount), 0) FROM refunds r WHERE r.order_id = o.id)
                ELSE 0 END) as total_refunds,
            AVG(o.total_amount) as avg_order_value
        FROM orders o
        WHERE o.vendor_id = ? 
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(o.created_at, ?)
        ORDER BY period_label ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$dateFormat, $vendorId, $startDate, $endDate, $dateFormat]);
    
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'period' => $row['period_label'],
            'total_orders' => (int)$row['total_orders'],
            'gross_revenue' => (float)$row['gross_revenue'],
            'net_revenue' => (float)$row['net_revenue'],
            'total_deposits' => (float)$row['total_deposits'],
            'released_deposits' => (float)$row['released_deposits'],
            'withheld_deposits' => (float)$row['withheld_deposits'],
            'total_refunds' => (float)$row['total_refunds'],
            'avg_order_value' => (float)$row['avg_order_value']
        ];
    }

    // Get summary statistics
    $summaryStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as gross_revenue,
            SUM(CASE WHEN status IN ('Completed', 'Active_Rental') THEN total_amount ELSE 0 END) as net_revenue,
            SUM(deposit_amount) as total_deposits,
            SUM(CASE WHEN deposit_status = 'released' THEN deposit_amount ELSE 0 END) as released_deposits,
            SUM(CASE WHEN deposit_status IN ('partially_withheld', 'fully_withheld') THEN deposit_withheld_amount ELSE 0 END) as withheld_deposits,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE vendor_id = ? 
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $summaryStmt->execute([$vendorId, $startDate, $endDate]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    // Get total refunds
    $refundStmt = $db->prepare("
        SELECT COALESCE(SUM(r.amount), 0) as total_refunds
        FROM refunds r 
        JOIN orders o ON r.order_id = o.id
        WHERE o.vendor_id = ? 
        AND DATE(r.created_at) BETWEEN ? AND ?
    ");
    $refundStmt->execute([$vendorId, $startDate, $endDate]);
    $refundData = $refundStmt->fetch(PDO::FETCH_ASSOC);

    return [
        'report_type' => 'revenue',
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'summary' => [
            'total_orders' => (int)$summary['total_orders'],
            'gross_revenue' => (float)$summary['gross_revenue'],
            'net_revenue' => (float)$summary['net_revenue'],
            'total_deposits' => (float)$summary['total_deposits'],
            'released_deposits' => (float)$summary['released_deposits'],
            'withheld_deposits' => (float)$summary['withheld_deposits'],
            'total_refunds' => (float)$refundData['total_refunds'],
            'avg_order_value' => (float)$summary['avg_order_value']
        ],
        'data' => $data
    ];
}

/**
 * Generate product performance report
 */
function generateProductPerformanceReport(PDO $db, string $vendorId, string $startDate, string $endDate): array
{
    $sql = "
        SELECT 
            p.id as product_id,
            p.name as product_name,
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'Completed' THEN o.id END) as completed_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'Active_Rental' THEN o.id END) as active_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'Rejected' THEN o.id END) as rejected_orders,
            SUM(oi.total_price) as total_revenue,
            SUM(CASE WHEN o.status IN ('Completed', 'Active_Rental') THEN oi.total_price ELSE 0 END) as net_revenue,
            AVG(oi.total_price) as avg_order_value,
            SUM(oi.quantity) as total_quantity_rented,
            COUNT(DISTINCT o.customer_id) as unique_customers,
            AVG(CASE WHEN o.status = 'Completed' THEN 
                TIMESTAMPDIFF(DAY, rp.start_datetime, rp.end_datetime)
            END) as avg_rental_duration_days
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        LEFT JOIN rental_periods rp ON oi.rental_period_id = rp.id
        WHERE p.vendor_id = ?
        AND (o.id IS NULL OR DATE(o.created_at) BETWEEN ? AND ?)
        GROUP BY p.id, p.name
        ORDER BY total_revenue DESC, total_orders DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$vendorId, $startDate, $endDate]);
    
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalOrders = (int)$row['total_orders'];
        $completedOrders = (int)$row['completed_orders'];
        $rejectedOrders = (int)$row['rejected_orders'];
        
        $data[] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'active_orders' => (int)$row['active_orders'],
            'rejected_orders' => $rejectedOrders,
            'total_revenue' => (float)$row['total_revenue'],
            'net_revenue' => (float)$row['net_revenue'],
            'avg_order_value' => (float)$row['avg_order_value'],
            'total_quantity_rented' => (int)$row['total_quantity_rented'],
            'unique_customers' => (int)$row['unique_customers'],
            'avg_rental_duration_days' => round((float)$row['avg_rental_duration_days'], 1),
            'completion_rate' => $totalOrders > 0 ? 
                round(($completedOrders / $totalOrders) * 100, 1) : 0,
            'rejection_rate' => $totalOrders > 0 ? 
                round(($rejectedOrders / $totalOrders) * 100, 1) : 0
        ];
    }

    // Get summary statistics
    $summaryStmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as total_products,
            COUNT(DISTINCT CASE WHEN oi.id IS NOT NULL THEN p.id END) as products_with_orders,
            COUNT(DISTINCT o.id) as total_orders,
            SUM(oi.total_price) as total_revenue,
            AVG(oi.total_price) as avg_order_value
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE p.vendor_id = ?
        AND (o.id IS NULL OR DATE(o.created_at) BETWEEN ? AND ?)
    ");
    $summaryStmt->execute([$vendorId, $startDate, $endDate]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    return [
        'report_type' => 'product_performance',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'summary' => [
            'total_products' => (int)$summary['total_products'],
            'products_with_orders' => (int)$summary['products_with_orders'],
            'total_orders' => (int)$summary['total_orders'],
            'total_revenue' => (float)$summary['total_revenue'],
            'avg_order_value' => (float)$summary['avg_order_value']
        ],
        'data' => $data
    ];
}