<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class UserCustomVerifyEmail extends VerifyEmailBase
{
    /**
     * メールアドレスの確認URLを生成
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            //ここに名前付きルートを記述
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }

    /**
     * メールアドレスの確認メールを送信
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('メールアドレスの確認')
            ->markdown('auth.emails.verify_email', [
                'verify_url' => $verificationUrl
            ]);
    }
}