<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Controller;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'superadmin';
        $this->appVersion = config('superadmin.module_version');
    }

    /**
     * Install
     *
     * @return Response
     */
    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        //Check if installed or not.
        $is_installed = System::getProperty($this->module_name.'_version');
        if (empty($is_installed)) {
            DB::statement('SET default_storage_engine=INNODB;');
            $this->syncMissingSchemaOnly();
            System::addProperty($this->module_name.'_version', $this->appVersion);
        }

        $output = ['success' => 1,
            'msg' => 'Superadmin module installed succesfully',
        ];

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    //Updating
    public function update()
    {
        //Check if superadmin_version is same as appVersion then 404
        //If appVersion > superadmin_version - run update script.
        //Else there is some problem.

        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $superadmin_version = System::getProperty($this->module_name.'_version');

            if (Comparator::greaterThan($this->appVersion, $superadmin_version)) {
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', '512M');
                $this->installSettings();

                DB::statement('SET default_storage_engine=INNODB;');
                $this->syncMissingSchemaOnly();

                System::setProperty($this->module_name.'_version', $this->appVersion);
            } else {
                abort(404);
            }

            DB::commit();

            $output = ['success' => 1,
                'msg' => 'Superadmin module updated Succesfully to version '.$this->appVersion.' !!',
            ];

            return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
        } catch (Exception $e) {
            DB::rollBack();
            exit($e->getMessage());
        }
    }

    /**
     * Uninstall
     *
     * @return Response
     */
    public function uninstall()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name.'_version');

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    private function syncMissingSchemaOnly()
    {
        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->text('description');
                $table->integer('location_count')->comment('No. of Business Locations, 0 = infinite option.');
                $table->integer('user_count');
                $table->integer('product_count');
                $table->integer('invoice_count');
                $table->boolean('bookings')->default(false)->comment('Enable/Disable bookings');
                $table->boolean('kitchen')->default(false)->comment('Enable/Disable kitchen');
                $table->boolean('order_screen')->default(false)->comment('Enable/Disable order_screen');
                $table->boolean('tables')->default(false)->comment('Enable/Disable tables');
                $table->enum('interval', ['days', 'months', 'years']);
                $table->integer('interval_count');
                $table->integer('trial_days');
                $table->decimal('price', 22, 4);
                $table->longText('custom_permissions')->nullable();
                $table->integer('created_by');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active');
                $table->boolean('is_private')->default(0);
                $table->boolean('is_one_time')->default(0);
                $table->boolean('enable_custom_link')->default(0);
                $table->string('custom_link')->nullable();
                $table->string('custom_link_text')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        } else {
            Schema::table('packages', function ($table) {
                if (! Schema::hasColumn('packages', 'bookings')) {
                    $table->boolean('bookings')->default(false)->comment('Enable/Disable bookings');
                }
                if (! Schema::hasColumn('packages', 'kitchen')) {
                    $table->boolean('kitchen')->default(false)->comment('Enable/Disable kitchen');
                }
                if (! Schema::hasColumn('packages', 'order_screen')) {
                    $table->boolean('order_screen')->default(false)->comment('Enable/Disable order_screen');
                }
                if (! Schema::hasColumn('packages', 'tables')) {
                    $table->boolean('tables')->default(false)->comment('Enable/Disable tables');
                }
                if (! Schema::hasColumn('packages', 'custom_permissions')) {
                    $table->longText('custom_permissions')->nullable();
                }
                if (! Schema::hasColumn('packages', 'is_private')) {
                    $table->boolean('is_private')->default(0);
                }
                if (! Schema::hasColumn('packages', 'is_one_time')) {
                    $table->boolean('is_one_time')->default(0);
                }
                if (! Schema::hasColumn('packages', 'enable_custom_link')) {
                    $table->boolean('enable_custom_link')->default(0);
                }
                if (! Schema::hasColumn('packages', 'custom_link')) {
                    $table->string('custom_link')->nullable();
                }
                if (! Schema::hasColumn('packages', 'custom_link_text')) {
                    $table->string('custom_link_text')->nullable();
                }
            });
        }

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function ($table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned();
                $table->integer('package_id')->unsigned();
                $table->date('start_date')->nullable();
                $table->date('trial_end_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('package_price', 22, 4);
                $table->longText('package_details');
                $table->integer('created_id')->unsigned();
                $table->string('paid_via')->nullable();
                $table->string('payment_transaction_id')->nullable();
                $table->enum('status', ['approved', 'waiting', 'declined'])->default('waiting');
                $table->softDeletes();
                $table->timestamps();
                $table->index('package_id', 'subscriptions_package_id_index');
                $table->index('created_id', 'subscriptions_created_id_index');
            });
        } else {
            Schema::table('subscriptions', function ($table) {
                if (! Schema::hasColumn('subscriptions', 'business_id')) {
                    $table->integer('business_id')->unsigned()->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'package_id')) {
                    $table->integer('package_id')->unsigned()->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'start_date')) {
                    $table->date('start_date')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'trial_end_date')) {
                    $table->date('trial_end_date')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'end_date')) {
                    $table->date('end_date')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'package_price')) {
                    $table->decimal('package_price', 22, 4)->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'package_details')) {
                    $table->longText('package_details')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'created_id')) {
                    $table->integer('created_id')->unsigned()->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'paid_via')) {
                    $table->string('paid_via')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'payment_transaction_id')) {
                    $table->string('payment_transaction_id')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'status')) {
                    $table->enum('status', ['approved', 'waiting', 'declined'])->default('waiting');
                }
                if (! Schema::hasColumn('subscriptions', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (! Schema::hasColumn('subscriptions', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('subscriptions', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            if (Schema::hasColumn('subscriptions', 'package_id') && ! $this->hasIndexForColumn('subscriptions', 'package_id')) {
                Schema::table('subscriptions', function ($table) {
                    $table->index('package_id', 'subscriptions_package_id_index');
                });
            }

            if (Schema::hasColumn('subscriptions', 'created_id') && ! $this->hasIndexForColumn('subscriptions', 'created_id')) {
                Schema::table('subscriptions', function ($table) {
                    $table->index('created_id', 'subscriptions_created_id_index');
                });
            }
        }

        if (! Schema::hasTable('superadmin_communicator_logs')) {
            Schema::create('superadmin_communicator_logs', function ($table) {
                $table->increments('id');
                $table->text('business_ids')->nullable();
                $table->string('subject')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('superadmin_frontend_pages')) {
            Schema::create('superadmin_frontend_pages', function ($table) {
                $table->increments('id');
                $table->string('title')->nullable();
                $table->string('slug');
                $table->longText('content');
                $table->boolean('is_shown')->default(1);
                $table->integer('menu_order')->nullable()->default(0);
                $table->timestamps();
            });
        }

        $this->upsertSystemSetting('superadmin_version', (string) config('superadmin.module_version'));
        $this->upsertSystemSetting('app_currency_id', '2');
        $this->upsertSystemSetting('invoice_business_name', (string) env('APP_NAME'));
        $this->upsertSystemSetting('invoice_business_landmark', 'Landmark');
        $this->upsertSystemSetting('invoice_business_zip', 'Zip');
        $this->upsertSystemSetting('invoice_business_state', 'State');
        $this->upsertSystemSetting('invoice_business_city', 'City');
        $this->upsertSystemSetting('invoice_business_country', 'Country');
        $this->upsertSystemSetting('email', 'superadmin@example.com');
        $this->upsertSystemSetting('package_expiry_alert_days', '5');
        $this->upsertSystemSetting('enable_business_based_username', '0');
    }

    private function hasIndexForColumn(string $table, string $column): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    private function upsertSystemSetting(string $key, string $value): void
    {
        if (! Schema::hasTable('system')) {
            return;
        }

        DB::table('system')->updateOrInsert(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
