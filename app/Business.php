<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'woocommerce_api_settings'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['woocommerce_api_settings'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'ref_no_prefixes' => 'array',
        'enabled_modules' => 'array',
        'email_settings' => 'array',
        'sms_settings' => 'array',
        'common_settings' => 'array',
        'weighing_scale_setting' => 'array',
    ];

    public static function normalizeCommonSettings($commonSettings = [])
    {
        $commonSettings = is_array($commonSettings) ? $commonSettings : [];

        // Keep purchase tax override flags in sync.
        // New semantics: allow_purchase_tax_override=1 means editable tax fields.
        if (array_key_exists('allow_purchase_tax_override', $commonSettings)) {
            $allowPurchaseTaxOverride = ! empty($commonSettings['allow_purchase_tax_override']) ? 1 : 0;
            $commonSettings['allow_purchase_tax_override'] = $allowPurchaseTaxOverride;
            $commonSettings['lock_purchase_tax_override'] = $allowPurchaseTaxOverride ? 0 : 1;
        } elseif (array_key_exists('lock_purchase_tax_override', $commonSettings)) {
            $lockPurchaseTaxOverride = ! empty($commonSettings['lock_purchase_tax_override']) ? 1 : 0;
            $commonSettings['lock_purchase_tax_override'] = $lockPurchaseTaxOverride;
            $commonSettings['allow_purchase_tax_override'] = $lockPurchaseTaxOverride ? 0 : 1;
        }

        $defaultMappings = config('constants.default_account_mappings', []);
        $typeMappings = ! empty($commonSettings['default_account_mappings']) && is_array($commonSettings['default_account_mappings'])
            ? $commonSettings['default_account_mappings']
            : [];

        foreach ($defaultMappings as $mappingKey => $defaultAccountId) {
            if (empty($typeMappings[$mappingKey]) && ! empty($defaultAccountId)) {
                $typeMappings[$mappingKey] = (int) $defaultAccountId;
            }
        }

        if (empty($typeMappings['purchase_tax']) && ! empty($typeMappings['tax'])) {
            $typeMappings['purchase_tax'] = $typeMappings['tax'];
        }

        if (empty($typeMappings['sales_tax']) && ! empty($typeMappings['tax'])) {
            $typeMappings['sales_tax'] = $typeMappings['tax'];
        }

        if (empty($typeMappings['tax']) && ! empty($typeMappings['sales_tax'])) {
            $typeMappings['tax'] = $typeMappings['sales_tax'];
        }

        if (! empty($typeMappings)) {
            $commonSettings['default_account_mappings'] = $typeMappings;
        }

        return $commonSettings;
    }

    /**
     * Returns the date formats
     */
    public static function date_formats()
    {
        return [
            'd-m-Y' => 'dd-mm-yyyy',
            'm-d-Y' => 'mm-dd-yyyy',
            'd/m/Y' => 'dd/mm/yyyy',
            'm/d/Y' => 'mm/dd/yyyy',
        ];
    }

    /**
     * Get the owner details
     */
    public function owner()
    {
        return $this->hasOne(\App\User::class, 'id', 'owner_id');
    }

    /**
     * Get the Business currency.
     */
    public function currency()
    {
        return $this->belongsTo(\App\Currency::class);
    }

    /**
     * Get the Business locations.
     */
    public function locations()
    {
        return $this->hasMany(\App\BusinessLocation::class);
    }

    /**
     * Get the Business printers.
     */
    public function printers()
    {
        return $this->hasMany(\App\Printer::class);
    }

    /**
     * Get the Business subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany('\Modules\Superadmin\Entities\Subscription');
    }

    /**
     * Get the Business users.
     */
    public function users()
    {
        return $this->hasMany(\App\User::class);
    }

    /**
     * Creates a new business based on the input provided.
     *
     * @return object
     */
    public static function create_business($details)
    {
        $details['common_settings'] = self::normalizeCommonSettings($details['common_settings'] ?? []);

        $business = Business::create($details);
        return $business;
    }

    /**
     * Updates a business based on the input provided.
     *
     * @param  int  $business_id
     * @param  array  $details
     * @return object
     */
    public static function update_business($business_id, $details)
    {
        if (! empty($details)) {
            if (array_key_exists('common_settings', $details)) {
                $details['common_settings'] = self::normalizeCommonSettings($details['common_settings']);
            }

            Business::where('id', $business_id)->update($details);
        }
    }

    public function getBusinessAddressAttribute()
    {
        $location = $this->locations->first();
        $address = $location->landmark.', '.$location->city.
        ', '.$location->state.'<br>'.$location->country.', '.$location->zip_code;

        return $address;
    }
}
