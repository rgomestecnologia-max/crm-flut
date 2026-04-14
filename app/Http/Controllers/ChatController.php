<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __invoke(?Conversation $conversation = null): View|RedirectResponse
    {
        $user = Auth::user();

        if ($conversation && !$user->isAdmin() && !$user->belongsToDepartment((int) $conversation->department_id)) {
            abort(403);
        }

        return view('chat.index', ['activeConversation' => $conversation]);
    }
}
