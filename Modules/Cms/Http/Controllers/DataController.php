<?php

namespace Modules\Cms\Http\Controllers;

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
                'value' => 'cms.view_page',
                'label' => __('cms::lang.view_page'),
                'default' => false,
            ],
            [
                'value' => 'cms.create_page',
                'label' => __('cms::lang.add_page'),
                'default' => false,
            ],
            [
                'value' => 'cms.edit_page',
                'label' => __('cms::lang.edit_page'),
                'default' => false,
            ],
            [
                'value' => 'cms.delete_page',
                'label' => __('cms::lang.delete_page'),
                'default' => false,
            ],
            [
                'value' => 'cms.manage_media',
                'label' => __('cms::lang.manage_media'),
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
                'name' => 'cms_module',
                'label' => __('cms::lang.cms_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds CMS menus.
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        $commonUtil = new Util();

        $is_cms_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'cms_module', 'superadmin_package');

        $is_admin = $commonUtil->is_admin(auth()->user(), $business_id);
        $user_can_access = $is_admin || auth()->user()->can('superadmin') ||
            \App\Support\ModuleAccessGate::userCanAccess(auth()->user(), [], ['cms.']);

        if ($is_cms_enabled && $user_can_access) {
            $menu = Menu::instance('admin-sidebar-menu');
            $group_title = 'Modules & Apps';
            $added_to_group = false;

            $menu->whereTitle($group_title, function ($sub) use (&$added_to_group) {
                if ($sub === null) { return; }
                $added_to_group = true;
                $sub->url(
                    url('cms/pages'),
                    __('cms::lang.cms'),
                    ['icon' => '', 'active' => request()->segment(1) == 'cms']
                );
            });

            if (! $added_to_group) {
                $menu->dropdown($group_title, function ($sub) {
                    $sub->url(
                        url('cms/pages'),
                        __('cms::lang.cms'),
                        ['icon' => '', 'active' => request()->segment(1) == 'cms']
                    );
                }, ['icon' => 'fas fa-layer-group'])->order(84);
            }
        }
    }
}
