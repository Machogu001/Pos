<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Services\MobileLoginService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Lets the mobile app open web screens (e.g. the full POS) in an in-app browser without a second
 * password + OTP sign-in. The app exchanges its bearer token for a single-use link that expires
 * after a minute; opening the link starts a normal web session for the same user.
 */
class WebSessionController extends BaseMobileController
{
    protected const TTL_SECONDS = 60;

    protected const TARGETS = [
        'pos' => '/pos/create',
        'home' => '/home',
    ];

    public function __construct(protected MobileLoginService $loginService)
    {
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'target' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::TARGETS))],
        ]);

        try {
            $user = $request->user();
            $target = $data['target'] ?? 'pos';
            if ($target === 'pos' && ! ($user->can('sell.create') || $user->can('direct_sell.access'))) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $token = Str::random(64);
            Cache::put(self::cacheKey($token), [
                'user_id' => $user->id,
                'target' => $target,
            ], now()->addSeconds(self::TTL_SECONDS));

            return $this->success([
                'url' => url('/mobile/web-login/'.$token),
                'expires_in' => self::TTL_SECONDS,
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_web_session']);
        }
    }

    /** Web route: consumes the single-use link and signs the user into the website. */
    public function consume(Request $request, string $token)
    {
        $payload = strlen($token) === 64 ? Cache::pull(self::cacheKey($token)) : null;
        $user = ! empty($payload['user_id']) ? User::find($payload['user_id']) : null;

        if (empty($user) || $this->loginService->restrictionMessage($user) !== null) {
            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('This sign-in link has expired. Please open it again from the app.'),
            ]);
        }

        if (Auth::check()) {
            Auth::logout();
        }
        $request->session()->invalidate();
        Auth::login($user);
        $request->session()->regenerate();
        $this->loginService->logAuthenticationEvent($request, $user, 'login');

        return redirect(self::TARGETS[$payload['target']] ?? self::TARGETS['pos']);
    }

    protected static function cacheKey(string $token): string
    {
        return 'mobile_web_login:'.hash('sha256', $token);
    }
}
