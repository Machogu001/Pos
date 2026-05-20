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
     * Replace the {{APP_URL}} placeholder (and any previously hardcoded URL
     * in the "System URL:" line) with the current installation URL.
     */
    private function resolveUrl(string $content): string
    {
        $appUrl = rtrim(config('app.url'), '/');

        // Replace explicit placeholder
        $content = str_replace('{{APP_URL}}', $appUrl, $content);

        // Backward-compat: normalise the "System URL:" line if it still
        // carries a different hardcoded URL (e.g. content saved to DB before
        // the placeholder was introduced).
        $content = preg_replace(
            '/^(\*{0,2}System URL:\*{0,2}\s*)https?:\/\/[^\s`]+/im',
            '$1' . $appUrl,
            $content
        );

        return $content;
    }

    /**
     * Display the SLA with a print button.
     */
    public function show()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $content    = $this->resolveUrl(System::getProperty('sla_content') ?: $this->defaultContent());
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

        $content    = $this->resolveUrl(System::getProperty('sla_content') ?: $this->defaultContent());
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

        // Regenerate public/SLA.pdf
        $mdPath = escapeshellarg(base_path('SLA.md'));
        $pdfPath = escapeshellarg(public_path('SLA.pdf'));
        exec("pandoc {$mdPath} -o {$pdfPath} --pdf-engine=xelatex -V geometry:margin=2cm -V fontsize=11pt -V colorlinks=true -V 'mainfont=Liberation Serif' 2>/dev/null");

        $output = ['success' => 1, 'msg' => 'SLA updated successfully.'];

        return redirect()->route('superadmin.sla.show')->with('status', $output);
    }
}
