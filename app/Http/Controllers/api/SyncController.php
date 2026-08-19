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
    private function castBigIntsToStrings($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->castBigIntsToStrings($value);
            }
        } elseif (is_object($data)) {
            foreach (get_object_vars($data) as $key => $value) {
                $data->$key = $this->castBigIntsToStrings($value);
            }
        } elseif ((is_int($data) || is_float($data)) && $data > 9007199254740991) {
            return (string) $data;
        }
        return $data;
    }
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
            Log::info('Push received changes', ['count' => count($changes)]);
            foreach ($changes as $change) {
                Log::info('Processing change', ['table' => $change['table_name'], 'op' => $change['op']]);
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

                        // user_address pivot: desktop sends it explicitly AND we auto-create it when
                        // processing the addresses record → use upsert to avoid duplicate key errors
                        if ($tableName === 'user_address') {
                            $upsertData = [];
                            foreach ($data as $k => $v) {
                                if (!is_null($v) && Schema::hasColumn($tableName, $k)) {
                                    $upsertData[$k] = $v;
                                }
                            }
                            DB::table($tableName)->updateOrInsert(
                                ['user_id' => $upsertData['user_id'] ?? null, 'address_id' => $upsertData['address_id'] ?? null],
                                $upsertData
                            );
                            $applied[] = $change['id'];
                            continue; // skip the rest, no ChangeLog needed (already logged by Eloquent model)
                        }

                        $data['id'] = $recordId; // ensure ID matches
                        
                        // Remove null values so MySQL uses column defaults
                        // And remove columns that don't exist on the server to prevent schema mismatch crashes
                        $filteredData = [];
                        foreach ($data as $k => $v) {
                            if ($k === 'deleted_at' && ($v === 0 || $v === '0' || $v === '0000-00-00 00:00:00' || $v === '1970-01-01 00:00:00' || empty($v))) {
                                $v = null;
                            }
                            if (!is_null($v) && Schema::hasColumn($tableName, $k)) {
                                $filteredData[$k] = $v;
                            }
                        }
                        $data = $filteredData;

                        if (!empty($data)) {
                            // Use updateOrInsert to ensure data is always written even if ID exists
                            DB::table($tableName)->updateOrInsert(['id' => $recordId], $data);
                        }
                        
                        // Handle user_address pivot sync for electronPOS backwards compatibility
                        // (addresses table stores customer_id but server uses pivot table)
                        if ($tableName === 'addresses' && isset($payload['customer_id'])) {
                            \App\Models\UserAddress::updateOrCreate(
                                ['user_id' => $payload['customer_id'], 'address_id' => $recordId]
                            );
                        }

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
                            if ($key === 'deleted_at' && ($fieldOp['value'] === 0 || $fieldOp['value'] === '0' || $fieldOp['value'] === '0000-00-00 00:00:00' || $fieldOp['value'] === '1970-01-01 00:00:00' || empty($fieldOp['value']))) {
                                $fieldOp['value'] = null;
                            }
                            
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
                            
                            if ($tableName === 'addresses' && isset($updates['customer_id'])) {
                                \App\Models\UserAddress::updateOrCreate(
                                    ['user_id' => $updates['customer_id'], 'address_id' => $recordId]
                                );
                            }

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
                    Log::error('Change processing failed', ['table' => $change['table_name'], 'error' => $e->getMessage()]);
                    $failed[] = [
                        'id' => $change['id'] ?? null,
                        'error' => $e->getMessage()
                    ];
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sync push failed completely', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Sync failed', 'message' => $e->getMessage()], 500);
        }

        return response()->json($this->castBigIntsToStrings([
            'success' => true,
            'data' => [
                'applied' => $applied,
                'failed' => $failed,
            ]
        ]));
    }

    public function pull(Request $request)
    {
        ini_set('memory_limit', '-1');
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
                'record_id' => (string) $log->record_id,
                'client_id' => $log->client_id,
                'data' => $data
            ];
        }

        return response()->json($this->castBigIntsToStrings([
            'success' => true,
            'data' => [
                'changes' => $changes,
                'serverTime' => now()->toIso8601String(),
            ]
        ]));
    }

    public function bootstrap(Request $request, $table)
    {
        ini_set('memory_limit', '-1');
        if (!$this->verifySecretKey($request)) {
            return response()->json(['error' => 'Unauthorized. Invalid secret_key.'], 401);
        }

        if (!Schema::hasTable($table)) {
            return response()->json(['error' => "Table {$table} does not exist."], 404);
        }

        $rows = DB::table($table)->get();

        return response()->json($this->castBigIntsToStrings([
            'success' => true,
            'data' => [
                'serverSnapshotAt' => now()->toISOString(),
                'rows' => $rows
            ]
        ]));
    }
}
