<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\Payment;
use RentalPlatform\Models\Refund;
use RentalPlatform\Repositories\PaymentRepository;
use RentalPlatform\Repositories\RefundRepository;

/**
 * RazorpayService
 * 
 * Handles Razorpay payment integration
 * Note: This is a simplified implementation for the MVP
 * In production, use the official Razorpay PHP SDK
 */
class RazorpayService
{
    private string $keyId;
    private string $keySecret;
    private PaymentRepository $paymentRepo;
    private RefundRepository $refundRepo;

    public function __construct(
        string $keyId,
        string $keySecret,
        ?PaymentRepository $paymentRepo = null,
        ?RefundRepository $refundRepo = null
    ) {
        $this->keyId = $keyId;
        $this->keySecret = $keySecret;
        $this->paymentRepo = $paymentRepo ?? new PaymentRepository();
        $this->refundRepo = $refundRepo ?? new RefundRepository();
    }

    /**
     * Create a payment order
     * 
     * @param float $amount Amount in INR
     * @param string $customerId Customer ID
     * @param array $metadata Additional metadata
     * @return Payment
     */
    public function createPaymentOrder(float $amount, string $customerId, array $metadata = []): Payment
    {
        // In production, call Razorpay API to create order
        // For MVP, we'll simulate this
        
        $razorpayOrderId = 'order_' . uniqid();
        
        $payment = Payment::create(
            $razorpayOrderId,
            $amount,
            $customerId,
            $metadata
        );
        
        $this->paymentRepo->create($payment);
        
        return $payment;
    }

    /**
     * Verify payment signature
     * 
     * @param string $razorpayOrderId
     * @param string $razorpayPaymentId
     * @param string $razorpaySignature
     * @return bool
     */
    public function verifyPaymentSignature(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): bool {
        // Generate expected signature
        $expectedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . '|' . $razorpayPaymentId,
            $this->keySecret
        );
        
        // Compare signatures
        return hash_equals($expectedSignature, $razorpaySignature);
    }

    /**
     * Verify and capture payment
     * 
     * @param string $razorpayOrderId
     * @param string $razorpayPaymentId
     * @param string $razorpaySignature
     * @return Payment|null
     */
    public function verifyAndCapturePayment(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): ?Payment {
        // Find payment by Razorpay order ID
        $payment = $this->paymentRepo->findByRazorpayOrderId($razorpayOrderId);
        
        if (!$payment) {
            return null;
        }
        
        // Verify signature
        if (!$this->verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
            $payment->fail();
            $this->paymentRepo->update($payment);
            return null;
        }
        
        // Mark payment as verified
        $payment->verify($razorpayPaymentId, $razorpaySignature);
        $this->paymentRepo->update($payment);
        
        return $payment;
    }

    /**
     * Initiate refund
     * 
     * @param string $paymentId Payment ID
     * @param string $orderId Order ID
     * @param float $amount Amount to refund
     * @param string $reason Refund reason
     * @return Refund
     */
    public function initiateRefund(
        string $paymentId,
        string $orderId,
        float $amount,
        string $reason
    ): Refund {
        // Create refund record
        $refund = Refund::create($paymentId, $orderId, $amount, $reason);
        $this->refundRepo->create($refund);
        
        // In production, call Razorpay API to process refund
        // For MVP, we'll simulate this
        try {
            $razorpayRefundId = 'rfnd_' . uniqid();
            $refund->markProcessing($razorpayRefundId);
            $this->refundRepo->update($refund);
            
            // Simulate successful refund
            $refund->complete();
            $this->refundRepo->update($refund);
        } catch (\Exception $e) {
            $refund->fail();
            $this->refundRepo->update($refund);
        }
        
        return $refund;
    }

    /**
     * Get payment by ID
     */
    public function getPayment(string $paymentId): ?Payment
    {
        return $this->paymentRepo->findById($paymentId);
    }

    /**
     * Get refund by ID
     */
    public function getRefund(string $refundId): ?Refund
    {
        return $this->refundRepo->findById($refundId);
    }

    /**
     * Get Razorpay key ID (for frontend)
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }
}
