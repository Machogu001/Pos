<?php

namespace Modules\Connector\Http\Controllers;

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
                'value' => 'connector.access_api',
                'label' => __('connector::lang.access_api'),
                'default' => false,
            ],
            [
                'value' => 'connector.manage_tokens',
                'label' => __('connector::lang.manage_tokens'),
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
                'name' => 'connector_module',
                'label' => __('connector::lang.connector_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds Connector menus.
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        $commonUtil = new Util();

        $is_connector_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'connector_module', 'superadmin_package');

        $is_admin = $commonUtil->is_admin(auth()->user(), $business_id);
        $user_can_access = $is_admin || auth()->user()->can('superadmin') ||
            \App\Support\ModuleAccessGate::userCanAccess(auth()->user(), ['connector.access_api', 'connector.manage_tokens']);

        if ($is_connector_enabled && $user_can_access) {
            $menu = Menu::instance('admin-sidebar-menu');
            $group_title = 'Modules & Apps';
            $added_to_group = false;

            $menu->whereTitle($group_title, function ($sub) use (&$added_to_group) {
                if ($sub === null) { return; }
                $added_to_group = true;
                $sub->url(
                    url('connector/tokens'),
                    __('connector::lang.connector'),
                    ['icon' => '', 'active' => request()->segment(1) == 'connector']
                );
            });

            if (! $added_to_group) {
                $menu->dropdown($group_title, function ($sub) {
                    $sub->url(
                        url('connector/tokens'),
                        __('connector::lang.connector'),
                        ['icon' => '', 'active' => request()->segment(1) == 'connector']
                    );
                }, ['icon' => 'fas fa-plug'])->order(85);
            }
        }
    }
}
