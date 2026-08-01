<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Notifications\ContactFormSubmitted;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    // お問い合わせフォーム送信処理
    public function store(ContactRequest $request)
    {
        Notification::route('mail', config('mail.contact_notify_address'))
            ->notify(new ContactFormSubmitted(
                $request->validated('name'),
                $request->validated('email'),
                $request->validated('message'),
            ));

        return response()->json([
            'status_code' => 200,
            'message' => 'お問い合わせを受け付けました',
        ]);
    }
}
