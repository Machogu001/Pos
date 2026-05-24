<?php

namespace App\Utils;

use App\Barcode;
use App\Account;
use App\AccountType;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Currency;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\NotificationTemplate;
use App\Printer;
use App\Unit;
use App\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\VariationLocationDetails;


class BusinessUtil extends Util
{
    /**
     * Adds a default settings/resources for a new business
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @return bool
     */
    public function newBusinessDefaultResources($business_id, $user_id)
    {
        $user = User::find($user_id);

        //create Admin role and assign to user
        $role = Role::create(['name' => 'Admin#'.$business_id,
            'business_id' => $business_id,
            'guard_name' => 'web', 'is_default' => 1,
        ]);
        $user->assignRole($role->name);

        // Ensure Admin has HRM access by default
        try {
            $role->givePermissionTo('hrm.access');
        } catch (\Exception $e) {
            // Ignore if permission missing; seeder will add it later
        }

        //Create Cashier role for a new business
        $cashier_role = Role::create(['name' => 'Cashier#'.$business_id,
            'business_id' => $business_id,
            'guard_name' => 'web',
        ]);
        $cashier_role->syncPermissions([
            'sell.view',
            'sell.create',
            'sell.update',
            'sell.delete',
            'purchase.view',
            'purchase.create',
            'purchase.update',
            'purchase.update_status',
            'purchase.payments',
            'access_all_locations',
            'view_cash_register',
            'close_cash_register',
        ]);

        $business = Business::findOrFail($business_id);

        //Update reference count
        $ref_count = $this->setAndGetReferenceCount('contacts', $business_id);
        $contact_id = $this->generateReferenceNumber('contacts', $ref_count, $business_id);

        //Add Default/Walk-In Customer for new business
        $customer = [
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => 'Walk-In Customer',
            'created_by' => $user_id,
            'is_default' => 1,
            'contact_id' => $contact_id,
            'credit_limit' => 0,
        ];
        Contact::create($customer);

        //create default invoice setting for new business
        InvoiceScheme::create(['name' => 'Default',
            'scheme_type' => 'blank',
            'prefix' => '',
            'start_number' => 1,
            'total_digits' => 4,
            'is_default' => 1,
            'business_id' => $business_id,
        ]);
        //create default invoice layour for new business
        InvoiceLayout::create(['name' => 'Default',
            'header_text' => null,
            'invoice_no_prefix' => 'Invoice No.',
            'invoice_heading' => 'Invoice',
            'sub_total_label' => 'Subtotal',
            'discount_label' => 'Discount',
            'tax_label' => 'Tax',
            'total_label' => 'Total',
            'show_landmark' => 1,
            'show_city' => 1,
            'show_state' => 1,
            'show_zip_code' => 1,
            'show_country' => 1,
            'highlight_color' => '#000000',
            'footer_text' => '',
            'is_default' => 1,
            'business_id' => $business_id,
            'invoice_heading_not_paid' => '',
            'invoice_heading_paid' => '',
            'total_due_label' => 'Total Due',
            'paid_label' => 'Total Paid',
            'show_payments' => 1,
            'show_customer' => 1,
            'customer_label' => 'Customer',
            'table_product_label' => 'Product',
            'table_qty_label' => 'Quantity',
            'table_unit_price_label' => 'Unit Price',
            'table_subtotal_label' => 'Subtotal',
            'date_label' => 'Date',
        ]);

        //create default barcode setting for new business
        // Barcode::create(['name' => 'Default',
        //                 'description' => '',
        //                 'width' => 37.29,
        //                 'height' => 25.93,
        //                 'top_margin' => 5,
        //                 'left_margin' => 5,
        //                 'row_distance' => 1,
        //                 'col_distance' => 1,
        //                 'stickers_in_one_row' => 4,
        //                 'is_default' => 1,
        //                 'business_id' => $business_id
        //             ]);

        //Add Default Unit for new business
        $unit = [
            'business_id' => $business_id,
            'actual_name' => 'Pieces',
            'short_name' => 'Pc(s)',
            'allow_decimal' => 0,
            'created_by' => $user_id,
        ];
        Unit::create($unit);

        //Create default notification templates
        $notification_templates = NotificationTemplate::defaultNotificationTemplates($business_id);
        foreach ($notification_templates as $notification_template) {
            NotificationTemplate::create($notification_template);
        }

        $this->provisionDefaultAccountMappings($business_id, $user_id);

        return true;
    }

