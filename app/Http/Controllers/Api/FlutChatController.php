<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlutChatLead;
use App\Models\FlutChatWidget;
use App\Services\CurrentCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlutChatController extends Controller
{
    public function config(string $publicId): JsonResponse
    {
        $widget = FlutChatWidget::withoutGlobalScopes()
            ->where('public_id', $publicId)
            ->where('is_active', true)
            ->first();

        if (!$widget) return response()->json(['error' => 'Widget not found'], 404);

        // Seta empresa para que os scopes funcionem
        app(CurrentCompany::class)->set($widget->company_id, persist: false);

        $flow = FlutChatWidget::find($widget->id)->activeFlow;
        $steps = $flow ? $flow->steps()->get(['id', 'type', 'content', 'input_key', 'input_placeholder', 'options', 'next_step_id', 'action_type', 'action_value', 'sort_order']) : [];

        return response()->json([
            'widget' => [
                'title'            => $widget->title,
                'subtitle'         => $widget->subtitle,
                'color'            => $widget->color,
                'logo_url'         => $widget->logo_url,
                'position'         => $widget->position,
                'whatsapp_number'  => $widget->whatsapp_number,
                'whatsapp_message' => $widget->whatsapp_message,
            ],
            'steps' => $steps,
        ]);
    }

    public function saveLead(string $publicId, Request $request): JsonResponse
    {
        $widget = FlutChatWidget::withoutGlobalScopes()
            ->where('public_id', $publicId)
            ->where('is_active', true)
            ->first();

        if (!$widget) return response()->json(['error' => 'Widget not found'], 404);

        $lead = FlutChatLead::withoutGlobalScopes()->create([
            'company_id'   => $widget->company_id,
            'widget_id'    => $widget->id,
            'data'         => $request->input('data', []),
            'action_taken' => $request->input('action'),
            'ip'           => $request->ip(),
            'user_agent'   => substr($request->userAgent() ?? '', 0, 255),
            'page_url'     => $request->input('page_url'),
        ]);

        // Se ação é lead, salva também no BroadcastContact (menu Leads)
        $data = $request->input('data', []);
        $phone = $data['telefone'] ?? $data['whatsapp'] ?? $data['phone'] ?? null;
        $name  = $data['nome'] ?? $data['name'] ?? null;
        if ($phone) {
            $phone = preg_replace('/\D/', '', $phone);
            if (strlen($phone) === 11) $phone = '55' . $phone;
            try {
                app(CurrentCompany::class)->set($widget->company_id, persist: false);
                \App\Models\BroadcastContact::firstOrCreate(
                    ['company_id' => $widget->company_id, 'phone' => $phone],
                    ['name' => $name, 'is_active' => true, 'tags' => ['flut-chat']]
                );
            } catch (\Throwable) {}
        }

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    public function aiChat(string $publicId, Request $request): JsonResponse
    {
        $widget = FlutChatWidget::withoutGlobalScopes()
            ->where('public_id', $publicId)
            ->where('is_active', true)
            ->first();

        if (!$widget) return response()->json(['error' => 'Widget not found'], 404);

        app(CurrentCompany::class)->set($widget->company_id, persist: false);

        $botConfig = \App\Models\AiBotConfig::current();
        if (!$botConfig || !$botConfig->is_active || !$botConfig->hasKey()) {
            return response()->json(['error' => 'AI not configured'], 400);
        }

        $model  = \App\Models\GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        $apiKey = \App\Models\GlobalSetting::get('gemini_api_key');

        $messages = $request->input('messages', []);

        // Monta system prompt completo (igual ProcessBotResponse)
        $systemPrompt = $botConfig->system_prompt ?: 'Você é um assistente virtual. Seja cordial e objetivo.';

        // Tom de voz
        if ($botConfig->voice_tones) {
            $tones = $botConfig->voice_tones;
            if (is_string($tones)) { $decoded = json_decode($tones, true); $tones = is_array($decoded) ? $decoded : array_map('trim', explode(',', $tones)); }
            if (!empty($tones)) { $systemPrompt .= "\n\n---\nTOM DE VOZ: " . implode(', ', $tones) . ".\nAdote esse tom em todas as respostas."; }
        }

        // Descrição da empresa
        if ($botConfig->company_description) {
            $systemPrompt .= "\n\n---\nSOBRE A EMPRESA:\n" . $botConfig->company_description;
        }

        // Conteúdo do site
        if ($botConfig->website_content) {
            $systemPrompt .= "\n\n---\nCONTEÚDO DO SITE DA EMPRESA:\n" . $botConfig->website_content;
        }

        // FAQ
        if ($botConfig->faq) {
            $systemPrompt .= "\n\n---\nPERGUNTAS FREQUENTES (FAQ):\n" . $botConfig->faq;
        }

        // Checklist
        if ($botConfig->checklist) {
            $systemPrompt .= "\n\n---\nCHECKLIST DE ATENDIMENTO:\n" . $botConfig->checklist;
        }

        // Catálogo de produtos
        $products = \App\Models\AiBotProduct::where('is_active', true)->orderBy('type')->orderBy('name')->get();
        if ($products->isNotEmpty()) {
            $systemPrompt .= "\n\n---\nCATÁLOGO DE PRODUTOS E SERVIÇOS:\n";
            foreach ($products as $product) { $systemPrompt .= $product->toPromptLine() . "\n"; }
        }

        // Data/hora atual
        $now = now()->timezone('America/Sao_Paulo');
        $days = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
        $systemPrompt .= "\n\n---\nDATA E HORA ATUAL: " . $days[$now->dayOfWeek] . ', ' . $now->format('d/m/Y H:i');

        $systemPrompt .= "\n\nIMPORTANTE: Você está atendendo via chat do site (Flut Chat), não via WhatsApp.";

        $geminiContents = [];
        foreach ($messages as $msg) {
            $geminiContents[] = [
                'role'  => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $response = Http::timeout(30)->post($url, [
                'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents'          => $geminiContents,
                'generationConfig'  => ['maxOutputTokens' => 1024, 'temperature' => 0.7],
            ]);

            $text = $response->json('candidates.0.content.parts.0.text') ?? 'Desculpe, não consegui processar sua mensagem.';

            return response()->json(['reply' => $text]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI error', 'message' => $e->getMessage()], 500);
        }
    }
}
