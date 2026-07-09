<?php

namespace Tests\Feature;

use App\Services\MobileSasaSmsService;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginOtpFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('business');
        Schema::dropIfExists('admin_settings');

        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('password');
            $table->string('language')->nullable();
            $table->string('status')->default('active');
            $table->boolean('allow_login')->default(true);
            $table->string('user_type')->default('user');
            $table->boolean('otp_login_enabled')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('subscription_required')->default(false);
            $table->timestamps();
        });

        $businessUtil = \Mockery::mock(BusinessUtil::class);
        $businessUtil->shouldReceive('activityLog')->zeroOrMoreTimes();
        $businessUtil->shouldReceive('getAuthActivityProperties')->zeroOrMoreTimes()->andReturn([]);
        $this->app->instance(BusinessUtil::class, $businessUtil);

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $moduleUtil->shouldReceive('hasThePermissionInSubscription')->zeroOrMoreTimes()->andReturn(true);
        $this->app->instance(ModuleUtil::class, $moduleUtil);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('admin_settings');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('business');
        // Truncate the migrations table so subsequent RefreshDatabase tests
        // re-run all migrations from scratch against the clean DB state.
        if (Schema::hasTable('migrations')) {
            \DB::table('migrations')->truncate();
        }
        parent::tearDown();
    }

    public function test_login_redirects_to_otp_challenge_when_otp_is_enabled()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->json('common_settings')->nullable();
        });

        \DB::table('business')->insert([
            'id' => 1,
            'name' => 'OTP Business',
            'is_active' => 1,
            'common_settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('users')->insert([
            'business_id' => 1,
            'surname' => 'Otp',
            'first_name' => 'Login',
            'last_name' => 'User',
            'username' => 'otp-login-user',
            'email' => 'otp@example.com',
            'contact_number' => '0712345678',
            'password' => Hash::make('secret'),
            'language' => 'en',
            'status' => 'active',
            'allow_login' => 1,
            'user_type' => 'user',
            'otp_login_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $smsService = \Mockery::mock(MobileSasaSmsService::class);
        $smsService->shouldReceive('sendLoginOtp')->once()->andReturn(true);
        $this->app->instance(MobileSasaSmsService::class, $smsService);

        $response = $this->post('/login', [
            'username' => 'otp-login-user',
            'password' => 'secret',
            'otp_delivery_method' => 'sms',
        ]);

        $response->assertRedirect(route('login.otp.form'));
        $response->assertSessionHas('login_otp', function ($otpData) {
            return $otpData['delivery_method'] === 'sms'
                && $otpData['user_id'] > 0
                && ! empty($otpData['otp_hash']);
        });
        $this->assertGuest();
    }

    public function test_verify_otp_authenticates_user_and_clears_pending_session()
    {
        \DB::table('business')->insert([
            'id' => 1,
            'name' => 'OTP Business',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'business_id' => 1,
            'surname' => 'Otp',
            'first_name' => 'Verify',
            'last_name' => 'User',
            'username' => 'otp-verify-user',
            'email' => 'verify@example.com',
            'contact_number' => '0712345678',
            'password' => Hash::make('secret'),
            'language' => 'en',
            'status' => 'active',
            'allow_login' => 1,
            'user_type' => 'user',
            'otp_login_enabled' => 1,
        ]);

        $smsService = \Mockery::mock(MobileSasaSmsService::class);
        $this->app->instance(MobileSasaSmsService::class, $smsService);

        $response = $this->withSession([
            'login_otp' => [
                'user_id' => $user->id,
                'remember' => false,
                'delivery_method' => 'sms',
                'delivery_target' => '254712345678',
                'phone' => '254712345678',
                'email' => 'verify@example.com',
                'otp_hash' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(5)->timestamp,
                'resend_available_at' => now()->addSeconds(30)->timestamp,
                'attempts' => 0,
            ],
        ])->post(route('login.otp.verify'), [
            'otp' => '123456',
        ]);

        $response->assertRedirect('/home');
        $response->assertSessionMissing('login_otp');
        $this->assertAuthenticatedAs($user);
    }

    public function test_resend_otp_can_switch_to_email_delivery_and_refresh_session_state()
    {
        Mail::fake();

        \DB::table('business')->insert([
            'id' => 1,
            'name' => 'OTP Business',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'business_id' => 1,
            'surname' => 'Otp',
            'first_name' => 'Resend',
            'last_name' => 'User',
            'username' => 'otp-resend-user',
            'email' => 'resend@example.com',
            'contact_number' => '0712345678',
            'password' => Hash::make('secret'),
            'language' => 'en',
            'status' => 'active',
            'allow_login' => 1,
            'user_type' => 'user',
            'otp_login_enabled' => 1,
        ]);

        $smsService = \Mockery::mock(MobileSasaSmsService::class);
        $this->app->instance(MobileSasaSmsService::class, $smsService);

        $response = $this->from(route('login.otp.form'))->withSession([
            'login_otp' => [
                'user_id' => $user->id,
                'remember' => false,
                'delivery_method' => 'sms',
                'delivery_target' => '254712345678',
                'phone' => '254712345678',
                'email' => 'resend@example.com',
                'otp_hash' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(5)->timestamp,
                'resend_available_at' => now()->subSecond()->timestamp,
                'attempts' => 2,
            ],
        ])->post(route('login.otp.resend'), [
            'otp_delivery_method' => 'email',
        ]);

        $response->assertRedirect(route('login.otp.form'));
        $response->assertSessionHas('status.success', 1);
        $response->assertSessionHas('login_otp', function ($otpData) {
            return $otpData['delivery_method'] === 'email'
                && $otpData['delivery_target'] === 'resend@example.com'
                && $otpData['attempts'] === 0;
        });

        Mail::assertSent(\App\Mail\LoginOtpMail::class, 1);
    }
}