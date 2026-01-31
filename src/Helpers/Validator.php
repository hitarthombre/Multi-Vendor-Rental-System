<?php

namespace RentalPlatform\Helpers;

/**
 * Input Validation and Sanitization Helper
 * 
 * Provides comprehensive input validation and sanitization
 * to prevent security vulnerabilities
 */
class Validator
{
    /**
     * Validate and sanitize string input
     */
    public static function sanitizeString(string $input, int $maxLength = 255): string
    {
        $sanitized = trim($input);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        if (strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }
        
        return $sanitized;
    }

    /**
     * Validate email address
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Sanitize email address
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Validate integer
     */
    public static function validateInt($value, int $min = null, int $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            return false;
        }
        
        if ($max !== null && $intValue > $max) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate float/decimal
     */
    public static function validateFloat($value, float $min = null, float $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $floatValue = (float)$value;
        
        if ($min !== null && $floatValue < $min) {
            return false;
        }
        
        if ($max !== null && $floatValue > $max) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate UUID format
     */
    public static function validateUUID(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    public static function validateDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate datetime format (YYYY-MM-DD HH:MM:SS)
     */
    public static function validateDateTime(string $datetime): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }

    /**
     * Validate URL
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Sanitize URL
     */
    public static function sanitizeUrl(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Validate phone number (basic validation)
     */
    public static function validatePhone(string $phone): bool
    {
        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)]+/', '', $phone);
        // Check if it's 10-15 digits
        return preg_match('/^\+?[0-9]{10,15}$/', $cleaned) === 1;
    }

    /**
     * Validate required fields
     */
    public static function validateRequired(array $data, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = "Field '$field' is required";
            }
        }
        
        return $errors;
    }

    /**
     * Validate enum value
     */
    public static function validateEnum($value, array $allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }

    /**
     * Sanitize filename for safe storage
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove special characters except dots, dashes, and underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
            $filename = $name . '.' . $extension;
        }
        
        return $filename;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array
    {
        $errors = [];
        
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File exceeds maximum size';
                return $errors;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file uploaded';
                return $errors;
            default:
                $errors[] = 'Unknown upload error';
                return $errors;
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File exceeds maximum size of ' . ($maxSize / 1024 / 1024) . 'MB';
        }
        
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
        }
        
        return $errors;
    }

    /**
     * Prevent XSS by escaping output
     */
    public static function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape for JavaScript context
     */
    public static function escapeJs(string $text): string
    {
        return json_encode($text, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Validate password strength
     */
    public static function validatePasswordStrength(string $password, int $minLength = 8): array
    {
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least $minLength characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
}
