<?php

namespace App\Http\Controllers;

use App\PwaEvent;
use Illuminate\Http\Request;

class PwaTelemetryController extends Controller
{
    /**
     * Store telemetry event
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event' => 'required|string|max:191',
            'payload' => 'nullable|array',
        ]);

        $event = PwaEvent::create([
            'user_id' => auth()->id(),
            'event' => $data['event'],
            'payload' => $data['payload'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        return response()->json(['ok' => true, 'id' => $event->id], 201);
    }
}
