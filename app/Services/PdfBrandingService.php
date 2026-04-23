<?php

namespace App\Services;

use App\Models\ConfiguracaoModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class PdfBrandingService
{
    private const WATERMARK_CONFIG_KEY = 'pdf_logo_fundo';
    private const WHITE_THRESHOLD = 245;
    private const MAX_IMAGE_DIMENSION = 1400;

    private ConfiguracaoModel $configModel;
    private string $uploadRelativeDir = 'uploads/sistema/';
    private string $uploadAbsoluteDir;

    public function __construct()
    {
        $this->configModel = new ConfiguracaoModel();
        $this->uploadAbsoluteDir = rtrim(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $this->uploadRelativeDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($this->uploadAbsoluteDir)) {
            mkdir($this->uploadAbsoluteDir, 0775, true);
        }
    }

    public function getContext(): array
    {
        $watermarkFile = $this->ensureDefaultWatermarkLogo();
        $systemLogoFile = $this->resolveStoredFilePath((string) get_config('sistema_logo', ''));
        $watermarkLogoFile = $this->resolveStoredFilePath((string) ($watermarkFile ?? ''));

        return [
            'empresa_nome' => trim((string) get_config('empresa_nome', 'Assistencia Tecnica')),
            'empresa_cnpj' => trim((string) get_config('empresa_cnpj', '')),
            'empresa_telefone' => trim((string) get_config('empresa_telefone', '')),
            'empresa_email' => trim((string) get_config('empresa_email', '')),
            'empresa_endereco' => trim((string) get_config('empresa_endereco', '')),
            'header_logo_data_uri' => $this->buildDataUri($systemLogoFile),
            'watermark_logo_data_uri' => $this->buildDataUri($watermarkLogoFile),
        ];
    }

    public function ensureDefaultWatermarkLogo(bool $forceRefresh = false): ?string
    {
        $current = trim((string) get_config(self::WATERMARK_CONFIG_KEY, ''));
        if (!$forceRefresh && $this->isStoredFileAvailable($current)) {
            return basename($current);
        }

        $sourceName = trim((string) get_config('sistema_logo', ''));
        $sourcePath = $this->resolveStoredFilePath($sourceName);
        if ($sourcePath === null || !is_file($sourcePath)) {
            return null;
        }

        $targetName = 'pdf_logo_fundo_' . date('Ymd_His') . '.png';
        $targetPath = $this->uploadAbsoluteDir . $targetName;

        if (!$this->createTransparentPngCopy($sourcePath, $targetPath)) {
            return null;
        }

        if ($current !== '' && basename($current) !== basename($sourceName)) {
            $this->deleteStoredFile($current);
        }

        $this->configModel->setConfig(self::WATERMARK_CONFIG_KEY, $targetName);

        return $targetName;
    }

    public function handleUploadedWatermark(?UploadedFile $file): ?string
    {
        if ($file === null || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $extension = strtolower((string) $file->getExtension());
        if ($extension !== 'png') {
            throw new \InvalidArgumentException('A logo de fundo dos documentos deve ser um arquivo PNG sem fundo.');
        }

        $tempPath = $file->getTempName();
        if (!is_file($tempPath)) {
            throw new \RuntimeException('Arquivo temporario da logo de fundo nao encontrado.');
        }

        $targetName = 'pdf_logo_fundo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
        $targetPath = $this->uploadAbsoluteDir . $targetName;

        if (!$this->createTransparentPngCopy($tempPath, $targetPath)) {
            throw new \RuntimeException('Nao foi possivel preparar a logo de fundo dos documentos.');
        }

        $old = trim((string) get_config(self::WATERMARK_CONFIG_KEY, ''));
        if ($old !== '') {
            $this->deleteStoredFile($old);
        }

        $this->configModel->setConfig(self::WATERMARK_CONFIG_KEY, $targetName);

        return $targetName;
    }

    public function getStoredFileUrl(string $fileName): string
    {
        $safeName = basename(trim($fileName));
        if ($safeName === '') {
            return '';
        }

        return base_url($this->uploadRelativeDir . $safeName);
    }

    private function createTransparentPngCopy(string $sourcePath, string $targetPath): bool
    {
        $imageType = @exif_imagetype($sourcePath);
        if ($imageType === false) {
            return false;
        }

        $source = match ($imageType) {
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (!$source) {
            return false;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($source);
            return false;
        }

        [$targetWidth, $targetHeight] = $this->resolveTargetDimensions($sourceWidth, $sourceHeight);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $this->applyWhiteToTransparentMask($canvas);
        $saved = imagepng($canvas, $targetPath);

        imagedestroy($canvas);
        imagedestroy($source);

        return $saved && is_file($targetPath);
    }

    private function resolveTargetDimensions(int $width, int $height): array
    {
        $maxDimension = max($width, $height);
        if ($maxDimension <= self::MAX_IMAGE_DIMENSION) {
            return [$width, $height];
        }

        $scale = self::MAX_IMAGE_DIMENSION / $maxDimension;

        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }

    private function applyWhiteToTransparentMask(\GdImage $image): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                if ($alpha > 0) {
                    $color = imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
                    imagesetpixel($image, $x, $y, $color);
                    continue;
                }

                if (
                    $red >= self::WHITE_THRESHOLD
                    && $green >= self::WHITE_THRESHOLD
                    && $blue >= self::WHITE_THRESHOLD
                ) {
                    imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, 255, 255, 255, 127));
                }
            }
        }
    }

    private function buildDataUri(?string $absolutePath): string
    {
        if ($absolutePath === null || !is_file($absolutePath)) {
            return '';
        }

        $mime = mime_content_type($absolutePath);
        if (!is_string($mime) || trim($mime) === '') {
            $mime = 'image/png';
        }

        $content = @file_get_contents($absolutePath);
        if ($content === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function resolveStoredFilePath(string $fileName): ?string
    {
        $safeName = basename(trim($fileName));
        if ($safeName === '') {
            return null;
        }

        $path = $this->uploadAbsoluteDir . $safeName;

        return is_file($path) ? $path : null;
    }

    private function isStoredFileAvailable(string $fileName): bool
    {
        return $this->resolveStoredFilePath($fileName) !== null;
    }

    private function deleteStoredFile(string $fileName): void
    {
        $safeName = basename(trim($fileName));
        if ($safeName === '') {
            return;
        }

        $systemLogo = basename(trim((string) get_config('sistema_logo', '')));
        if ($safeName === $systemLogo) {
            return;
        }

        $path = $this->uploadAbsoluteDir . $safeName;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
