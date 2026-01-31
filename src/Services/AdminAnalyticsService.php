<?php

namespace RentalPlatform\Services;

use PDO;
use RentalPlatform\Database\Connection;

/**
 * Admin Analytics Service
 * 
 * Provides analytics and statistics for administrators
 */
class AdminAnalyticsService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Get platform overview statistics
     */
    public function getPlatformOverview(): array
    {
        $stats = [];

        // Total users by role
        $stmt = $this->db->query("
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role
        ");
        $usersByRole = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stats['total_users'] = array_sum($usersByRole);
        $stats['customers'] = $usersByRole['Customer'] ?? 0;
        $stats['vendors'] = $usersByRole['Vendor'] ?? 0;
        $stats['administrators'] = $usersByRole['Administrator'] ?? 0;

        // Vendor statistics
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM vendors 
            GROUP BY status
        ");
        $vendorsByStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stats['total_vendors'] = array_sum($vendorsByStatus);
        $stats['active_vendors'] = $vendorsByStatus['Active'] ?? 0;
        $stats['pending_vendors'] = $vendorsByStatus['Pending'] ?? 0;
        $stats['suspended_vendors'] = $vendorsByStatus['Suspended'] ?? 0;

        // Product statistics
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM products 
            GROUP BY status
        ");
        $productsByStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stats['total_products'] = array_sum($productsByStatus);
        $stats['active_products'] = $productsByStatus['Active'] ?? 0;
        $stats['inactive_products'] = $productsByStatus['Inactive'] ?? 0;

        // Order statistics
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM orders 
            GROUP BY status
        ");
        $ordersByStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stats['total_orders'] = array_sum($ordersByStatus);
        $stats['pending_orders'] = $ordersByStatus['Pending_Vendor_Approval'] ?? 0;
        $stats['active_orders'] = $ordersByStatus['Active_Rental'] ?? 0;
        $stats['completed_orders'] = $ordersByStatus['Completed'] ?? 0;

        // Payment statistics
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_payments,
                SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified_payments,
                SUM(CASE WHEN status = 'Failed' THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN status = 'Verified' THEN amount ELSE 0 END) as total_revenue
            FROM payments
        ");
        $paymentStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['total_payments'] = $paymentStats['total_payments'] ?? 0;
        $stats['verified_payments'] = $paymentStats['verified_payments'] ?? 0;
        $stats['failed_payments'] = $paymentStats['failed_payments'] ?? 0;
        $stats['total_revenue'] = $paymentStats['total_revenue'] ?? 0;

        // Refund statistics
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_refunds,
                SUM(amount) as total_refunded
            FROM refunds
        ");
        $refundStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['total_refunds'] = $refundStats['total_refunds'] ?? 0;
        $stats['total_refunded'] = $refundStats['total_refunded'] ?? 0;

        return $stats;
    }

    /**
     * Get order flow statistics
     */
    public function getOrderFlowStats(): array
    {
        $stats = [];

        // Orders by status with counts
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM orders 
            GROUP BY status
            ORDER BY count DESC
        ");
        $stats['orders_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Average time in each status (in hours)
        $stmt = $this->db->query("
            SELECT 
                status,
                AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours
            FROM orders
            WHERE status != 'Payment_Successful'
            GROUP BY status
        ");
        $stats['avg_time_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Orders pending approval for more than 24 hours
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM orders
            WHERE status = 'Pending_Vendor_Approval'
            AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > 24
        ");
        $stats['delayed_approvals'] = $stmt->fetchColumn();

        return $stats;
    }

    /**
     * Get vendor activity statistics
     */
    public function getVendorActivityStats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                v.id,
                v.business_name,
                COUNT(DISTINCT p.id) as product_count,
                COUNT(DISTINCT o.id) as order_count,
                SUM(CASE WHEN o.status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN o.status = 'Pending_Vendor_Approval' THEN 1 ELSE 0 END) as pending_orders
            FROM vendors v
            LEFT JOIN products p ON v.id = p.vendor_id
            LEFT JOIN orders o ON v.id = o.vendor_id
            WHERE v.status = 'Active'
            GROUP BY v.id, v.business_name
            ORDER BY order_count DESC
            LIMIT 10
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get payment trends (last 30 days)
     */
    public function getPaymentTrends(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as payment_count,
                SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified_count,
                SUM(CASE WHEN status = 'Failed' THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN status = 'Verified' THEN amount ELSE 0 END) as daily_revenue
            FROM payments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([':days' => $days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get rental trends (last 30 days)
     */
    public function getRentalTrends(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(CASE WHEN status = 'Active_Rental' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count
            FROM orders
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([':days' => $days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get refund frequency statistics
     */
    public function getRefundStats(): array
    {
        $stats = [];

        // Refunds by status
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM refunds 
            GROUP BY status
        ");
        $stats['refunds_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Refund rate
        $stmt = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM refunds) as total_refunds,
                (SELECT COUNT(*) FROM orders) as total_orders
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['refund_rate'] = $result['total_orders'] > 0 
            ? ($result['total_refunds'] / $result['total_orders']) * 100 
            : 0;

        // Recent refunds
        $stmt = $this->db->query("
            SELECT 
                r.*,
                o.order_number,
                u.username as customer_name
            FROM refunds r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON o.customer_id = u.id
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $stats['recent_refunds'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }
}

