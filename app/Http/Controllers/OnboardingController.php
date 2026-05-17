<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OnboardingController extends Controller
{
    public function show()
    {
        return view('onboarding');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'company_name'       => 'required|string|max:200',
            'cnpj'               => 'nullable|string|max:20',
            'segment'            => 'nullable|string|max:100',
            'website'            => 'nullable|string|max:300',
            'social_media'       => 'nullable|string|max:500',
            'brand_color'        => 'nullable|string|max:9',
            'logo'               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'whatsapp_number'    => 'nullable|string|max:20',
            'has_whatsapp_business' => 'nullable|string',
            'agents_count'       => 'nullable|integer|min:1|max:100',
            'departments'        => 'nullable|string|max:2000',
            'department_leads'   => 'nullable|string|max:2000',
            'sales_pipeline'     => 'nullable|string|max:2000',
            'custom_fields'      => 'nullable|string|max:2000',
            'company_description'=> 'nullable|string|max:5000',
            'voice_tone'         => 'nullable|string|max:500',
            'business_hours'     => 'nullable|string|max:500',
            'faq'                => 'nullable|string|max:5000',
            'checklist'          => 'nullable|string|max:5000',
            'site_for_ai'        => 'nullable|string|max:300',
            'has_catalog'        => 'nullable|string',
            'catalog_files.*'    => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:20480',
            'has_site_leads'     => 'nullable|string',
            'auto_message'       => 'nullable|string|max:2000',
            'want_followup'      => 'nullable|string',
            'contact_name'       => 'nullable|string|max:200',
            'contact_email'      => 'nullable|email|max:200',
            'contact_phone'      => 'nullable|string|max:20',
            'notes'              => 'nullable|string|max:5000',
        ]);

        $data = $request->except(['logo', 'catalog_files', '_token']);

        // Upload logo
        if ($request->hasFile('logo')) {
            $data['logo_path'] = \App\Services\MediaStorage::store($request->file('logo'), 'onboarding');
        }

        // Upload catálogos
        $catalogPaths = [];
        if ($request->hasFile('catalog_files')) {
            foreach ($request->file('catalog_files') as $file) {
                $catalogPaths[] = \App\Services\MediaStorage::store($file, 'onboarding/catalogs');
            }
        }
        $data['catalog_paths'] = $catalogPaths;
        $data['submitted_at'] = now()->format('Y-m-d H:i:s');

        // Salva como JSON
        $filename = 'onboarding/' . \Illuminate\Support\Str::slug($data['company_name']) . '-' . now()->format('Ymd-His') . '.json';
        \App\Services\MediaStorage::put($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Log::info('Onboarding recebido', ['company' => $data['company_name']]);

        return view('onboarding', ['submitted' => true, 'companyName' => $data['company_name']]);
    }
}
