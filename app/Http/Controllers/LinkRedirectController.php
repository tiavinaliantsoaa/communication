<?php

namespace App\Http\Controllers;

use App\Models\TrackedLink;
use App\Services\VisitorInfoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LinkRedirectController extends Controller
{
    public function __invoke(Request $request, string $slug, VisitorInfoService $visitorInfo)
    {
        $link = TrackedLink::query()
            ->where('slug', $slug)
            ->where('actif', true)
            ->first();

        if (! $link) {
            abort(404);
        }

        $info = $visitorInfo->fromRequest($request);

        try {
            DB::transaction(function () use ($link, $info) {
                $link->visits()->create($info);
                TrackedLink::whereKey($link->id)->increment('clicks_count');
            });
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->away($link->destination_url, 302);
    }
}
