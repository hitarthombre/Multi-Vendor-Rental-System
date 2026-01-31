<?php
/**
 * Razorpay Webhook Handler
 * 
 * Handles webhook notifications from Razorpay
 * Requirements: 23.4
 */

header('Content-Type: application/json');

require_once '../../../config/razorpay.php';
require_once '../../../src/Services/PaymentService.php';

use RentalPlatform\Services\PaymentService;

// Get webhook configuration
$config = require '../../../config/razorpay.php';
$webhookSecret = $config[$config['environment']]['webhook_secret'];

try {
    // Get raw POST data
    $payload = file_get_contents('php://input');
    $headers = getallheaders();
    
    // Verify webhook signature if secret is configured
    if (!empty($webhookSecret)) {
        $signature = $headers['X-Razorpay-Signature'] ?? '';
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }
    
    // Parse webhook data
    $data = json_decode($payload, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }
    
    // Log webhook for debugging
    error_log("Razorpay Webhook: " . json_encode($data));
    
    $paymentService = new PaymentService();
    
    // Handle different webhook events
    switch ($data['event']) {
        case 'payment.captured':
            $paymentService->handlePaymentCaptured($data['payload']['payment']['entity']);
            break;
            
        case 'payment.failed':
            $paymentService->handlePaymentFailed($data['payload']['payment']['entity']);
            break;
            
        case 'refund.created':
            $paymentService->handleRefundCreated($data['payload']['refund']['entity']);
            break;
            
        case 'refund.processed':
            $paymentService->handleRefundProcessed($data['payload']['refund']['entity']);
            break;
            
        default:
            error_log("Unhandled Razorpay webhook event: " . $data['event']);
            break;
    }
    
    // Acknowledge webhook
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Razorpay webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}