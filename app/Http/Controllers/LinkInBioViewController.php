<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\LinkInBioPage;
use Illuminate\Http\Request;

class LinkInBioViewController extends Controller
{
    public function show(string $companySlug, string $pageSlug, Request $request)
    {
        $page = LinkInBioPage::withoutGlobalScopes()
            ->where('slug', $pageSlug)
            ->first();

        if (!$page) abort(404);

        $isPreview = $request->query('preview') === '1';
        if ($page->status !== 'published' && !$isPreview) abort(404);

        if ($page->status === 'published' && !$isPreview) {
            $page->increment('views_count');
        }

        $links = $page->links()->where('is_active', true)->orderBy('sort_order')->get();
        $theme = $page->theme ?? LinkInBioPage::THEMES['dark'];

        return view('link-in-bio.show', compact('page', 'links', 'theme', 'isPreview'));
    }

    public function trackClick(int $linkId)
    {
        $link = \App\Models\LinkInBioLink::find($linkId);
        if ($link) {
            $link->increment('clicks_count');
            return response()->json(['ok' => true]);
        }
        return response()->json(['ok' => false], 404);
    }
}
