<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Defines user permissions for the module.
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'manufacturing.view_recipe',
                'label' => __('manufacturing::lang.view_recipe'),
                'default' => false,
            ],
            [
                'value' => 'manufacturing.create_recipe',
                'label' => __('manufacturing::lang.add_recipe'),
                'default' => false,
            ],
            [
                'value' => 'manufacturing.edit_recipe',
                'label' => __('manufacturing::lang.edit_recipe'),
                'default' => false,
            ],
            [
                'value' => 'manufacturing.delete_recipe',
                'label' => __('manufacturing::lang.delete_recipe'),
                'default' => false,
            ],
            [
                'value' => 'manufacturing.view_production',
                'label' => __('manufacturing::lang.view_production'),
                'default' => false,
            ],
            [
                'value' => 'manufacturing.create_production',
                'label' => __('manufacturing::lang.create_production'),
                'default' => false,
            ],
        ];
    }

    /**
     * Superadmin package permissions.
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'manufacturing_module',
                'label' => __('manufacturing::lang.manufacturing_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds Manufacturing menus.
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        $commonUtil = new Util();

        $is_manufacturing_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'manufacturing_module', 'superadmin_package');

        $is_admin = $commonUtil->is_admin(auth()->user(), $business_id);
        $user_can_access = $is_admin || auth()->user()->can('superadmin') ||
            \App\Support\ModuleAccessGate::userCanAccess(auth()->user(), [], ['manufacturing.']);

        if ($is_manufacturing_enabled && $user_can_access) {
            $menu = Menu::instance('admin-sidebar-menu');
            $group_title = __('ui.modules_apps_menu');
            $added_to_group = false;

            $menu->whereTitle($group_title, function ($sub) use (&$added_to_group) {
                if ($sub === null) { return; }
                $added_to_group = true;
                $sub->url(
                    url('manufacturing/productions'),
                    __('manufacturing::lang.manufacturing'),
                    ['icon' => '', 'active' => request()->segment(1) == 'manufacturing']
                );
            });

            if (! $added_to_group) {
                $menu->dropdown($group_title, function ($sub) {
                    $sub->url(
                        url('manufacturing/productions'),
                        __('manufacturing::lang.manufacturing'),
                        ['icon' => '', 'active' => request()->segment(1) == 'manufacturing']
                    );
                }, ['icon' => 'fas fa-layer-group'])->order(84);
            }
        }
    }
}
