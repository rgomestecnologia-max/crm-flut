<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\LandingPage;
use Illuminate\Support\Str;

class LandingPageViewController extends Controller
{
    public function show(string $companySlug, string $pageSlug)
    {
        // Busca empresa pelo slug do nome
        $company = Company::all()->first(fn($c) => Str::slug($c->name) === $companySlug);
        if (!$company) abort(404);

        $page = LandingPage::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('slug', $pageSlug)
            ->where('status', 'published')
            ->first();

        if (!$page) abort(404);

        // Incrementa views
        $page->increment('views_count');

        $sections = $page->sections()->where('visible', true)->orderBy('sort_order')->get();

        return view('landing-page.show', compact('page', 'sections', 'company'));
    }
}
