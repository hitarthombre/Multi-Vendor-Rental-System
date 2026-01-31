<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Services\ReportingService;

Session::start();
Middleware::requireAdministrator();

header('Content-Type: application/json');

$reportingService = new ReportingService();

$action = $_GET['action'] ?? '';

if ($action === 'export') {
    $format = $_GET['format'] ?? 'csv';
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get report data
    $report = $reportingService->getAdminReport([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    
    if ($format === 'csv') {
        // Export as CSV
        $filename = 'platform_report_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Platform Rentals Section
        fputcsv($output, ['Platform-Wide Rentals']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Orders', $report['platform_rentals']['total_orders']]);
        fputcsv($output, ['Active Rentals', $report['platform_rentals']['active_rentals']]);
        fputcsv($output, ['Completed Rentals', $report['platform_rentals']['completed_rentals']]);
        fputcsv($output, ['Rejected Orders', $report['platform_rentals']['rejected_orders']]);
        fputcsv($output, ['Pending Approval', $report['platform_rentals']['pending_approval']]);
        fputcsv($output, []);
        
        // Payment Stats Section
        fputcsv($output, ['Payment Statistics']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Payments', $report['payment_stats']['total_payments']]);
        fputcsv($output, ['Verified Payments', $report['payment_stats']['verified_payments']]);
        fputcsv($output, ['Failed Payments', $report['payment_stats']['failed_payments']]);
        fputcsv($output, ['Total Revenue', $report['payment_stats']['total_revenue']]);
        fputcsv($output, ['Success Rate (%)', $report['payment_stats']['success_rate']]);
        fputcsv($output, []);
        
        // Refund Stats
        fputcsv($output, ['Refund Statistics']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Refunds', $report['refund_stats']['total_refunds']]);
        fputcsv($output, ['Total Refunded Amount', $report['refund_stats']['total_refunded']]);
        fputcsv($output, ['Total Orders', $report['refund_stats']['total_orders']]);
        fputcsv($output, ['Refund Rate (%)', $report['refund_stats']['refund_rate']]);
        fputcsv($output, []);
        
        // Vendor Activity
        fputcsv($output, ['Vendor Activity']);
        fputcsv($output, ['Vendor Name', 'Products', 'Orders', 'Revenue', 'Approval Rate (%)']);
        foreach ($report['vendor_activity'] as $vendor) {
            fputcsv($output, [
                $vendor['business_name'],
                $vendor['product_count'],
                $vendor['order_count'],
                $vendor['total_revenue'] ?? 0,
                number_format($vendor['approval_rate'], 1)
            ]);
        }
        fputcsv($output, []);
        
        // Daily Trends
        fputcsv($output, ['Daily Trends']);
        fputcsv($output, ['Date', 'Orders', 'Revenue', 'Payments', 'Verified Payments']);
        foreach ($report['daily_trends'] as $trend) {
            fputcsv($output, [
                $trend['date'],
                $trend['order_count'],
                $trend['daily_revenue'],
                $trend['payment_count'],
                $trend['verified_payments']
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
    } elseif ($format === 'excel') {
        // For Excel, we'll return a simple message
        // In a real implementation, you'd use a library like PhpSpreadsheet
        http_response_code(501);
        echo json_encode(['error' => 'Excel export not yet implemented']);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid format']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
