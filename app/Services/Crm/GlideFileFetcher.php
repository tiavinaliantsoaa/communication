<?php

namespace App\Services\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GlideFileFetcher
{
    public const MAX_BYTES = 40 * 1024 * 1024;

    /** @var list<string> */
    private const HOSTS = [
        'storage.googleapis.com',
        'storage.cloud.google.com',
    ];

    /**
     * @return array{path: string, mime: string, size: int, original_name: string}
     */
    public function store(string $url, int $candidateId, string $originalName): array
    {
        $this->assertUrl($url);

        $tmp = tempnam(sys_get_temp_dir(), 'glide');
        if ($tmp === false) {
            throw new RuntimeException('Fichier temporaire impossible.');
        }

        try {
            $response = Http::timeout(90)
                ->connectTimeout(15)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'protocols' => ['https'],
                        'on_redirect' => function ($request, $response, $uri): void {
                            $this->assertHost((string) $uri->getHost());
                        },
                    ],
                ])
                ->sink($tmp)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('Réponse HTTP '.$response->status().'.');
            }

            $size = filesize($tmp);
            if ($size === false || $size < 1) {
                throw new RuntimeException('Fichier vide.');
            }
            if ($size > self::MAX_BYTES) {
                throw new RuntimeException('Fichier trop volumineux.');
            }

            $extension = $this->extension($url, $originalName, (string) $response->header('Content-Type'));
            $mime = $this->mime($extension);
            $name = $this->fileName($originalName, $extension);
            $relative = 'crm/documents/'.$candidateId.'/'.Str::uuid().'.'.$extension;

            $stream = fopen($tmp, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Lecture du fichier impossible.');
            }
            try {
                Storage::disk('local')->writeStream($relative, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return [
                'path' => $relative,
                'mime' => $mime,
                'size' => (int) $size,
                'original_name' => $name,
            ];
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private function assertUrl(string $url): void
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ! isset($parts['host'])) {
            throw new RuntimeException('Adresse non autorisée.');
        }
        $this->assertHost((string) $parts['host']);
    }

    private function assertHost(string $host): void
    {
        if (! in_array(strtolower($host), self::HOSTS, true)) {
            throw new RuntimeException('Hôte non autorisé.');
        }
    }

    private function extension(string $url, string $originalName, string $contentType): string
    {
        $fromName = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $fromUrl = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $fromType = $this->extensionFromMime(strtolower(trim(strtok($contentType, ';') ?: '')));

        foreach ([$fromName, $fromUrl, $fromType] as $candidate) {
            if ($this->allowed($candidate)) {
                return $candidate === 'jpeg' ? 'jpg' : $candidate;
            }
        }

        throw new RuntimeException('Type de fichier non accepté.');
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/heic', 'image/heif' => 'heic',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            default => '',
        };
    }

    private function allowed(string $extension): bool
    {
        return in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'zip', 'doc', 'docx', 'xls', 'xlsx'], true);
    }

    private function mime(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'heic', 'heif' => 'image/heic',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }

    private function fileName(string $originalName, string $extension): string
    {
        $name = trim(str_replace(["\r", "\n"], '', $originalName));
        if ($name === '') {
            $name = 'document.'.$extension;
        }
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === '') {
            $name .= '.'.$extension;
        }

        return mb_substr($name, 0, 255);
    }
}
