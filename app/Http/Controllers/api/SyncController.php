<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\ChangeLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncController extends Controller
{
    private function verifySecretKey(Request $request)
    {
        $headerKey = $request->header('secret_key') 
                  ?? $request->header('Secret-Key') 
                  ?? $request->input('secret_key'); // Fallback to query/body
        
        $validKey = Setting::where('name', 'desktop_secret_key')->value('setting') ?? 'Food2go@Sync2024';

        \Illuminate\Support\Facades\Log::info('Sync Auth Debug', [
            'received_key' => $headerKey,
            'valid_key' => $validKey
        ]);

        if (!$headerKey) {
            return false;
        }

        return $headerKey === $validKey;
    }

    public function push(Request $request)
    {
        if (!$this->verifySecretKey($request)) {
            return response()->json(['error' => 'Unauthorized. Invalid secret_key.'], 401);
        }

        $changes = $request->input('changes', []);
        $clientId = $request->input('clientId');
        
        $applied = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($changes as $change) {
                try {
                    $tableName = $change['table_name'];
                    $recordId = $change['record_id'];
                    $op = $change['op'];
                    $payloadStr = $change['payload'];

                    if (!Schema::hasTable($tableName)) {
                        throw new \Exception("Table {$tableName} does not exist.");
                    }

                    // Tag this change with the client_id so pull can filter it out (prevents sync loop)
                    // We temporarily suppress LogChanges for DB operations triggered by push
                    if ($op === 'delete') {
                        DB::table($tableName)->where('id', $recordId)->delete();
                        $applied[] = $change['id'];
                        continue;
                    }

                    $payload = json_decode($payloadStr, true);
                    if (!$payload) {
                        throw new \Exception("Invalid payload.");
                    }

                    if ($op === 'insert') {
                        // Desktop sends flat row for insert
                        $data = $payload;
                        $data['id'] = $recordId; // ensure ID matches
                        DB::table($tableName)->insert($data);
                    } elseif ($op === 'update') {
                        $fields = $payload['fields'] ?? [];
                        $updates = [];
                        foreach ($fields as $key => $fieldOp) {
                            if ($fieldOp['op'] === 'set') {
                                $updates[$key] = $fieldOp['value'];
                            } elseif ($fieldOp['op'] === 'inc') {
                                DB::table($tableName)->where('id', $recordId)->increment($key, $fieldOp['value']);
                            }
                        }
                        if (count($updates) > 0) {
                            DB::table($tableName)->where('id', $recordId)->update($updates);
                        }
                    }

                    // Update the change_log entry for this record to tag it with client_id
                    // so it is excluded from the next pull for this same client
                    if ($clientId) {
                        DB::table('change_logs')
                            ->where('table_name', $tableName)
                            ->where('record_id', $recordId)
                            ->whereNull('client_id')
                            ->orderBy('id', 'desc')
                            ->limit(1)
                            ->update(['client_id' => $clientId]);
                    }

                    $applied[] = $change['id'];
                } catch (\Exception $e) {
                    $failed[] = [
                        'id' => $change['id'] ?? null,
                        'error' => $e->getMessage()
                    ];
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Sync failed', 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'applied' => $applied,
                'failed' => $failed,
            ]
        ]);
    }

    public function pull(Request $request)
    {
        if (!$this->verifySecretKey($request)) {
            return response()->json(['error' => 'Unauthorized. Invalid secret_key.'], 401);
        }

        $since = $request->query('since', '1970-01-01 00:00:00');
        $clientId = $request->query('clientId');

        // Convert ISO 8601 (e.g. 2026-08-15T12:00:00.000Z) to MySQL datetime string
        try {
            $sinceCarbon = \Carbon\Carbon::parse($since)->utc();
            $sinceFormatted = $sinceCarbon->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $sinceFormatted = '1970-01-01 00:00:00';
        }
        
        $query = ChangeLog::where('created_at', '>', $sinceFormatted)
            ->orderBy('id', 'asc');

        // Exclude changes that originated from this same desktop client to prevent sync loops
        if ($clientId) {
            $query->where(function($q) use ($clientId) {
                $q->whereNull('client_id')->orWhere('client_id', '!=', $clientId);
            });
        }

        $logs = $query->get();
        
        $changes = [];
        foreach ($logs as $log) {
            $data = null;
            if ($log->op === 'insert') {
                $data = $log->new_payload;
            } elseif ($log->op === 'update') {
                $old = $log->old_payload ?? [];
                $new = $log->new_payload ?? [];
                $fields = [];
                foreach ($new as $key => $val) {
                    if (!array_key_exists($key, $old) || $old[$key] !== $val) {
                        $fields[$key] = ['op' => 'set', 'value' => $val];
                    }
                }
                $data = ['fields' => $fields];
            }

            $changes[] = [
                'table_name' => $log->table_name,
                'op' => $log->op,
                'record_id' => $log->record_id,
                'data' => $data
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'changes' => $changes,
                'serverTime' => now()->toIso8601String(),
            ]
        ]);
    }

    public function bootstrap(Request $request, $table)
    {
        if (!$this->verifySecretKey($request)) {
            return response()->json(['error' => 'Unauthorized. Invalid secret_key.'], 401);
        }

        if (!Schema::hasTable($table)) {
            return response()->json(['error' => "Table {$table} does not exist."], 404);
        }

        $rows = DB::table($table)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'serverSnapshotAt' => now()->toISOString(),
                'rows' => $rows
            ]
        ]);
    }
}