    /**
     * Gives a list of all currencies
     *
     * @return array
     */
    public function allCurrencies()
    {
        $currencies = Currency::select('id', DB::raw("concat(country, ' - ',currency, '(', code, ') ') as info"))
                ->orderBy('country')
                ->pluck('info', 'id');

        return $currencies;
    }

    /**
     * Gives a list of all timezone
     *
     * @return array
     */
    public function allTimeZones()
    {
        $datetime = new \DateTimeZone('EDT');

        $timezones = $datetime->listIdentifiers();
        $timezone_list = [];
        foreach ($timezones as $timezone) {
            $timezone_list[$timezone] = $timezone;
        }

        return $timezone_list;
    }

    /**
     * Gives a list of all accouting methods
     *
     * @return array
     */
    public function allAccountingMethods()
    {
        return [
            'fifo' => __('business.fifo'),
            'lifo' => __('business.lifo'),
        ];
    }

    /**
     * Creates new business with default settings.
     *
     * @return array
     */
    public function createNewBusiness($business_details)
    {
        $enabledModules = ! empty($business_details['enabled_modules'])
            ? (array) $business_details['enabled_modules']
            : ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses', 'account'];

        if (! in_array('account', $enabledModules, true)) {
            $enabledModules[] = 'account';
        }

        $business_details['enabled_modules'] = array_values(array_unique($enabledModules));

        $business_details['sell_price_tax'] = 'includes';

        $business_details['default_profit_percent'] = 25;

        //Add POS shortcuts
        $business_details['keyboard_shortcuts'] = '{"pos":{"express_checkout":"shift+e","pay_n_ckeckout":"shift+p","draft":"shift+d","cancel":"shift+c","edit_discount":"shift+i","edit_order_tax":"shift+t","add_payment_row":"shift+r","finalize_payment":"shift+f","recent_product_quantity":"f2","add_new_product":"f4"}}';

        //Add prefixes
        $business_details['ref_no_prefixes'] = [
            'purchase' => 'PO',
            'stock_transfer' => 'ST',
            'stock_adjustment' => 'SA',
            'sell_return' => 'CN',
            'expense' => 'EP',
            'contacts' => 'CO',
            'purchase_payment' => 'PP',
            'sell_payment' => 'SP',
            'business_location' => 'BL',
        ];

        //Disable inline tax editing
        $business_details['enable_inline_tax'] = 0;

        $business = Business::create_business($business_details);

        return $business;
    }

