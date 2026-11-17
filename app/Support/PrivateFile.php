<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFile
{
    public static function delete(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            Storage::disk($disk)->delete($path);
        }
    }

    public static function download(string $path, string $name): StreamedResponse
    {
        return self::stream($path, $name, true);
    }

    public static function inline(string $path, string $name): StreamedResponse
    {
        return self::stream($path, $name, false);
    }

    /**
     * Contenu encodé en texte, sans nom de fichier ni type PDF.
     * Un aperçu servi en PDF est intercepté comme un téléchargement.
     */
    public static function raw(string $path): Response
    {
        foreach (['local', 'public'] as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            return response(base64_encode(Storage::disk($disk)->get($path)), 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        abort(404);
    }

    private static function stream(string $path, string $name, bool $download): StreamedResponse
    {
        $name = str_replace(["\r", "\n", '"'], '', basename($name)) ?: 'fichier';

        foreach (['local', 'public'] as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            if ($download) {
                return Storage::disk($disk)->download($path, $name);
            }

            return Storage::disk($disk)->response($path, $name, [
                'Content-Disposition' => 'inline; filename="'.$name.'"',
            ]);
        }

        abort(404);
    }
}
