<?php

namespace App\Utils;

use Illuminate\Http\Request;
use Nwidart\Menus\Facades\Menu;

/**
 * Support for pages shown inside the BreMac360 Android app. The app identifies its WebView with
 * "BreMac360App" in the User-Agent; such pages hide the website's own header and sidebar and hand
 * the sidebar menu to the app so it can show it in its native navigation drawer.
 */
class MobileAppView
{
    public const USER_AGENT_MARKER = 'BreMac360App';

    public static function isAppRequest(?Request $request = null): bool
    {
        $request = $request ?: request();

        return str_contains((string) $request->userAgent(), self::USER_AGENT_MARKER);
    }

    /**
     * The (already permission-filtered) admin sidebar menu as a plain list:
     * [{title, url, children: [{title, url}]}].
     */
    public static function menu(): array
    {
        try {
            if (! Menu::has('admin-sidebar-menu')) {
                return [];
            }

            $items = [];
            foreach (Menu::instance('admin-sidebar-menu')->getOrderedItems() as $item) {
                if ($item->hidden() || $item->isDivider() || $item->isHeader()) {
                    continue;
                }

                $children = [];
                foreach ($item->getChilds() as $child) {
                    if ($child->hidden() || $child->isDivider() || $child->isHeader()) {
                        continue;
                    }
                    $entry = self::entry($child);
                    if ($entry['url'] !== null) {
                        $children[] = $entry;
                    }
                }

                $entry = self::entry($item);
                if (! empty($children)) {
                    $entry['url'] = null;
                    $entry['children'] = $children;
                    $items[] = $entry;
                } elseif ($entry['url'] !== null) {
                    $entry['children'] = [];
                    $items[] = $entry;
                }
            }

            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function entry($item): array
    {
        $title = trim(html_entity_decode(strip_tags((string) $item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = (string) $item->getUrl();
        if ($url === '' || $url === '#' || str_starts_with(strtolower($url), 'javascript:')) {
            $url = null;
        }

        return ['title' => $title, 'url' => $url];
    }
}
