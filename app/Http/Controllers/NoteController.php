<?php

namespace App\Http\Controllers;

use App\Models\UserNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    public function show(Request $request)
    {
        $note = UserNote::forUser($request->user()->id);

        return response()->json([
            'ok' => true,
            'contenu' => $note->contenu ?? '',
            'couleur' => $note->couleur ?? 'yellow',
            'updated_at' => optional($note->updated_at)?->toIso8601String(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'contenu' => ['nullable', 'string', 'max:200000'],
            'couleur' => ['nullable', 'string', 'max:20'],
        ]);

        $note = UserNote::forUser($request->user()->id);
        $note->contenu = $this->sanitizeHtml((string) ($data['contenu'] ?? ''));
        if (! empty($data['couleur'])) {
            $note->couleur = $data['couleur'];
        }
        $note->save();

        return response()->json([
            'ok' => true,
            'contenu' => $note->contenu,
            'couleur' => $note->couleur,
            'updated_at' => optional($note->updated_at)?->toIso8601String(),
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $path = $request->file('image')->store(
            'notes/'.$request->user()->id,
            'public'
        );

        return response()->json([
            'ok' => true,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    private function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><div><span><b><strong><i><em><u><s><strike><ul><ol><li><img>';
        $clean = strip_tags($html, $allowed);

        // Keep only safe attributes on img / span
        $clean = preg_replace_callback(
            '/<(img|span|div|p)(\s[^>]*)?>/i',
            function ($m) {
                $tag = strtolower($m[1]);
                $attrs = $m[2] ?? '';
                $kept = [];

                if ($tag === 'img') {
                    if (preg_match('/\ssrc=("|\')(https?:\/\/[^"\']+|\/[^"\']+)\1/i', $attrs, $src)) {
                        $kept[] = 'src='.$src[1].$src[2].$src[1];
                    }
                    if (preg_match('/\salt=("|\')([^"\']*)\1/i', $attrs, $alt)) {
                        $kept[] = 'alt='.$alt[1].e($alt[2]).$alt[1];
                    }
                    $kept[] = 'style="max-width:100%;height:auto;border-radius:4px;"';
                }

                if (preg_match('/\sstyle=("|\')([^"\']*)\1/i', $attrs, $style) && in_array($tag, ['span', 'div', 'p'], true)) {
                    $safe = preg_replace('/(expression|javascript|url)\s*\(/i', '', $style[2]);
                    $safe = preg_replace('/[^a-z0-9#%,.\s:;()-]/i', '', $safe ?? '');
                    if ($safe !== '') {
                        $kept[] = 'style='.$style[1].$safe.$style[1];
                    }
                }

                return '<'.$tag.(count($kept) ? ' '.implode(' ', $kept) : '').'>';
            },
            $clean
        );

        return trim($clean);
    }
}
