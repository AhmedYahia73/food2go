<?php

namespace App\Traits;

use App\Models\ChangeLog;
use Illuminate\Support\Facades\Log;

trait LogChanges
{
    public static function bootLogChanges()
    {
        static::created(function ($model) {
            self::recordChange($model, 'insert');
        });

        static::updated(function ($model) {
            self::recordChange($model, 'update');
        });

        static::deleted(function ($model) {
            self::recordChange($model, 'delete');
        });
    }

    protected static function recordChange($model, $op)
    {
        try {
            // Prevent logging changes that are triggered by a sync push itself,
            // to avoid sync loops. We can check if a global flag is set or just log everything 
            // and the pull logic will filter it out based on client_id (advanced).
            // For now, log everything.
            
            $tableName = $model->getTable();
            $recordId = clone $model;
            $recordId = $recordId->getKey();
            
            if (!$recordId) {
                return; // cannot track records without primary keys
            }

            $newPayload = null;
            $oldPayload = null;

            if ($op === 'insert') {
                $newPayload = $model->getAttributes();
            } elseif ($op === 'update') {
                $newPayload = $model->getAttributes();
                $oldPayload = array_merge($newPayload, $model->getOriginal());
            }

            ChangeLog::create([
                'table_name' => $tableName,
                'record_id' => $recordId,
                'op' => $op,
                'old_payload' => $oldPayload,
                'new_payload' => $newPayload,
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording change log for ' . get_class($model) . ': ' . $e->getMessage());
        }
    }
}
