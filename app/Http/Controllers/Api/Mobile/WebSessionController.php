<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Middleware\AdminSidebarMenu;
use App\Services\MobileLoginService;
use App\User;
use App\Utils\MobileAppView;
use App\Utils\Util;
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

    public function __construct(protected MobileLoginService $loginService, protected Util $util)
    {
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'target' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::TARGETS))],
            // Website page to open after signing in (admins only), e.g. "/reports/profit-loss".
            'path' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $user = $request->user();
            $target = $data['target'] ?? 'pos';
            if ($target === 'pos' && ! ($user->can('sell.create') || $user->can('direct_sell.access'))) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }
            if ($target === 'home' && ! $this->util->is_admin($user)) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $token = Str::random(64);
            Cache::put(self::cacheKey($token), [
                'user_id' => $user->id,
                'target' => $target,
                'path' => $target === 'home' ? self::safePath($data['path'] ?? null) : null,
            ], now()->addSeconds(self::TTL_SECONDS));

            return $this->success([
                'url' => url('/mobile/web-login/'.$token),
                'expires_in' => self::TTL_SECONDS,
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_web_session']);
        }
    }

    /**
     * The website's sidebar menu (filtered by the user's permissions and enabled modules), so the app
     * can list Purchases, Products, Reports, … in its own menu before any website page is opened.
     */
    public function menu(Request $request)
    {
        try {
            if (! $this->util->is_admin($request->user())) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            app(AdminSidebarMenu::class)->handle($request, function () {
                return null;
            });

            return $this->success(['items' => MobileAppView::menu()]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_web_menu']);
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

        return redirect($payload['path'] ?? self::TARGETS[$payload['target']] ?? self::TARGETS['pos']);
    }

    /** Accepts only a local path (no scheme/host), so the link can't redirect to another site. */
    protected static function safePath(?string $path): ?string
    {
        if ($path === null || ! preg_match('#^/(?![/\\\\])[^\s\\\\]*$#', $path)) {
            return null;
        }
        if (preg_match('#^/(mobile/web-login|login|logout)(/|\?|$)#i', $path)) {
            return null;
        }

        return $path;
    }

    protected static function cacheKey(string $token): string
    {
        return 'mobile_web_login:'.hash('sha256', $token);
    }
}
