<?php

namespace App\Console\Commands;

use App\AdminSetting;
use App\Business;
use App\Notifications\TopSellingLowStockMailNotification;
use App\Notifications\TopSellingLowStockNotification;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotifyTopSellingLowStock extends Command
{
    protected $signature = 'inventory:notify-top-selling-low-stock
                            {--business-id= : Limit alerts to a single business id}
                            {--days= : Lookback window for top-selling calculation}
                            {--limit= : Maximum alerts to send per business}
                            {--dry-run : Preview alerts without sending notifications}';

    protected $description = 'Notify business users when top-selling products are running low or out of stock.';

    public function handle()
    {
        $settings = AdminSetting::first();
        $businessId = $this->option('business-id') ?: ($settings->top_selling_low_stock_alert_business_id ?? null);
        $days = max(1, (int) ($this->option('days') ?? ($settings->top_selling_low_stock_alert_days ?? 30)));
        $limit = max(1, (int) ($this->option('limit') ?? ($settings->top_selling_low_stock_alert_limit ?? 5)));
        $dryRun = (bool) $this->option('dry-run');
        $since = Carbon::now()->subDays($days)->startOfDay();
        $channelSettings = $this->resolveChannelSettings($settings);

        $businesses = Business::query()
            ->when(! empty($businessId), function ($query) use ($businessId) {
                $query->where('id', (int) $businessId);
            })
            ->get();

        if ($businesses->isEmpty()) {
            $this->info('No businesses matched the supplied filters.');

            return 0;
        }

        $sentCount = 0;
        $mailCount = 0;
        $smsCount = 0;
        $whatsAppCount = 0;
        $previewRows = [];

        foreach ($businesses as $business) {
            $alerts = $this->getLowStockTopSellers((int) $business->id, $since, $limit);

            if ($alerts->isEmpty()) {
                continue;
            }

            $allowSmsForThisBatch = $alerts->count() < 5;

            $userRecipients = $this->getRecipients($business);
            if ($userRecipients->isEmpty() && ! $dryRun && $channelSettings['send_in_app']) {
                $this->warn("No in-app alert recipients found for business #{$business->id}.");
            }

            if (! $dryRun && ! $this->hasAnyRecipient($business, $userRecipients, $channelSettings)) {
                continue;
            }

            foreach ($alerts as $alert) {
                $payload = $this->buildNotificationPayload($business, $alert, $days);

                if ($dryRun) {
                    $previewRows[] = [
                        $business->name,
                        $payload['product_display_name'],
                        $payload['location_name'],
                        $payload['stock_status'],
                        $payload['current_stock_label'],
                        $payload['total_qty_sold_label'],
                    ];
                    continue;
                }

                $cacheKey = $this->getCacheKey($payload);
                if (Cache::has($cacheKey)) {
                    continue;
                }

                $alertDatabaseCount = 0;
                $alertMailCount = 0;
                $alertSmsCount = 0;
                $alertWhatsAppCount = 0;

                if ($channelSettings['send_in_app']) {
                    foreach ($userRecipients as $recipient) {
                        $recipient->notify(new TopSellingLowStockNotification($payload));
                        $alertDatabaseCount++;
                    }
                }

                $alertMailCount = $this->sendEmailAlerts($userRecipients, $payload, $channelSettings);
                $alertSmsCount = $this->sendSmsAlerts($business, $userRecipients, $payload, $channelSettings, $allowSmsForThisBatch);
                $alertWhatsAppCount = $this->sendWhatsAppAlerts($userRecipients, $payload, $channelSettings);

                if ($alertDatabaseCount === 0 && $alertMailCount === 0 && $alertSmsCount === 0 && $alertWhatsAppCount === 0) {
                    continue;
                }

                $sentCount += $alertDatabaseCount;
                $mailCount += $alertMailCount;
                $smsCount += $alertSmsCount;
                $whatsAppCount += $alertWhatsAppCount;

                Cache::put($cacheKey, true, Carbon::now()->addHours(12));
            }
        }

        if ($dryRun) {
            if (empty($previewRows)) {
                $this->info('No top-selling low-stock products found.');
            } else {
                $this->table(
                    ['Business', 'Product', 'Location', 'Status', 'Current stock', 'Sold'],
                    $previewRows
                );
            }

            $this->info('Preview matches: '.count($previewRows));

            return 0;
        }

        if (! empty($settings)) {
            $settings->forceFill([
                'top_selling_low_stock_alert_last_run_at' => now(),
            ])->save();
        }

        $this->info("Top-selling low-stock alerts delivered. In-app: {$sentCount}, email: {$mailCount}, SMS: {$smsCount}, WhatsApp: {$whatsAppCount}");

        return 0;
    }

    protected function resolveChannelSettings(?AdminSetting $settings): array
    {
        return [
            'send_in_app' => (bool) ($settings->top_selling_low_stock_alert_send_in_app ?? true),
            'send_email' => (bool) ($settings->top_selling_low_stock_alert_send_email ?? false),
            'send_sms' => (bool) ($settings->top_selling_low_stock_alert_send_sms ?? false),
            'send_whatsapp' => (bool) ($settings->top_selling_low_stock_alert_send_whatsapp ?? false),
            'custom_emails' => $this->parseRecipients($settings->top_selling_low_stock_alert_custom_emails ?? ''),
            'custom_phones' => $this->parseRecipients($settings->top_selling_low_stock_alert_custom_phones ?? ''),
            'whatsapp_webhook_url' => trim((string) ($settings->top_selling_low_stock_alert_whatsapp_webhook_url ?? '')),
            'whatsapp_auth_header' => trim((string) ($settings->top_selling_low_stock_alert_whatsapp_auth_header ?? '')),
            'whatsapp_auth_token' => trim((string) ($settings->top_selling_low_stock_alert_whatsapp_auth_token ?? '')),
            'whatsapp_phone_param' => trim((string) ($settings->top_selling_low_stock_alert_whatsapp_phone_param ?? 'phone')) ?: 'phone',
            'whatsapp_message_param' => trim((string) ($settings->top_selling_low_stock_alert_whatsapp_message_param ?? 'message')) ?: 'message',
        ];
    }

    protected function getLowStockTopSellers(int $businessId, Carbon $since, int $limit): Collection
    {
        return DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('variation_location_details as vld', function ($join) {
                $join->on('tsl.product_id', '=', 'vld.product_id')
                    ->on('tsl.variation_id', '=', 'vld.variation_id')
                    ->on('t.location_id', '=', 'vld.location_id');
            })
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->leftJoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->leftJoin('business_locations as l', 'vld.location_id', '=', 'l.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereNull('tsl.parent_sell_line_id')
            ->whereNull('v.deleted_at')
            ->whereNotNull('p.alert_quantity')
            ->where('t.transaction_date', '>=', $since)
            ->whereRaw('vld.qty_available <= p.alert_quantity')
            ->groupBy(
                'vld.id',
                'p.id',
                'p.name',
                'p.sku',
                'p.type',
                'p.alert_quantity',
                'tsl.variation_id',
                'pv.name',
                'v.name',
                'v.sub_sku',
                't.location_id',
                'l.name',
                'u.short_name',
                'vld.qty_available'
            )
            ->orderByDesc('total_qty_sold')
            ->limit($limit)
            ->select(
                'vld.id as variation_location_detail_id',
                'p.id as product_id',
                'tsl.variation_id',
                't.location_id',
                'p.name as product_name',
                'p.sku',
                'p.type',
                'p.alert_quantity',
                'pv.name as product_variation',
                'v.name as variation_name',
                'v.sub_sku',
                'l.name as location_name',
                'u.short_name as unit',
                'vld.qty_available',
                DB::raw('SUM(tsl.quantity - COALESCE(tsl.quantity_returned, 0)) as total_qty_sold')
            )
            ->get();
    }

    protected function getRecipients(Business $business): Collection
    {
        $userQuery = $business->users()->whereNull('deleted_at');

        if (Schema::hasColumn('users', 'allow_login')) {
            $userQuery->where('allow_login', 1);
        }

        $users = $userQuery->get();

        if (! empty($business->owner_id)) {
            $owner = User::find($business->owner_id);
            if (! empty($owner)) {
                $users->push($owner);
            }
        }

        return $users
            ->filter(function ($user) {
                return ! empty($user) && empty($user->deleted_at);
            })
            ->unique('id')
            ->values();
    }

    protected function hasAnyRecipient(Business $business, Collection $userRecipients, array $channelSettings): bool
    {
        if ($channelSettings['send_in_app'] && $userRecipients->isNotEmpty()) {
            return true;
        }

        if ($channelSettings['send_email'] && (! empty($channelSettings['custom_emails']) || $userRecipients->contains(function ($user) {
            return ! empty($user->email);
        }))) {
            return true;
        }

        if ($channelSettings['send_sms'] && (! empty($channelSettings['custom_phones']) || $userRecipients->contains(function ($user) {
            return ! empty($user->phone);
        })) && ! empty($this->getSmsSettings($business))) {
            return true;
        }

        if ($channelSettings['send_whatsapp'] && (! empty($channelSettings['custom_phones']) || $userRecipients->contains(function ($user) {
            return ! empty($user->phone);
        })) && ! empty($channelSettings['whatsapp_webhook_url'])) {
            return true;
        }

        return false;
    }

    protected function buildNotificationPayload(Business $business, $alert, int $days): array
    {
        $currentStock = (float) $alert->qty_available;
        $soldQty = (float) $alert->total_qty_sold;
        $unit = (string) ($alert->unit ?? '');
        $stockReportUrl = action([
            \App\Http\Controllers\ReportController::class,
            'getStockReport',
        ], [
            'product_id' => $alert->product_id,
            'variation_id' => $alert->variation_id,
            'location_id' => $alert->location_id,
            'highlight_product' => 1,
        ]);
        $messageKey = $currentStock <= 0
            ? 'lang_v1.top_selling_product_out_of_stock_message'
            : 'lang_v1.top_selling_product_running_low_message';
        $plainKey = $currentStock <= 0
            ? 'lang_v1.top_selling_product_out_of_stock_plain'
            : 'lang_v1.top_selling_product_running_low_plain';
        $messageReplacements = [
            'product_name' => $this->formatProductName($alert),
            'location' => $alert->location_name ?? __('lang_v1.all'),
            'stock' => trim($this->formatQuantity($currentStock).' '.$unit),
            'days' => $days,
            'sold' => trim($this->formatQuantity($soldQty).' '.$unit),
        ];
        $plainMessage = __($plainKey, $messageReplacements);
        $htmlMessage = __($messageKey, $messageReplacements);
        $stockReportLabel = __('report.stock_report');

        return [
            'business_id' => $business->id,
            'business_name' => $business->name,
            'product_id' => $alert->product_id,
            'variation_id' => $alert->variation_id,
            'location_id' => $alert->location_id,
            'variation_location_detail_id' => $alert->variation_location_detail_id,
            'product_display_name' => $this->formatProductName($alert),
            'location_name' => $alert->location_name ?? __('lang_v1.all'),
            'stock_status' => $currentStock <= 0 ? 'out_of_stock' : 'running_low',
            'current_stock' => $currentStock,
            'current_stock_label' => trim($this->formatQuantity($currentStock).' '.$unit),
            'alert_quantity' => (float) $alert->alert_quantity,
            'total_qty_sold' => $soldQty,
            'total_qty_sold_label' => trim($this->formatQuantity($soldQty).' '.$unit),
            'unit' => $unit,
            'days' => $days,
            'stock_report_url' => $stockReportUrl,
            'mail_subject' => $this->buildMailSubject($business->name, $currentStock <= 0 ? 'out_of_stock' : 'running_low', $this->formatProductName($alert)),
            'mail_body' => '<p>'.$htmlMessage.'</p><p><strong>'.e(__('lang_v1.top_selling_low_stock_mail_business')).':</strong> '.e($business->name).'<br><strong>'.e($stockReportLabel).':</strong> <a href="'.e($stockReportUrl).'">'.e($stockReportUrl).'</a></p>',
            'sms_body' => $plainMessage,
            'whatsapp_body' => $plainMessage,
        ];
    }

    protected function sendEmailAlerts(Collection $userRecipients, array $payload, array $channelSettings): int
    {
        if (! $channelSettings['send_email']) {
            return 0;
        }

        $emails = $userRecipients
            ->pluck('email')
            ->merge($channelSettings['custom_emails'])
            ->filter()
            ->map(function ($email) {
                return strtolower(trim((string) $email));
            })
            ->unique()
            ->values();

        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify(new TopSellingLowStockMailNotification($payload));
        }

        return $emails->count();
    }

    protected function sendSmsAlerts(Business $business, Collection $userRecipients, array $payload, array $channelSettings, bool $allowSmsForThisBatch): int
    {
        if (! $channelSettings['send_sms'] || ! $allowSmsForThisBatch) {
            return 0;
        }

        $smsSettings = $this->getSmsSettings($business);
        if (empty($smsSettings)) {
            return 0;
        }

        $phones = $this->collectPhoneRecipients($userRecipients, $channelSettings['custom_phones']);
        if ($phones->isEmpty()) {
            return 0;
        }

        try {
            if (($smsSettings['provider'] ?? null) === 'mobilesasa') {
                $this->sendSmsViaMobileSasa($smsSettings, $phones, $payload['sms_body']);
            } else {
                app(\App\Utils\Util::class)->sendSms([
                    'sms_settings' => $smsSettings,
                    'mobile_number' => $phones->implode(','),
                    'sms_body' => $payload['sms_body'],
                ]);
            }

            return $phones->count();
        } catch (Throwable $e) {
            \Log::warning('Top-selling low-stock SMS alert failed: '.$e->getMessage(), [
                'business_id' => $business->id,
                'product_id' => $payload['product_id'],
            ]);

            return 0;
        }
    }

    protected function sendWhatsAppAlerts(Collection $userRecipients, array $payload, array $channelSettings): int
    {
        if (! $channelSettings['send_whatsapp'] || empty($channelSettings['whatsapp_webhook_url'])) {
            return 0;
        }

        $phones = $this->collectPhoneRecipients($userRecipients, $channelSettings['custom_phones']);
        if ($phones->isEmpty()) {
            return 0;
        }

        $client = new Client();
        $count = 0;
        foreach ($phones as $phone) {
            try {
                $headers = [];
                if (! empty($channelSettings['whatsapp_auth_header']) && ! empty($channelSettings['whatsapp_auth_token'])) {
                    $headers[$channelSettings['whatsapp_auth_header']] = $channelSettings['whatsapp_auth_token'];
                }

                $client->post($channelSettings['whatsapp_webhook_url'], [
                    'headers' => $headers,
                    'form_params' => [
                        $channelSettings['whatsapp_phone_param'] => $phone,
                        $channelSettings['whatsapp_message_param'] => $payload['whatsapp_body'],
                    ],
                    'timeout' => 15,
                ]);
                $count++;
            } catch (Throwable $e) {
                \Log::warning('Top-selling low-stock WhatsApp alert failed: '.$e->getMessage(), [
                    'phone' => $phone,
                    'product_id' => $payload['product_id'],
                ]);
            }
        }

        return $count;
    }

    protected function getSmsSettings(Business $business): array
    {
        if (! empty($business->sms_settings) && is_array($business->sms_settings)) {
            return $business->sms_settings;
        }

        $token = trim((string) config('services.mobilesasa.token', ''));
        $senderId = trim((string) config('services.mobilesasa.sender_id', ''));
        $baseUrl = rtrim((string) config('services.mobilesasa.base_url', 'https://api.mobilesasa.com/v1'), '/');

        if ($token === '' || $senderId === '') {
            return [];
        }

        return [
            'provider' => 'mobilesasa',
            'token' => $token,
            'sender_id' => $senderId,
            'base_url' => $baseUrl,
        ];
    }

    protected function sendSmsViaMobileSasa(array $smsSettings, Collection $phones, string $message): void
    {
        $client = new Client([
            'base_uri' => $smsSettings['base_url'].'/',
            'timeout' => 15,
        ]);

        foreach ($phones as $phone) {
            $client->post('send/message', [
                'headers' => [
                    'Authorization' => 'Bearer '.$smsSettings['token'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'senderID' => $smsSettings['sender_id'],
                    'phone' => $phone,
                    'message' => $message,
                ],
            ]);
        }
    }

    protected function collectPhoneRecipients(Collection $userRecipients, array $customPhones): Collection
    {
        return $userRecipients
            ->map(function ($user) {
                if (Schema::hasColumn('users', 'stock_alert_sms_notification_enabled') && ! $user->stock_alert_sms_notification_enabled) {
                    return null;
                }

                return $user->phone;
            })
            ->merge($customPhones)
            ->filter()
            ->map(function ($phone) {
                return preg_replace('/\s+/', '', trim((string) $phone));
            })
            ->unique()
            ->values();
    }

    protected function parseRecipients(string $rawRecipients): array
    {
        return collect(preg_split('/[\s,;]+/', $rawRecipients, -1, PREG_SPLIT_NO_EMPTY))
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function buildMailSubject(string $businessName, string $stockStatus, string $productName): string
    {
        return $stockStatus === 'out_of_stock'
            ? __('lang_v1.top_selling_product_out_of_stock_subject', ['product_name' => $productName, 'business_name' => $businessName])
            : __('lang_v1.top_selling_product_running_low_subject', ['product_name' => $productName, 'business_name' => $businessName]);
    }

    protected function formatProductName($alert): string
    {
        if ($alert->type === 'single') {
            return $alert->product_name.' ('.$alert->sku.')';
        }

        return $alert->product_name
            .' - '.$alert->product_variation
            .' - '.$alert->variation_name
            .' ('.$alert->sub_sku.')';
    }

    protected function formatQuantity(float $quantity): string
    {
        if ((float) ((int) $quantity) === $quantity) {
            return (string) ((int) $quantity);
        }

        return rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
    }

    protected function getCacheKey(array $payload): string
    {
        return implode(':', [
            'top-selling-low-stock',
            $payload['business_id'],
            $payload['variation_location_detail_id'],
            $payload['stock_status'],
            $payload['current_stock_label'],
        ]);
    }
}