<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SuperadminSettingsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $mailDrivers;

    protected $backupDisk;

    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;

        $this->mailDrivers = [
            'smtp' => 'SMTP',
            'sendmail' => 'Sendmail',
            'mailgun' => 'Mailgun',
            'mandrill' => 'Mandrill',
            'ses' => 'SES',
            'sparkpost' => 'Sparkpost',
        ];

        $this->backupDisk = ['local' => 'Local', 'dropbox' => 'Dropbox'];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $settings = System::pluck('value', 'key');
        $currencies = $this->businessUtil->allCurrencies();

        $superadmin_version = System::getProperty('superadmin_version');
        $is_demo = config('app.env') == 'demo' ? true : false;

        // env() returns null when config:cache is active; read .env file directly.
        $raw_env = [];
        $env_path = base_path('.env');
        if (file_exists($env_path)) {
            foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $raw_env[trim($k)] = trim($v, " \t\"'");
            }
        }
        $re = fn ($key) => $raw_env[$key] ?? null;   // shorthand getter

        $default_values = [
            'APP_NAME' => $re('APP_NAME'),
            'APP_TITLE' => $re('APP_TITLE'),
            'APP_LOCALE' => $re('APP_LOCALE'),
            'MAIL_MAILER' => $is_demo ? null : $re('MAIL_MAILER'),
            'MAIL_HOST' => $is_demo ? null : $re('MAIL_HOST'),
            'MAIL_PORT' => $is_demo ? null : $re('MAIL_PORT'),
            'MAIL_USERNAME' => $is_demo ? null : $re('MAIL_USERNAME'),
            'MAIL_PASSWORD' => $is_demo ? null : $re('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => $is_demo ? null : $re('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => $is_demo ? null : $re('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => $is_demo ? null : $re('MAIL_FROM_NAME'),
            'STRIPE_PUB_KEY' => $is_demo ? null : $re('STRIPE_PUB_KEY'),
            'STRIPE_SECRET_KEY' => $is_demo ? null : $re('STRIPE_SECRET_KEY'),
            'PAYPAL_MODE' => $re('PAYPAL_MODE'),
            'PAYPAL_SANDBOX_API_USERNAME' => $is_demo ? null : $re('PAYPAL_SANDBOX_API_USERNAME'),
            'PAYPAL_SANDBOX_API_PASSWORD' => $is_demo ? null : $re('PAYPAL_SANDBOX_API_PASSWORD'),
            'PAYPAL_SANDBOX_API_SECRET' => $is_demo ? null : $re('PAYPAL_SANDBOX_API_SECRET'),
            'PAYPAL_LIVE_API_USERNAME' => $is_demo ? null : $re('PAYPAL_LIVE_API_USERNAME'),
            'PAYPAL_LIVE_API_PASSWORD' => $is_demo ? null : $re('PAYPAL_LIVE_API_PASSWORD'),
            'PAYPAL_LIVE_API_SECRET' => $is_demo ? null : $re('PAYPAL_LIVE_API_SECRET'),
            'BACKUP_DISK' => $re('BACKUP_DISK'),
            'DROPBOX_ACCESS_TOKEN' => $is_demo ? null : $re('DROPBOX_ACCESS_TOKEN'),
            'RAZORPAY_KEY_ID' => $is_demo ? null : $re('RAZORPAY_KEY_ID'),
            'RAZORPAY_KEY_SECRET' => $is_demo ? null : $re('RAZORPAY_KEY_SECRET'),
            'PESAPAL_CONSUMER_KEY' => $is_demo ? null : $re('PESAPAL_CONSUMER_KEY'),
            'PESAPAL_CONSUMER_SECRET' => $is_demo ? null : $re('PESAPAL_CONSUMER_SECRET'),
            'PESAPAL_LIVE' => $is_demo ? null : $re('PESAPAL_LIVE'),
            'MPESA_CONSUMER_KEY' => $is_demo ? null : $re('MPESA_CONSUMER_KEY'),
            'MPESA_CONSUMER_SECRET' => $is_demo ? null : $re('MPESA_CONSUMER_SECRET'),
            'MPESA_SHORTCODE' => $is_demo ? null : $re('MPESA_SHORTCODE'),
            'MPESA_PASSKEY' => $is_demo ? null : $re('MPESA_PASSKEY'),
            'MPESA_CALLBACK' => $is_demo ? null : $re('MPESA_CALLBACK'),
            'PUSHER_APP_ID' => $is_demo ? null : $re('PUSHER_APP_ID'),
            'PUSHER_APP_KEY' => $is_demo ? null : $re('PUSHER_APP_KEY'),
            'PUSHER_APP_SECRET' => $is_demo ? null : $re('PUSHER_APP_SECRET'),
            'PUSHER_APP_CLUSTER' => $is_demo ? null : $re('PUSHER_APP_CLUSTER'),
            'GOOGLE_MAP_API_KEY' => $is_demo ? null : $re('GOOGLE_MAP_API_KEY'),
            'ALLOW_REGISTRATION' => $is_demo ? null : $re('ALLOW_REGISTRATION'),
            'PAYSTACK_PUBLIC_KEY' => $is_demo ? null : $re('PAYSTACK_PUBLIC_KEY'),
            'PAYSTACK_SECRET_KEY' => $is_demo ? null : $re('PAYSTACK_SECRET_KEY'),
            'FLUTTERWAVE_PUBLIC_KEY' => $is_demo ? null : $re('FLUTTERWAVE_PUBLIC_KEY'),
            'FLUTTERWAVE_SECRET_KEY' => $is_demo ? null : $re('FLUTTERWAVE_SECRET_KEY'),
            'FLUTTERWAVE_ENCRYPTION_KEY' => $is_demo ? null : $re('FLUTTERWAVE_ENCRYPTION_KEY'),
        ];
        $mail_drivers = $this->mailDrivers;

        $config_languages = config('constants.langs');
        $languages = [];
        foreach ($config_languages as $key => $value) {
            $languages[$key] = $value['full_name'];
        }
        $backup_disk = $this->backupDisk;

        $cron_job_command = $this->businessUtil->getCronJobCommand();

        return view('superadmin::superadmin_settings.edit')
            ->with(compact(
                'currencies',
                'settings',
                'superadmin_version',
                'mail_drivers',
                'languages',
                'default_values',
                'backup_disk',
                'cron_job_command'
            ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {

            //Disable .ENV settings in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                    'msg' => 'Feature disabled in demo!!',
                ];

                return back()->with('status', $output);
            }

            $system_settings = $request->only(['app_currency_id', 'invoice_business_name', 'email', 'invoice_business_landmark', 'invoice_business_zip', 'invoice_business_state', 'invoice_business_city', 'invoice_business_country', 'package_expiry_alert_days', 'superadmin_register_tc', 'welcome_email_subject', 'welcome_email_body', 'additional_js', 'additional_css', 'offline_payment_details']);

            //Checkboxes
            $checkboxes = ['enable_business_based_username', 'superadmin_enable_register_tc', 'allow_email_settings_to_businesses', 'enable_new_business_registration_notification', 'enable_new_subscription_notification', 'enable_welcome_email', 'enable_offline_payment'];
            $input = $request->input();
            foreach ($checkboxes as $checkbox) {
                $system_settings[$checkbox] = ! empty($input[$checkbox]) ? 1 : 0;
            }

            foreach ($system_settings as $key => $setting) {
                System::updateOrCreate(
                    ['key' => $key],
                    ['value' => $setting]
                            );
            }

            $env_settings = $request->only(['APP_NAME', 'APP_TITLE',
                'APP_LOCALE', 'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT',
                'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
                'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'STRIPE_PUB_KEY',
                'STRIPE_SECRET_KEY', 'PAYPAL_MODE',
                'PAYPAL_SANDBOX_API_USERNAME',
                'PAYPAL_SANDBOX_API_PASSWORD',
                'PAYPAL_SANDBOX_API_SECRET', 'PAYPAL_LIVE_API_USERNAME',
                'PAYPAL_LIVE_API_PASSWORD', 'PAYPAL_LIVE_API_SECRET',
                'BACKUP_DISK', 'DROPBOX_ACCESS_TOKEN',
                'RAZORPAY_KEY_ID', 'RAZORPAY_KEY_SECRET',
                'PESAPAL_CONSUMER_KEY', 'PESAPAL_CONSUMER_SECRET', 'PESAPAL_LIVE',
                'MPESA_CONSUMER_KEY', 'MPESA_CONSUMER_SECRET', 'MPESA_SHORTCODE',
                'MPESA_PASSKEY', 'MPESA_CALLBACK',
                'PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET',
                'PUSHER_APP_CLUSTER', 'GOOGLE_MAP_API_KEY', 'PAYSTACK_SECRET_KEY',
                'PAYSTACK_PUBLIC_KEY', 'FLUTTERWAVE_PUBLIC_KEY',
                'FLUTTERWAVE_SECRET_KEY', 'FLUTTERWAVE_ENCRYPTION_KEY', 'MAPBOX_ACCESS_TOKEN',
            ]);

            $env_settings['ALLOW_REGISTRATION'] = ! empty($request->input('ALLOW_REGISTRATION')) ? 'true' : 'false';
            $env_settings['BROADCAST_DRIVER'] = 'pusher';

            // Remove keys that were not present in the submitted form (null means the
            // field was never sent — preserve whatever is already in .env for those keys).
            $env_settings = array_filter($env_settings, fn ($v) => $v !== null);

            $found_envs = [];
            $env_path = base_path('.env');
            $env_lines = file($env_path);
            foreach ($env_settings as $index => $value) {
                foreach ($env_lines as $key => $line) {
                    // Exact-key match: line must start with KEY= or KEY="
                    // (avoids MAIL_HOST matching MAIL_HOST_VERIFY, etc.)
                    if (preg_match('/^'.preg_quote($index, '/').'=/', $line)) {
                        $env_lines[$key] = $index.'="'.$value.'"'.PHP_EOL;
                        $found_envs[] = $index;
                    }
                }
            }

            //Add the missing env settings (only for keys that were actually submitted)
            $missing_envs = array_diff(array_keys($env_settings), $found_envs);
            if (! empty($missing_envs)) {
                $missing_envs = array_values($missing_envs);
                foreach ($missing_envs as $k => $key) {
                    if ($k == 0) {
                        $env_lines[] = PHP_EOL.$key.'="'.$env_settings[$key].'"'.PHP_EOL;
                    } else {
                        $env_lines[] = $key.'="'.$env_settings[$key].'"'.PHP_EOL;
                    }
                }
            }

            $env_content = implode('', $env_lines);

            if (is_writable($env_path) && file_put_contents($env_path, $env_content)) {
                $output = ['success' => 1,
                    'msg' => __('lang_v1.success'),
                ];
            } else {
                $envOwner = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner(base_path('.env')))['name'] ?? '?') : '?';
                $envPerms = substr(sprintf('%o', fileperms(base_path('.env'))), -3);
                $output = ['success' => 0, 'msg' => "Some settings could not be saved. Run: sudo chown www-data:www-data .env && sudo chmod 640 .env (current: {$envPerms} {$envOwner})"];
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()
            ->action([\Modules\Superadmin\Http\Controllers\SuperadminSettingsController::class, 'edit'])
            ->with('status', $output);
    }
}
