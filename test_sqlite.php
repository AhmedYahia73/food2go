<?php
require __DIR__.'/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);
$capsule->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

Capsule::schema()->create('test_products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->integer('price');
    $table->timestamps();
});

Capsule::schema()->create('change_logs', function (Blueprint $table) {
    $table->id();
    $table->string('table_name');
    $table->string('record_id');
    $table->string('op');
    $table->json('old_payload')->nullable();
    $table->json('new_payload')->nullable();
    $table->timestamps();
});

class ChangeLog extends Model {
    protected $guarded = [];
    protected $casts = ['old_payload' => 'array', 'new_payload' => 'array'];
}

trait TestLogChanges {
    public static function bootTestLogChanges() {
        static::created(function ($model) { self::recordChange($model, 'insert'); });
        static::updated(function ($model) { self::recordChange($model, 'update'); });
    }
    protected static function recordChange($model, $op) {
        $newPayload = $op === 'insert' ? $model->getAttributes() : $model->getAttributes();
        $oldPayload = $op === 'update' ? array_merge($newPayload, $model->getOriginal()) : null;
        ChangeLog::create([
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'op' => $op,
            'old_payload' => $oldPayload,
            'new_payload' => $newPayload,
        ]);
    }
}

class TestProduct extends Model {
    use TestLogChanges;
    protected $guarded = [];
}

$p = TestProduct::create(['name' => 'Burger', 'price' => 10]);
$p->update(['price' => 15]);

echo json_encode(ChangeLog::all()->toArray(), JSON_PRETTY_PRINT);
