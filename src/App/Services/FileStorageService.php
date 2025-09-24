<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Exceptions\HttpException;

class FileStorageService
{
    public function __construct(
        private string $uploadsDir,
        private string $publicDir
    ) {
    }

    public function storeUploadedFile(array $file, string $category): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new HttpException(400, 'File upload failed', ['file' => $this->errorMessage((int)$file['error'])]);
        }

        $tmpPath = $file['tmp_name'] ?? null;
        if (!$tmpPath || !is_uploaded_file($tmpPath)) {
            throw new HttpException(400, 'Invalid upload payload');
        }

        $extension = $this->detectExtension($tmpPath, (string)($file['name'] ?? ''));
        $relativeDir = trim($category, '/') . '/' . date('Y/m');
        $targetDir = rtrim($this->uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativeDir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . $extension;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            throw new HttpException(500, 'Unable to store uploaded file');
        }

        $relativePath = 'uploads/' . $relativeDir . '/' . $filename;

        return [
            'path' => $relativePath,
            'url' => '/' . $relativePath,
            'mime' => mime_content_type($targetPath) ?: 'application/octet-stream',
        ];
    }

    private function detectExtension(string $path, string $originalName): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
        ];

        if ($mime && isset($allowed[$mime])) {
            return $allowed[$mime];
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        }

        throw new HttpException(400, 'Unsupported file type');
    }

    private function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error',
        };
    }
}