    public function provisionDefaultAccountMappings($business_id, $user_id = null)
    {
        $business = Business::find($business_id);

        if (empty($business)) {
            return false;
        }

        $enabledModules = is_array($business->enabled_modules) ? $business->enabled_modules : [];
        if (! in_array('account', $enabledModules, true)) {
            return false;
        }

        $user_id = $user_id ?: $business->owner_id;
        $commonSettings = Business::normalizeCommonSettings($business->common_settings ?: []);
        $typeMappings = ! empty($commonSettings['default_account_mappings']) && is_array($commonSettings['default_account_mappings'])
            ? $commonSettings['default_account_mappings']
            : [];

        $accountBlueprints = [
            'sell' => ['name' => 'Sales/Revenue', 'candidates' => ['Sales/Revenue', 'Sales', 'Revenue'], 'number' => 4010, 'type' => 'Income', 'sub_type' => 'Sales Revenue'],
            'purchase' => ['name' => 'Accounts Payable', 'candidates' => ['Accounts Payable', 'Trade Payables'], 'number' => 2010, 'type' => 'Liabilities', 'sub_type' => 'Payables'],
            'expense' => ['name' => 'Office Expenses', 'candidates' => ['Office Expenses', 'Expenses'], 'number' => 6000, 'type' => 'Expenses', 'sub_type' => 'Operating Expenses'],
            'payroll' => ['name' => 'Payroll', 'candidates' => ['Payroll', 'Payroll Expense'], 'number' => 6010, 'type' => 'Expenses', 'sub_type' => 'Operating Expenses'],
            'payment' => ['name' => 'Bank', 'candidates' => ['Bank', 'Cash at Bank', 'Bank Account', 'Cash'], 'number' => 1010, 'type' => 'Assets', 'sub_type' => 'Cash & Bank'],
            'cogs' => ['name' => 'Cost of Goods', 'candidates' => ['Cost of Goods', 'Cost of Goods Sold', 'COGS'], 'number' => 5010, 'type' => 'Expenses', 'sub_type' => 'Cost of Goods Sold'],
            'inventory' => ['name' => 'Inventory', 'candidates' => ['Inventory', 'Stock'], 'number' => 1200, 'type' => 'Assets', 'sub_type' => 'Inventory'],
            'inventory_gain' => ['name' => 'Inventory Gain', 'candidates' => ['Inventory Gain'], 'number' => 4090, 'type' => 'Income', 'sub_type' => 'Other Income'],
            'inventory_loss' => ['name' => 'Inventory Loss', 'candidates' => ['Inventory Loss', 'Inventory Adjustment'], 'number' => 6090, 'type' => 'Expenses', 'sub_type' => 'Other Expenses (Inventory Adjustment)'],
            'purchase_tax' => ['name' => 'Input VAT', 'candidates' => ['Input (Purchase) VAT 16 %', 'Input VAT', 'Purchase Tax'], 'number' => 1150, 'type' => 'Assets', 'sub_type' => 'Other Assets'],
            'sales_tax' => ['name' => 'Output VAT', 'candidates' => ['Output (Sales) VAT 16 %', 'Output VAT', 'Sales Tax'], 'number' => 2105, 'type' => 'Liabilities', 'sub_type' => 'Taxes Payable'],
            'accounts_receivable' => ['name' => 'Accounts Receivable', 'candidates' => ['Accounts Receivable', 'Trade Receivables'], 'number' => 1100, 'type' => 'Assets', 'sub_type' => 'Receivables'],
            'opening_stock_equity' => ['name' => 'Opening Stock Equity', 'candidates' => ['Opening Stock Equity'], 'number' => 3100, 'type' => 'Equity', 'sub_type' => null],
        ];

        $updated = false;

        foreach ($accountBlueprints as $mappingKey => $blueprint) {
            $account = null;

            if (! empty($typeMappings[$mappingKey])) {
                $account = Account::where('business_id', $business_id)
                    ->where('id', $typeMappings[$mappingKey])
                    ->first();
            }

            if (empty($account)) {
                $account = $this->findBusinessAccountByCandidates($business_id, $blueprint['candidates']);
            }

            if (empty($account)) {
                $account = Account::create([
                    'business_id' => $business_id,
                    'name' => $blueprint['name'],
                    'account_number' => $this->nextAvailableBusinessAccountNumber($business_id, $blueprint['number']),
                    'account_type_id' => $this->ensureBusinessAccountTypeId($business_id, $blueprint['type'], $blueprint['sub_type'] ?? null),
                    'created_by' => $user_id,
                ]);
            }

            $targetAccountTypeId = $this->ensureBusinessAccountTypeId($business_id, $blueprint['type'], $blueprint['sub_type'] ?? null);
            if (! empty($targetAccountTypeId) && (int) $account->account_type_id !== (int) $targetAccountTypeId) {
                $account->account_type_id = $targetAccountTypeId;
                $account->save();
            }

            if (($typeMappings[$mappingKey] ?? null) != $account->id) {
                $typeMappings[$mappingKey] = $account->id;
                $updated = true;
            }
        }

        if (empty($typeMappings['tax']) && ! empty($typeMappings['sales_tax'])) {
            $typeMappings['tax'] = $typeMappings['sales_tax'];
            $updated = true;
        }

        if ($updated) {
            $commonSettings['default_account_mappings'] = $typeMappings;
            $business->common_settings = $commonSettings;
            $business->save();
        }

        return true;
    }

    protected function findBusinessAccountByCandidates($business_id, array $candidates)
    {
        foreach ($candidates as $candidate) {
            $account = Account::where('business_id', $business_id)
                ->whereRaw('LOWER(name) = ?', [strtolower($candidate)])
                ->first();

            if (! empty($account)) {
                return $account;
            }
        }

        return null;
    }

    protected function ensureBusinessAccountTypeId($business_id, $typeLabel, $subTypeLabel = null)
    {
        $majorType = $this->ensureBusinessMajorAccountType($business_id, $typeLabel);

        if (empty($subTypeLabel)) {
            return ! empty($majorType) ? $majorType->id : null;
        }

        $normalizedSubTypeLabel = strtolower(trim((string) $subTypeLabel));

        $accountType = AccountType::where('business_id', $business_id)
            ->where('parent_account_type_id', $majorType->id)
            ->whereRaw('LOWER(name) = ?', [$normalizedSubTypeLabel])
            ->first();

        if (! empty($accountType)) {
            return $accountType->id;
        }

        $accountType = AccountType::create([
            'business_id' => $business_id,
            'name' => $subTypeLabel,
            'parent_account_type_id' => $majorType->id,
        ]);

        return $accountType->id;
    }

