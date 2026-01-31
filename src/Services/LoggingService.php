<?php

namespace RentalPlatform\Services;

use Exception;
use DateTime;

/**
 * Logging Service
 * 
 * Centralized logging service for the application
 */
class LoggingService
{
    private array $config;
    private string $basePath;
    
    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/logging.php';
        $this->basePath = __DIR__ . '/../../';
    }
    
    /**
     * Log an emergency message
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }
    
    /**
     * Log an alert message
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }
    
    /**
     * Log a critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }
    
    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Log a notice message
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }
    
    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
    
    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Log a security event
     */
    public function security(string $event, array $context = []): void
    {
        $context['security_event'] = $event;
        $context['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $this->logToFile('security', 'warning', "Security Event: $event", $context);
    }
    
    /**
     * Log a payment event
     */
    public function payment(string $event, array $context = []): void
    {
        $context['payment_event'] = $event;
        $this->logToFile('payment', 'info', "Payment Event: $event", $context);
    }
    
    /**
     * Log an email event
     */
    public function email(string $event, array $context = []): void
    {
        $context['email_event'] = $event;
        $this->logToFile('email', 'info', "Email Event: $event", $context);
    }
    
    /**
     * Log an audit event
     */
    public function audit(string $action, string $entityType, string $entityId, string $actorId, array $context = []): void
    {
        $auditContext = array_merge($context, [
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'actor_id' => $actorId,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        
        $this->logToFile('audit', 'info', "Audit: $action on $entityType $entityId by $actorId", $auditContext);
    }
    
    /**
     * Log performance metrics
     */
    public function performance(string $operation, float $duration, int $memoryUsage, array $context = []): void
    {
        if (!$this->config['performance']['enabled']) {
            return;
        }
        
        $perfContext = array_merge($context, [
            'operation' => $operation,
            'duration' => $duration,
            'memory_usage' => $memoryUsage,
            'is_slow' => $duration > $this->config['performance']['slow_query_threshold'],
            'high_memory' => $memoryUsage > $this->config['performance']['memory_threshold'],
        ]);
        
        $level = ($duration > $this->config['performance']['slow_query_threshold']) ? 'warning' : 'info';
        $this->logToFile('performance', $level, "Performance: $operation took {$duration}s", $perfContext);
    }
    
    /**
     * Main logging method
     */
    private function log(string $level, string $message, array $context = []): void
    {
        // Determine which log file to use
        $logFile = $this->determineLogFile($level);
        $this->logToFile($logFile, $level, $message, $context);
    }
    
    /**
     * Log to specific file
     */
    private function logToFile(string $logFile, string $level, string $message, array $context = []): void
    {
        try {
            $config = $this->config['files'][$logFile] ?? $this->config['files']['application'];
            
            // Check if level should be logged
            if ($this->config['levels'][$level] > $this->config['levels'][$config['level']]) {
                return;
            }
            
            $logPath = $this->basePath . $config['path'];
            
            // Ensure log directory exists
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            // Check file size and rotate if necessary
            if (file_exists($logPath) && filesize($logPath) > $config['max_size']) {
                $this->rotateLogFile($logPath, $config);
            }
            
            // Format log entry
            $logEntry = $this->formatLogEntry($level, $message, $context);
            
            // Write to log file
            file_put_contents($logPath, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
            
            // Check for critical errors and send alerts
            if (in_array($level, ['emergency', 'alert', 'critical']) && $this->config['monitoring']['alert_on_critical']) {
                $this->sendCriticalAlert($level, $message, $context);
            }
            
        } catch (Exception $e) {
            // Fallback to error_log if our logging fails
            error_log("Logging service error: " . $e->getMessage());
            error_log("Original message: [$level] $message");
        }
    }
    
    /**
     * Format log entry
     */
    private function formatLogEntry(string $level, string $message, array $context = []): string
    {
        $timestamp = date($this->config['format']['timestamp_format']);
        $requestId = $this->getRequestId();
        
        $contextString = empty($context) ? '' : json_encode($context);
        
        $formatted = str_replace(
            ['{timestamp}', '{level}', '{message}', '{context}'],
            [$timestamp, strtoupper($level), $message, $contextString],
            $this->config['format']['message_format']
        );
        
        if ($this->config['format']['include_request_id']) {
            $formatted = "[$requestId] $formatted";
        }
        
        return $formatted;
    }
    
    /**
     * Determine which log file to use based on level
     */
    private function determineLogFile(string $level): string
    {
        if (in_array($level, ['emergency', 'alert', 'critical', 'error'])) {
            return 'error';
        }
        
        return 'application';
    }
    
    /**
     * Rotate log file
     */
    private function rotateLogFile(string $logPath, array $config): void
    {
        if (!$config['rotate']) {
            return;
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $rotatedPath = $logPath . '.' . $timestamp;
        
        if ($this->config['rotation']['compress']) {
            $rotatedPath .= '.gz';
            $content = file_get_contents($logPath);
            file_put_contents($rotatedPath, gzencode($content));
        } else {
            rename($logPath, $rotatedPath);
        }
        
        // Clean up old log files
        $this->cleanupOldLogs(dirname($logPath), basename($logPath), $config['keep_files']);
    }
    
    /**
     * Clean up old log files
     */
    private function cleanupOldLogs(string $logDir, string $logBasename, int $keepFiles): void
    {
        $pattern = $logDir . '/' . $logBasename . '.*';
        $files = glob($pattern);
        
        if (count($files) > $keepFiles) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $filesToRemove = array_slice($files, 0, count($files) - $keepFiles);
            foreach ($filesToRemove as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Send critical alert
     */
    private function sendCriticalAlert(string $level, string $message, array $context): void
    {
        try {
            $alertEmail = $this->config['monitoring']['alert_email'];
            $subject = "CRITICAL ALERT: $level - " . substr($message, 0, 50);
            $body = "Critical error detected:\n\nLevel: $level\nMessage: $message\nContext: " . json_encode($context, JSON_PRETTY_PRINT);
            
            // Use mail() function for simplicity (in production, use proper email service)
            mail($alertEmail, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Failed to send critical alert: " . $e->getMessage());
        }
    }
    
    /**
     * Get or generate request ID
     */
    private function getRequestId(): string
    {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        }
        
        return $requestId;
    }
    
    /**
     * Get log statistics
     */
    public function getLogStatistics(): array
    {
        $stats = [];
        
        foreach ($this->config['files'] as $name => $config) {
            $logPath = $this->basePath . $config['path'];
            
            if (file_exists($logPath)) {
                $stats[$name] = [
                    'size' => filesize($logPath),
                    'size_mb' => round(filesize($logPath) / 1024 / 1024, 2),
                    'modified' => date('Y-m-d H:i:s', filemtime($logPath)),
                    'lines' => $this->countLogLines($logPath),
                ];
            } else {
                $stats[$name] = [
                    'size' => 0,
                    'size_mb' => 0,
                    'modified' => 'Never',
                    'lines' => 0,
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Count lines in log file
     */
    private function countLogLines(string $filePath): int
    {
        $count = 0;
        $handle = fopen($filePath, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $count++;
            }
            fclose($handle);
        }
        
        return $count;
    }
}