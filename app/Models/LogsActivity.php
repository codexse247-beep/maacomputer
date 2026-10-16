<?php

namespace App\Models;

/** Records who created, changed or deleted a record (Admin › অ্যাক্টিভিটি লগ). */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => ActivityLog::record('created', $model));
        static::updated(function ($model) {
            $changes = ActivityLog::changesOf($model);
            if ($changes) {
                ActivityLog::record('updated', $model, $changes);
            }
        });
        static::deleted(fn ($model) => ActivityLog::record('deleted', $model));
    }
}
