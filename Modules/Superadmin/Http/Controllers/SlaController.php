<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SlaController extends BaseController
{
    /**
     * Default SLA content loaded from SLA.md as fallback.
     */
    private function defaultContent(): string
    {
        $path = base_path('SLA.md');
        return File::exists($path) ? File::get($path) : '';
    }

    /**
     * Display the SLA with a print button.
     */
    public function show()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $content    = System::getProperty('sla_content') ?: $this->defaultContent();
        $signatures = json_decode(System::getProperty('sla_signatures') ?? '[]', true) ?: [];

        $defaultSigs = [
            ['role' => 'System Administrator', 'name' => '', 'date' => ''],
            ['role' => 'Business Owner',        'name' => '', 'date' => ''],
            ['role' => 'IT Manager',            'name' => '', 'date' => ''],
        ];

        // Merge saved signature data into defaults
        foreach ($defaultSigs as $i => &$sig) {
            if (isset($signatures[$i])) {
                $sig = array_merge($sig, $signatures[$i]);
            }
        }
        unset($sig);

        return view('superadmin::sla.show', compact('content', 'defaultSigs'));
    }

    /**
     * Show the SLA editor form.
     */
    public function edit()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $content    = System::getProperty('sla_content') ?: $this->defaultContent();
        $signatures = json_decode(System::getProperty('sla_signatures') ?? '[]', true) ?: [];

        $defaultSigs = [
            ['role' => 'System Administrator', 'name' => '', 'date' => ''],
            ['role' => 'Business Owner',        'name' => '', 'date' => ''],
            ['role' => 'IT Manager',            'name' => '', 'date' => ''],
        ];

        foreach ($defaultSigs as $i => &$sig) {
            if (isset($signatures[$i])) {
                $sig = array_merge($sig, $signatures[$i]);
            }
        }
        unset($sig);

        return view('superadmin::sla.edit', compact('content', 'defaultSigs'));
    }

    /**
     * Save updated SLA content and signatures.
     */
    public function update(Request $request)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'sla_content'              => 'required|string|max:100000',
            'signatures.*.name'        => 'nullable|string|max:100',
            'signatures.*.date'        => 'nullable|string|max:30',
        ]);

        System::updateOrCreate(
            ['key' => 'sla_content'],
            ['value' => $request->input('sla_content')]
        );

        $signatures = $request->input('signatures', []);
        System::updateOrCreate(
            ['key' => 'sla_signatures'],
            ['value' => json_encode(array_values($signatures))]
        );

        // Regenerate the SLA.md file so pandoc/CLI stays in sync
        $md = $request->input('sla_content');
        File::put(base_path('SLA.md'), $md);

        $output = ['success' => 1, 'msg' => 'SLA updated successfully.'];

        return redirect()->route('superadmin.sla.show')->with('status', $output);
    }
}
