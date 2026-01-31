<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Services\ReportingService;
use RentalPlatform\Repositories\VendorRepository;

Session::start();
Middleware::requireVendor();

header('Content-Type: application/json');

$userId = Session::get('user_id');
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    http_response_code(403);
    echo json_encode(['error' => 'Vendor not found']);
    exit;
}

$reportingService = new ReportingService();

// Validate access
if (!$reportingService->validateReportAccess($userId, 'Vendor', $vendor->id)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'export') {
    $format = $_GET['format'] ?? 'csv';
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get report data
    $report = $reportingService->getVendorReport($vendor->id, [
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    
    if ($format === 'csv') {
        // Export as CSV
        $filename = 'vendor_report_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Rental Volume Section
        fputcsv($output, ['Rental Volume']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Orders', $report['rental_volume']['total_orders']]);
        fputcsv($output, ['Active Rentals', $report['rental_volume']['active_rentals']]);
        fputcsv($output, ['Completed Rentals', $report['rental_volume']['completed_rentals']]);
        fputcsv($output, ['Rejected Orders', $report['rental_volume']['rejected_orders']]);
        fputcsv($output, []);
        
        // Revenue Section
        fputcsv($output, ['Revenue Summary']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Revenue', $report['revenue']['total_revenue'] ?? 0]);
        fputcsv($output, ['Average Order Value', $report['revenue']['avg_order_value'] ?? 0]);
        fputcsv($output, ['Unique Customers', $report['revenue']['unique_customers'] ?? 0]);
        fputcsv($output, []);
        
        // Product Performance
        fputcsv($output, ['Product Performance']);
        fputcsv($output, ['Product Name', 'Order Count', 'Total Revenue']);
        foreach ($report['product_performance'] as $product) {
            fputcsv($output, [
                $product['name'],
                $product['order_count'],
                $product['total_revenue'] ?? 0
            ]);
        }
        fputcsv($output, []);
        
        // Approval Stats
        fputcsv($output, ['Approval Statistics']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Requiring Approval', $report['approval_stats']['total_requiring_approval'] ?? 0]);
        fputcsv($output, ['Approved', $report['approval_stats']['approved'] ?? 0]);
        fputcsv($output, ['Rejected', $report['approval_stats']['rejected'] ?? 0]);
        fputcsv($output, ['Avg Approval Time (hours)', $report['approval_stats']['avg_approval_time_hours'] ?? 0]);
        fputcsv($output, []);
        
        // Daily Trends
        fputcsv($output, ['Daily Trends']);
        fputcsv($output, ['Date', 'Order Count', 'Daily Revenue']);
        foreach ($report['daily_trends'] as $trend) {
            fputcsv($output, [
                $trend['date'],
                $trend['order_count'],
                $trend['daily_revenue']
            ]);
        }
        
        fclose($output);
        exit;
    } elseif ($format === 'pdf') {
        // For PDF, we'll return a simple message
        // In a real implementation, you'd use a library like TCPDF or FPDF
        http_response_code(501);
        echo json_encode(['error' => 'PDF export not yet implemented']);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid format']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
