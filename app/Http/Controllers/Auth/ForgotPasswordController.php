<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Override to catch mail delivery exceptions so SMTP failures return a
     * friendly message instead of a 500 error page.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        try {
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );
        } catch (\Throwable $e) {
            Log::warning('Password reset email delivery failed', [
                'email'   => $request->input('email'),
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                $this->username() => __('We could not send the password reset email right now. Please try again later.'),
            ]);
        }

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Get the needed authentication credentials from the request.
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username());
    }

    /**
     * Map 'email' to the correct username field.
     */
    public function username()
    {
        return 'email';
    }
}
