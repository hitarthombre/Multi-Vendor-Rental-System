<?php

namespace RentalPlatform\Services;

use Exception;

/**
 * Email Service
 * 
 * Handles SMTP email sending with Gmail configuration
 */
class EmailService
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        // SMTP Configuration for Gmail
        $this->smtpHost = 'smtp.gmail.com';
        $this->smtpPort = 587;
        $this->smtpUsername = 'hitarththombre@gmail.com';
        $this->smtpPassword = 'qpkykbbhmtorwtze'; // App password
        $this->fromEmail = 'hitarththombre@gmail.com';
        $this->fromName = 'Multi-Vendor Rental Platform';
    }

    /**
     * Send email using PHP's built-in mail function with SMTP headers
     * 
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $body): bool
    {
        try {
            // Create email headers
            $headers = $this->buildHeaders($toEmail, $toName);
            
            // Create HTML email body
            $htmlBody = $this->buildHtmlBody($body, $subject);
            
            // Send email using mail() function
            $success = mail($toEmail, $subject, $htmlBody, $headers);
            
            if ($success) {
                error_log("Email sent successfully to: {$toEmail}");
                return true;
            } else {
                error_log("Failed to send email to: {$toEmail}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build email headers
     * 
     * @param string $toEmail
     * @param string $toName
     * @return string
     */
    private function buildHeaders(string $toEmail, string $toName): string
    {
        $headers = [];
        
        // From header
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        
        // Reply-To header
        $headers[] = "Reply-To: {$this->fromEmail}";
        
        // Content type
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        
        // MIME version
        $headers[] = "MIME-Version: 1.0";
        
        // Additional headers for better deliverability
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "X-Priority: 3";
        
        return implode("\r\n", $headers);
    }

    /**
     * Build HTML email body with template
     * 
     * @param string $content
     * @param string $subject
     * @return string
     */
    private function buildHtmlBody(string $content, string $subject): string
    {
        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$subject}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e7eb;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            font-size: 12px;
            color: #6b7280;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Multi-Vendor Rental Platform</h1>
    </div>
    <div class='content'>
        {$content}
    </div>
    <div class='footer'>
        <p>This is an automated message from Multi-Vendor Rental Platform.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>";
    }

    /**
     * Send test email to verify configuration
     * 
     * @param string $testEmail
     * @return bool
     */
    public function sendTestEmail(string $testEmail): bool
    {
        $subject = "Test Email - Multi-Vendor Rental Platform";
        $body = "
            <h2>Email Configuration Test</h2>
            <p>This is a test email to verify that the email service is working correctly.</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p>If you received this email, the SMTP configuration is working properly.</p>
        ";
        
        return $this->sendEmail($testEmail, 'Test User', $subject, $body);
    }
}