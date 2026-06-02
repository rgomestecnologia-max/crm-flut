<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\LandingPage;
use Illuminate\Support\Str;

class LandingPageViewController extends Controller
{
    public function show(string $companySlug, string $pageSlug)
    {
        $company = Company::all()->first(fn($c) => Str::slug($c->name) === $companySlug);
        if (!$company) abort(404);

        $isPreview = request()->query('preview') === '1';

        $query = LandingPage::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('slug', $pageSlug);

        if (!$isPreview) {
            $query->where('status', 'published');
        }

        $page = $query->first();
        if (!$page) abort(404);

        // Só conta views em páginas publicadas (não preview)
        if (!$isPreview) {
            $page->increment('views_count');
        }

        $sections = $page->sections()->where('visible', true)->orderBy('sort_order')->get();

        return view('landing-page.show', compact('page', 'sections', 'company', 'isPreview'));
    }
}
