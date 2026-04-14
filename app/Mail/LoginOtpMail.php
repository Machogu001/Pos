<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;

    public ?User $user;

    public function __construct(string $otp, ?User $user = null)
    {
        $this->otp = $otp;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject(__('Your login OTP code'))
            ->view('emails.login_otp')
            ->with([
                'otp' => $this->otp,
                'user' => $this->user,
                'appName' => config('app.name'),
            ]);
    }
}
