<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;
use Exception;
use InvalidArgumentException;
use Cloudinary\Api\Admin\AdminApi;

class FileUploadService
{
    /**
     * Méthode générique pour uploader un fichier vers Cloudinary
     */
    private function uploadFile($file, string $folder, array $transformations = []): string
    {
        $this->validateFile($file);

        try {
            $cloudinary = Cloudinary::getFacadeRoot();

            $uploadResult = $cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'folder' => $folder,
                    'transformation' => $transformations
                ]
            );

            Log::info("Cloudinary upload result for {$folder}", ['uploadResult' => $uploadResult]);

            $secureUrl = $uploadResult['secure_url'] ?? null;

            if (!$secureUrl) {
                throw new Exception('L’upload Cloudinary a échoué ou n’a pas renvoyé de chemin sécurisé');
            }

            return $secureUrl;
        } catch (Exception $e) {
            Log::error("Erreur lors de l’upload vers {$folder}", [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);

            throw new Exception("Échec de l’upload vers {$folder}: " . $e->getMessage());
        }
    }

    /**
     * Upload d’un avatar utilisateur
     */
    public function uploadAvatar($file): string
    {
        return $this->uploadFile(
            $file,
            'hotel-app/avatars',
            ['width' => 200, 'height' => 200, 'crop' => 'fill', 'gravity' => 'face']
        );
    }

    /**
     * Upload d’une photo d’hôtel
     */

    public function uploadHotelPhoto($file): string
    {
        return $this->uploadFile(
            $file,
            'hotel-app/hotels',
            ['width' => 800, 'height' => 600, 'crop' => 'fill', 'quality' => 'auto']
        );
    }

    /**
     * Supprime un fichier Cloudinary
     */
    public function deleteFile($url): bool
    {
        try {
            if (empty($url)) return false;

            $publicId = $this->extractPublicIdFromUrl($url);
            if (!$publicId) return false;

            $adminApi = new AdminApi();
            $result = $adminApi->deleteAssets([$publicId]);

            $success = ($result['deleted'][$publicId] ?? null) === 'deleted';

            if ($success) {
                Log::info('Fichier supprimé de Cloudinary', ['public_id' => $publicId, 'url' => $url]);
            } else {
                Log::warning('Suppression Cloudinary non confirmée', ['public_id' => $publicId, 'result' => $result]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Erreur suppression Cloudinary', ['error' => $e->getMessage(), 'url' => $url]);
            return false;
        }
    }

    /**
     * Validation simple d’un fichier
     */
    private function validateFile($file, array $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif'], int $maxSizeMB = 10)
    {
        if (!$file || !$file->isValid()) {
            throw new InvalidArgumentException('Fichier invalide ou corrompu');
        }

        $maxSizeBytes = $maxSizeMB * 1024 * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            throw new InvalidArgumentException("Fichier trop volumineux, max {$maxSizeMB}MB");
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new InvalidArgumentException("Extension non autorisée: {$extension}");
        }
    }

    /**
     * Extrait le public_id depuis une URL Cloudinary
     */
    private function extractPublicIdFromUrl(string $url): ?string
    {
        $patterns = [
            '/\/v\d+\/(.+?)\.\w+$/',
            '/\/image\/upload\/v\d+\/(.+?)\.\w+$/',
            '/\/upload\/v\d+\/(.+?)\.\w+$/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        Log::warning('Impossible d’extraire le public_id', ['url' => $url]);
        return null;
    }

    /**
     * Vérifie que Cloudinary est correctement configuré
     */
    public function checkCloudinaryConfiguration(): array
    {
        try {
            $cloudName = config('cloudinary.cloud_name');
            $apiKey = config('cloudinary.api_key');
            $apiSecret = config('cloudinary.api_secret');

            if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
                throw new Exception('Configuration Cloudinary manquante');
            }

            Log::info('Cloudinary configuré', ['cloud_name' => $cloudName, 'api_key' => '***' . substr($apiKey, -4)]);

            return ['status' => 'success', 'message' => 'Cloudinary est correctement configuré'];
        } catch (Exception $e) {
            Log::error('Échec configuration Cloudinary', ['error' => $e->getMessage()]);
            throw new Exception('Erreur de configuration Cloudinary: ' . $e->getMessage());
        }
    }
}
