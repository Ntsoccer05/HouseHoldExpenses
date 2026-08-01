<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactFormSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $body,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('【カケポン】お問い合わせを受信しました')
            ->replyTo($this->senderEmail, $this->senderName)
            ->line('お問い合わせフォームより新しいメッセージが届きました。')
            ->line('お名前: '.$this->senderName)
            ->line('メールアドレス: '.$this->senderEmail)
            ->line('お問い合わせ内容:')
            ->line($this->body);
    }
}
