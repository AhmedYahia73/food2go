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
                    $createdAt = $change['created_at'];

                    if (!Schema::hasTable($tableName)) {
                        throw new \Exception("Table {$tableName} does not exist.");
                    }

                    if ($op === 'delete') {
                        DB::table($tableName)->where('id', $recordId)->delete();
                        ChangeLog::create([
                            'table_name' => $tableName,
                            'record_id' => $recordId,
                            'op' => 'delete',
                            'client_id' => $clientId,
                        ]);
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
                        
                        // Remove null values so MySQL uses column defaults
                        // And remove columns that don't exist on the server to prevent schema mismatch crashes
                        $filteredData = [];
                        foreach ($data as $k => $v) {
                            if (!is_null($v) && Schema::hasColumn($tableName, $k)) {
                                $filteredData[$k] = $v;
                            }
                        }
                        $data = $filteredData;

                        DB::table($tableName)->insert($data);
                        ChangeLog::create([
                            'table_name' => $tableName,
                            'record_id' => $recordId,
                            'op' => 'insert',
                            'client_id' => $clientId,
                            'new_payload' => $data,
                        ]);
                    } elseif ($op === 'update') {
                        $fields = $payload['fields'] ?? [];
                        $updates = [];
                        foreach ($fields as $key => $fieldOp) {
                            // Only update columns that actually exist on the server
                            if (!Schema::hasColumn($tableName, $key)) {
                                continue;
                            }
                            
                            if ($fieldOp['op'] === 'set') {
                                $updates[$key] = $fieldOp['value'];
                            } elseif ($fieldOp['op'] === 'inc') {
                                DB::table($tableName)->where('id', $recordId)->increment($key, $fieldOp['value']);
                            }
                        }
                        if (count($updates) > 0) {
                            DB::table($tableName)->where('id', $recordId)->update($updates);
                            ChangeLog::create([
                                'table_name' => $tableName,
                                'record_id' => $recordId,
                                'op' => 'update',
                                'client_id' => $clientId,
                                'new_payload' => $updates,
                            ]);
                        }
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
        
        $logs = ChangeLog::where('created_at', '>', $since)->orderBy('id', 'asc')->get();
        
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
                'client_id' => $log->client_id,
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
