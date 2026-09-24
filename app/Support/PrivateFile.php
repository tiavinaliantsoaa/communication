<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
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
        $name = str_replace(["\r", "\n", '"'], '', basename($name)) ?: 'fichier';

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->download($path, $name);
            }
        }

        abort(404);
    }
}
