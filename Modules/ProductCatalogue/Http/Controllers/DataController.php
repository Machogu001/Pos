<?php

namespace Modules\ProductCatalogue\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Defines module as a superadmin package.
     *
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'productcatalogue_module',
                'label' => __('productcatalogue::lang.productcatalogue_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Defines user permissions for the module.
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'productcatalogue.access',
                'label' => __('productcatalogue::lang.access_catalogue_qr'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds Catalogue QR menus
     *
     * @return null
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        $is_productcatalogue_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'productcatalogue_module', 'superadmin_package');

        $is_admin = (new \App\Utils\Util())->is_admin(auth()->user(), $business_id);
        $user_can_access_catalogue = $is_admin || auth()->user()->can('superadmin') ||
            auth()->user()->can('productcatalogue.access');

        if ($is_productcatalogue_enabled && $user_can_access_catalogue) {
            $menu = Menu::instance('admin-sidebar-menu');
            $group_title = 'Modules & Apps';
            $added_to_group = false;

            $menu->whereTitle($group_title, function ($sub) use (&$added_to_group) {
                                if ($sub === null) { return; }
                $added_to_group = true;
                $sub->url(
                    action([\Modules\ProductCatalogue\Http\Controllers\ProductCatalogueController::class, 'generateQr']),
                    __('productcatalogue::lang.catalogue_qr'),
                    ['icon' => '', 'active' => request()->segment(1) == 'product-catalogue']
                );
            });

            if (! $added_to_group) {
                $menu->dropdown($group_title, function ($sub) {
                    $sub->url(
                        action([\Modules\ProductCatalogue\Http\Controllers\ProductCatalogueController::class, 'generateQr']),
                        __('productcatalogue::lang.catalogue_qr'),
                        ['icon' => '', 'active' => request()->segment(1) == 'product-catalogue']
                    );
                }, ['icon' => 'fas fa-layer-group'])->order(84);
            }
        }
    }
}
