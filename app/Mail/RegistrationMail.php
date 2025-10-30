<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $business;
    public $user;
    public $registrationPrice;
    public $receipt;

    /**
     * Create a new message instance.
     */
    public function __construct($business, $user, $registrationPrice = 0, $receipt = null)
    {
        $this->business = $business;
        $this->user = $user;
        $this->registrationPrice = $registrationPrice;
        $this->receipt = $receipt;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject(__('business.business_created_succesfully'))
                    ->view('emails.registration')
                    ->with([
                        'business' => $this->business,
                        'user' => $this->user,
                        'registrationPrice' => $this->registrationPrice,
                        'receipt' => $this->receipt,
                    ]);
    }
}
