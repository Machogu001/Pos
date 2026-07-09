<?php

namespace Modules\AiAssistance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Superadmin package permissions
     *
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'aiassistance_module',
                'label' => __('aiassistance::lang.aiassistance_module'),
                'default' => false,
            ],
            [
                'name' => 'aiassistance_max_token',
                'label' => __('aiassistance::lang.aiassistance_max_token'),
                'default' => false,
                'field_type' => 'number',
                'tooltip' => __('aiassistance::lang.max_token_tooltip')
            ],
        ];
    }

    /**
     * Adds menus
     *
     * @return null
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();

        $is_aiassistance_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'aiassistance_module');

        $commonUtil = new Util();
        $is_admin = $commonUtil->is_admin(auth()->user(), $business_id);

        if (auth()->user()->can('aiassistance.access_aiassistance_module') && $is_aiassistance_enabled) {
            $menu = Menu::instance('admin-sidebar-menu');
            $group_title = __('ui.modules_apps_menu');
            $added_to_group = false;

            $menu->whereTitle($group_title, function ($sub) use (&$added_to_group) {
                                if ($sub === null) { return; }
                $added_to_group = true;
                $sub->url(
                    action([\Modules\AiAssistance\Http\Controllers\AiAssistanceController::class, 'index']),
                    __('aiassistance::lang.aiassistance'),
                    ['icon' => '', 'active' => request()->segment(1) == 'aiassistance']
                );
            });

            if (! $added_to_group) {
                $menu->dropdown($group_title, function ($sub) {
                    $sub->url(
                        action([\Modules\AiAssistance\Http\Controllers\AiAssistanceController::class, 'index']),
                        __('aiassistance::lang.aiassistance'),
                        ['icon' => '', 'active' => request()->segment(1) == 'aiassistance']
                    );
                }, ['icon' => 'fas fa-layer-group'])->order(84);
            }
        }
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
                'value' => 'aiassistance.access_aiassistance_module',
                'label' => __('aiassistance::lang.access_aiassistance_module'),
                'default' => false,
            ]      
        ];
    }
}
