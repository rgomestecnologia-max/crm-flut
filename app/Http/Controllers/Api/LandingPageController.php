<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\LandingPageLead;
use App\Services\CurrentCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function saveLead(int $pageId, Request $request): JsonResponse
    {
        $page = LandingPage::withoutGlobalScopes()->find($pageId);
        if (!$page || $page->status !== 'published') {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $lead = LandingPageLead::withoutGlobalScopes()->create([
            'company_id'      => $page->company_id,
            'landing_page_id' => $page->id,
            'data'            => $request->input('data', []),
            'ip'              => $request->ip(),
            'user_agent'      => substr($request->userAgent() ?? '', 0, 255),
            'page_url'        => $request->input('page_url'),
            'utm_source'      => $request->input('utm_source'),
            'utm_medium'      => $request->input('utm_medium'),
            'utm_campaign'    => $request->input('utm_campaign'),
        ]);

        // Salva no menu Leads
        $data = $request->input('data', []);
        $phone = $data['whatsapp'] ?? $data['telefone'] ?? $data['phone'] ?? null;
        $name  = $data['nome'] ?? $data['name'] ?? null;
        $email = $data['email'] ?? null;

        if ($phone) {
            $phone = preg_replace('/\D/', '', $phone);
            if (strlen($phone) === 11) $phone = '55' . $phone;
            try {
                app(CurrentCompany::class)->set($page->company_id, persist: false);
                $bc = \App\Models\BroadcastContact::firstOrCreate(
                    ['company_id' => $page->company_id, 'phone' => $phone],
                    ['name' => $name, 'email' => $email, 'is_active' => true, 'tags' => ['landing-page']]
                );
                $tags = $bc->tags ?? [];
                if (!in_array('landing-page', $tags)) {
                    $tags[] = 'landing-page';
                    $bc->update(['tags' => $tags]);
                }

                // CRM card
                $pipeline = \App\Models\CrmPipeline::first();
                $stage = $pipeline?->stages()->orderBy('sort_order')->first();
                if ($pipeline && $stage) {
                    $contact = \App\Models\Contact::firstOrCreate(
                        ['company_id' => $page->company_id, 'phone' => $phone],
                        ['name' => $name, 'email' => $email]
                    );
                    if (!\App\Models\CrmCard::where('contact_id', $contact->id)->where('pipeline_id', $pipeline->id)->exists()) {
                        \App\Models\CrmCard::create([
                            'pipeline_id' => $pipeline->id,
                            'stage_id'    => $stage->id,
                            'contact_id'  => $contact->id,
                            'title'       => $name ?: $phone,
                        ]);
                    }
                }
            } catch (\Throwable) {}
        }

        // Email de notificação
        if ($page->notification_email) {
            try {
                \Illuminate\Support\Facades\Mail::to($page->notification_email)->send(
                    new \App\Mail\FlutChatLeadNotification($lead, 'Landing Page: ' . $page->title)
                );
            } catch (\Throwable) {}
        }

        return response()->json(['success' => true, 'redirect' => $page->thank_you_url]);
    }
}