    protected function ensureBusinessMajorAccountType($business_id, $typeLabel)
    {
        $typeLabel = strtolower(trim((string) $typeLabel));

        $accountType = AccountType::where('business_id', $business_id)
            ->where(function ($query) use ($typeLabel) {
                $query->whereRaw('LOWER(name) = ?', [$typeLabel])
                    ->orWhereHas('parent_account', function ($parentQuery) use ($typeLabel) {
                        $parentQuery->whereRaw('LOWER(name) = ?', [$typeLabel]);
                    });
            })
            ->orderByRaw('CASE WHEN parent_account_type_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first();

        if (! empty($accountType)) {
            return $accountType;
        }

        $typeNameMap = [
            'assets' => 'Assets',
            'asset' => 'Assets',
            'liabilities' => 'Liabilities',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'income' => 'Income',
            'expenses' => 'Expenses',
            'expense' => 'Expenses',
        ];

        return AccountType::create([
            'business_id' => $business_id,
            'name' => $typeNameMap[$typeLabel] ?? ucfirst($typeLabel),
            'parent_account_type_id' => null,
        ]);
    }

    protected function nextAvailableBusinessAccountNumber($business_id, $candidate)
    {
        $candidate = (int) $candidate;

        while (Account::where('business_id', $business_id)
            ->where('account_number', (string) $candidate)
            ->exists()) {
            $candidate++;
        }

        return (string) $candidate;
    }

    /**
     * Gives details for a business
     *
     * @return object
     */
    public function getDetails($business_id)
    {
        $details = Business::leftjoin('tax_rates AS TR', 'business.default_sales_tax', 'TR.id')
                        ->leftjoin('currencies AS cur', 'business.currency_id', 'cur.id')
                        ->select(
                            'business.*',
                            'cur.code as currency_code',
                            'cur.symbol as currency_symbol',
                            'thousand_separator',
                            'decimal_separator',
                            'TR.amount AS tax_calculation_amount',
                            'business.default_sales_discount'
                        )
                        ->where('business.id', $business_id)
                        ->first();

        return $details;
    }

    /**
     * Gives current financial year
     *
     * @return array
     */
    public function getCurrentFinancialYear($business_id)
    {
        $business = Business::where('id', $business_id)->first();
        $start_month = $business->fy_start_month;
        $end_month = $start_month - 1;
        if ($start_month == 1) {
            $end_month = 12;
        }

        $start_year = date('Y');
        //if current month is less than start month change start year to last year
        if (date('n') < $start_month) {
            $start_year = $start_year - 1;
        }

        $end_year = date('Y');
        //if current month is greater than end month change end year to next year
        if (date('n') > $end_month) {
            $end_year = $start_year + 1;
        }
        $start_date = $start_year.'-'.str_pad($start_month, 2, 0, STR_PAD_LEFT).'-01';
        $end_date = $end_year.'-'.str_pad($end_month, 2, 0, STR_PAD_LEFT).'-01';
        $end_date = date('Y-m-t', strtotime($end_date));

        $output = [
            'start' => $start_date,
            'end' => $end_date,
        ];

        return $output;
    }

    /**
     * Adds a new location to a business
     *
     * @param  int  $business_id
     * @param  array  $location_details
     * @param  int  $invoice_layout_id default null
     * @return location object
     */
    public function addLocation($business_id, $location_details, $invoice_scheme_id = null, $invoice_layout_id = null)
    {
        if (empty($invoice_scheme_id)) {
            $layout = InvoiceLayout::where('is_default', 1)
                                    ->where('business_id', $business_id)
                                    ->first();
            $invoice_layout_id = $layout->id;
        }

        if (empty($invoice_scheme_id)) {
            $scheme = InvoiceScheme::where('is_default', 1)
                                    ->where('business_id', $business_id)
                                    ->first();
            $invoice_scheme_id = $scheme->id;
        }

        //Update reference count
        $ref_count = $this->setAndGetReferenceCount('business_location', $business_id);
        $location_id = $this->generateReferenceNumber('business_location', $ref_count, $business_id);

        //Enable all payment methods by default
        $payment_types = $this->payment_types();
        $location_payment_types = [];
        foreach ($payment_types as $key => $value) {
            $location_payment_types[$key] = [
                'is_enabled' => 1,
                'account' => null,
            ];
        }
        $location = BusinessLocation::create(['business_id' => $business_id,
            'name' => $location_details['name'],
            'landmark' => $location_details['landmark'],
            'city' => $location_details['city'],
            'state' => $location_details['state'],
            'zip_code' => $location_details['zip_code'],
            'country' => $location_details['country'],
            'invoice_scheme_id' => $invoice_scheme_id,
            'invoice_layout_id' => $invoice_layout_id,
            'sale_invoice_layout_id' => $invoice_layout_id,
            'mobile' => ! empty($location_details['mobile']) ? $location_details['mobile'] : '',
            'alternate_number' => ! empty($location_details['alternate_number']) ? $location_details['alternate_number'] : '',
            'website' => ! empty($location_details['website']) ? $location_details['website'] : '',
            'email' => '',
            'location_id' => $location_id,
            'default_payment_accounts' => json_encode($location_payment_types),
        ]);

        return $location;
    }

    /**
     * Return the invoice layout details
     *
     * @param  int  $business_id
     * @param  array  $layout_id = null
     * @return location object
     */
    public function invoiceLayout($business_id, $layout_id = null)
    {
        $layout = null;
        if (! empty($layout_id)) {
            $layout = InvoiceLayout::find($layout_id);
        }

        //If layout is not found (deleted) then get the default layout for the business
        if (empty($layout)) {
            $layout = InvoiceLayout::where('business_id', $business_id)
                        ->where('is_default', 1)
                        ->first();
        }
        //$output = []
        return $layout;
    }

    /**
     * Return the printer configuration
     *
     * @param  int  $business_id
     * @param  int  $printer_id
     * @return array
     */
    public function printerConfig($business_id, $printer_id)
    {
        $printer = Printer::where('business_id', $business_id)
                    ->find($printer_id);

        $output = [];

        if (! empty($printer)) {
            $output['connection_type'] = $printer->connection_type;
            $output['capability_profile'] = $printer->capability_profile;
            $output['char_per_line'] = $printer->char_per_line;
            $output['ip_address'] = $printer->ip_address;
            $output['port'] = $printer->port;
            $output['path'] = $printer->path;
            $output['server_url'] = $printer->server_url;
        }

        return $output;
    }

    /**
     * Return the date range for which editing of transaction for a business is allowed.
     *
     * @param  int  $business_id
     * @param  char  $edit_transaction_period
     * @return array
     */
    public function editTransactionDateRange($business_id, $edit_transaction_period)
    {
        if (is_numeric($edit_transaction_period)) {
            return ['start' => \Carbon::today()
                ->subDays($edit_transaction_period),
                'end' => \Carbon::today(),
            ];
        } elseif ($edit_transaction_period == 'fy') {
            //Editing allowed for current financial year
            return $this->getCurrentFinancialYear($business_id);
        }

        return false;
    }

    /**
     * Return the default setting for the pos screen.
     *
     * @return array
     */
    public function defaultPosSettings()
    {
        return ['disable_pay_checkout' => 0, 'disable_draft' => 0, 'disable_express_checkout' => 0, 'hide_product_suggestion' => 0, 'hide_recent_trans' => 0, 'disable_discount' => 0, 'disable_order_tax' => 0, 'is_pos_subtotal_editable' => 0];
    }

    /**
     * Return the default setting for the email.
     *
     * @return array
     */
    public function defaultEmailSettings()
    {
        return ['mail_host' => '', 'mail_port' => '', 'mail_username' => '', 'mail_password' => '', 'mail_encryption' => '', 'mail_from_address' => '', 'mail_from_name' => ''];
    }

    /**
     * Return the default setting for the email.
     *
     * @return array
     */
    public function defaultSmsSettings()
    {
        return ['url' => '', 'send_to_param_name' => 'to', 'msg_param_name' => 'text', 'request_method' => 'post', 'param_1' => '', 'param_val_1' => '', 'param_2' => '', 'param_val_2' => '', 'param_3' => '', 'param_val_3' => '', 'param_4' => '', 'param_val_4' => '', 'param_5' => '', 'param_val_5' => ''];
    }

}
