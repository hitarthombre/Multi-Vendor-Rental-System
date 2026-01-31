<?php

namespace RentalPlatform\Services;

/**
 * Image Upload Service
 * 
 * Handles image uploads, validation, optimization, and storage
 */
class ImageUploadService
{
    private string $uploadDir;
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    private int $maxFileSize = 5242880; // 5MB
    private int $maxWidth = 1920;
    private int $maxHeight = 1920;
    private int $thumbnailWidth = 400;
    private int $thumbnailHeight = 400;

    public function __construct(string $uploadDir = 'uploads/products')
    {
        $this->uploadDir = rtrim($uploadDir, '/');
        $this->ensureUploadDirExists();
    }

    /**
     * Ensure upload directory exists
     */
    private function ensureUploadDirExists(): void
    {
        $fullPath = __DIR__ . '/../../public/' . $this->uploadDir;
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        // Create thumbnails directory
        $thumbPath = $fullPath . '/thumbnails';
        if (!is_dir($thumbPath)) {
            mkdir($thumbPath, 0755, true);
        }
    }

    /**
     * Upload and process an image
     * 
     * @param array $file $_FILES array element
     * @return array ['success' => bool, 'filename' => string, 'thumbnail' => string, 'error' => string]
     */
    public function upload(array $file): array
    {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error']
            ];
        }

        try {
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $this->generateUniqueFilename($extension);
            
            $fullPath = __DIR__ . '/../../public/' . $this->uploadDir . '/' . $filename;
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = __DIR__ . '/../../public/' . $this->uploadDir . '/thumbnails/' . $thumbnailFilename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                return [
                    'success' => false,
                    'error' => 'Failed to move uploaded file'
                ];
            }

            // Optimize and resize image
            $this->optimizeImage($fullPath, $this->maxWidth, $this->maxHeight);
            
            // Create thumbnail
            $this->createThumbnail($fullPath, $thumbnailPath, $this->thumbnailWidth, $this->thumbnailHeight);

            return [
                'success' => true,
                'filename' => $filename,
                'thumbnail' => $thumbnailFilename,
                'path' => '/' . $this->uploadDir . '/' . $filename,
                'thumbnail_path' => '/' . $this->uploadDir . '/thumbnails/' . $thumbnailFilename
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate uploaded file
     * 
     * @param array $file
     * @return array ['valid' => bool, 'error' => string]
     */
    private function validateFile(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Allowed types: JPEG, PNG, WebP'
            ];
        }

        // Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'error' => 'File is not a valid image'
            ];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Get upload error message
     * 
     * @param int $errorCode
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Generate unique filename
     * 
     * @param string $extension
     * @return string
     */
    private function generateUniqueFilename(string $extension): string
    {
        return uniqid('img_', true) . '_' . time() . '.' . $extension;
    }

    /**
     * Optimize and resize image
     * 
     * @param string $filepath
     * @param int $maxWidth
     * @param int $maxHeight
     * @return bool
     */
    private function optimizeImage(string $filepath, int $maxWidth, int $maxHeight): bool
    {
        $imageInfo = getimagesize($filepath);
        if ($imageInfo === false) {
            return false;
        }

        list($width, $height, $type) = $imageInfo;

        // Load image based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($filepath);
                break;
            default:
                return false;
        }

        if ($image === false) {
            return false;
        }

        // Calculate new dimensions
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            // Create new image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
            }

            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        // Save optimized image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $filepath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $filepath, 8);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $filepath, 85);
                break;
        }

        imagedestroy($image);
        return true;
    }

    /**
     * Create thumbnail
     * 
     * @param string $sourcePath
     * @param string $destPath
     * @param int $width
     * @param int $height
     * @return bool
     */
    private function createThumbnail(string $sourcePath, string $destPath, int $width, int $height): bool
    {
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }

        list($srcWidth, $srcHeight, $type) = $imageInfo;

        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        if ($source === false) {
            return false;
        }

        // Calculate dimensions for crop (center crop)
        $ratio = max($width / $srcWidth, $height / $srcHeight);
        $newWidth = (int)($srcWidth * $ratio);
        $newHeight = (int)($srcHeight * $ratio);
        
        $cropX = (int)(($newWidth - $width) / 2);
        $cropY = (int)(($newHeight - $height) / 2);

        // Create thumbnail
        $temp = imagecreatetruecolor($newWidth, $newHeight);
        $thumbnail = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($temp, false);
            imagesavealpha($temp, true);
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        imagecopyresampled($temp, $source, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
        imagecopy($thumbnail, $temp, 0, 0, $cropX, $cropY, $width, $height);

        // Save thumbnail
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $destPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $destPath, 8);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumbnail, $destPath, 85);
                break;
        }

        imagedestroy($source);
        imagedestroy($temp);
        imagedestroy($thumbnail);

        return true;
    }

    /**
     * Delete image and its thumbnail
     * 
     * @param string $filename
     * @return bool
     */
    public function deleteImage(string $filename): bool
    {
        $fullPath = __DIR__ . '/../../public/' . $this->uploadDir . '/' . $filename;
        $thumbnailPath = __DIR__ . '/../../public/' . $this->uploadDir . '/thumbnails/thumb_' . $filename;

        $success = true;

        if (file_exists($fullPath)) {
            $success = $success && unlink($fullPath);
        }

        if (file_exists($thumbnailPath)) {
            $success = $success && unlink($thumbnailPath);
        }

        return $success;
    }

    /**
     * Get image URL
     * 
     * @param string $filename
     * @return string
     */
    public function getImageUrl(string $filename): string
    {
        return '/' . $this->uploadDir . '/' . $filename;
    }

    /**
     * Get thumbnail URL
     * 
     * @param string $filename
     * @return string
     */
    public function getThumbnailUrl(string $filename): string
    {
        return '/' . $this->uploadDir . '/thumbnails/thumb_' . $filename;
    }
}
