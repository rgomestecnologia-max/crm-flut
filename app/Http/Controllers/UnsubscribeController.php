<?php

namespace App\Http\Controllers;

use App\Models\BroadcastContact;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(string $token)
    {
        $data = $this->decodeToken($token);
        if (!$data) {
            return response('Link inválido.', 404);
        }

        return view('unsubscribe', [
            'token' => $token,
            'email' => $data['email'],
        ]);
    }

    public function process(Request $request, string $token)
    {
        $data = $this->decodeToken($token);
        if (!$data) {
            return response('Link inválido.', 404);
        }

        $reason = $request->input('reason', 'Não informado');

        $contact = BroadcastContact::withoutGlobalScopes()
            ->where('email', $data['email'])
            ->where('company_id', $data['company_id'])
            ->first();

        if ($contact) {
            $tags = $contact->tags ?? [];
            if (!in_array('unsubscribe', $tags)) {
                $tags[] = 'unsubscribe';
                $tags[] = 'unsub:' . \Illuminate\Support\Str::slug($reason);
            }
            $contact->update(['tags' => $tags]);
        }

        return view('unsubscribe', [
            'token'     => $token,
            'email'     => $data['email'],
            'confirmed' => true,
        ]);
    }

    private function decodeToken(string $token): ?array
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            if (count($parts) !== 2) return null;
            return ['email' => $parts[0], 'company_id' => (int) $parts[1]];
        } catch (\Throwable) {
            return null;
        }
    }

    public static function generateToken(string $email, int $companyId): string
    {
        return base64_encode("{$email}|{$companyId}");
    }
}
