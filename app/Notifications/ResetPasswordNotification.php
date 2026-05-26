<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu hệ thống bệnh viện')
            ->greeting('Xin chào '.$notifiable->name)
            ->line('Bạn nhận được email này vì đã yêu cầu đặt lại mật khẩu cho tài khoản bệnh viện.')
            ->line('Liên kết đặt lại mật khẩu: '.$url)
            ->line("Liên kết đặt lại mật khẩu sẽ hết hạn sau {$expire} phút.")
            ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.')
            ->salutation('Trân trọng, Hệ thống bệnh viện');
    }
}
