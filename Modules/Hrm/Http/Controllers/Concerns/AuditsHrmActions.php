<?php

namespace Modules\Hrm\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Log;

trait AuditsHrmActions
{
    protected function logHrmAudit(string $event, array $properties = [], $subject = null): void
    {
        try {
            if (function_exists('activity')) {
                $activity = activity('hrm')->causedBy(auth()->user());

                if ($subject) {
                    $activity->performedOn($subject);
                }

                $activity->withProperties($properties)->log($event);

                return;
            }
        } catch (\Throwable $e) {
            Log::warning('hrm.audit.log_failed', [
                'event' => $event,
                'exception' => $e->getMessage(),
            ]);
        }

        Log::info('hrm.audit', [
            'event' => $event,
            'properties' => $properties,
            'causer_id' => auth()->id(),
        ]);
    }
}
