<?php

namespace Modules\Cms\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Cms\Entities\CmsPage;
use Yajra\DataTables\Facades\DataTables;

class CmsPagesController extends Controller
{
    public function __construct()
    {
        $this->module_util = new ModuleUtil();
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->module_util->hasThePermissionInSubscription($business_id, 'cms_module', 'superadmin_package'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('cms.view_page')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $pages = CmsPage::where('business_id', $business_id)
                ->select('id', 'title', 'slug', 'status', 'created_at');

            return DataTables::of($pages)
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (auth()->user()->can('cms.edit_page')) {
                        $html .= '<button type="button" class="btn btn-xs btn-primary edit_page_btn" data-id="' . $row->id . '">'
                            . '<i class="fas fa-edit"></i> ' . __('messages.edit') . '</button>&nbsp;';
                    }
                    if (auth()->user()->can('cms.delete_page')) {
                        $html .= '<button type="button" class="btn btn-xs btn-danger delete_page_btn" data-id="' . $row->id . '">'
                            . '<i class="fas fa-trash"></i> ' . __('messages.delete') . '</button>';
                    }

                    return $html;
                })
                ->editColumn('status', function ($row) {
                    return $row->status === 'published'
                        ? '<span class="label label-success">' . __('cms::lang.status_published') . '</span>'
                        : '<span class="label label-default">' . __('cms::lang.status_draft') . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y');
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('cms::pages.index');
    }

    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! auth()->user()->can('cms.create_page')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'nullable|string',
            'status'  => 'required|in:draft,published',
        ]);

        $slug = Str::slug($request->title);
        $original = $slug;
        $count = 2;
        while (CmsPage::where('business_id', $business_id)->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }

        CmsPage::create([
            'business_id' => $business_id,
            'title'       => $request->title,
            'slug'        => $slug,
            'content'     => $request->content,
            'status'      => $request->status,
            'created_by'  => auth()->id(),
        ]);

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $page = CmsPage::where('business_id', $business_id)->findOrFail($id);

        return response()->json($page);
    }

    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! auth()->user()->can('cms.edit_page')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'nullable|string',
            'status'  => 'required|in:draft,published',
        ]);

        $page = CmsPage::where('business_id', $business_id)->findOrFail($id);
        $page->update([
            'title'   => $request->title,
            'content' => $request->content,
            'status'  => $request->status,
        ]);

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! auth()->user()->can('cms.delete_page')) {
            abort(403, 'Unauthorized action.');
        }

        CmsPage::where('business_id', $business_id)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }
}
