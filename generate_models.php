<?php

// Load .env variables manually just in case we need them
$envFile = __DIR__ . '/.env';
$envVariables = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name !== null) {
            $envVariables[trim($name)] = trim($value, '"\'');
        }
    }
}

$host = $envVariables['DB_HOST'] ?? '127.0.0.1';
$port = $envVariables['DB_PORT'] ?? '3306';
$dbName = $envVariables['DB_DATABASE'] ?? 'lamada_food2go';
$username = $envVariables['DB_USERNAME'] ?? 'root';
$password = $envVariables['DB_PASSWORD'] ?? '';

// Try connecting with .env credentials
$pdo = null;
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $username, $password);
} catch (PDOException $e) {
    // Fallback to root with no password (common in local XAMPP)
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbName", 'root', '');
    } catch (PDOException $e2) {
        die("Connection failed: " . $e2->getMessage() . "\n");
    }
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$outputDir = 'c:/xampp/htdocs/electronPOS/local-server/src/models';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

function mapSqlTypeToTs($sqlType) {
    $sqlType = strtolower($sqlType);
    if (strpos($sqlType, 'int') !== false) return 'number';
    if (strpos($sqlType, 'double') !== false || strpos($sqlType, 'float') !== false || strpos($sqlType, 'decimal') !== false) return 'number';
    if (strpos($sqlType, 'bool') !== false || strpos($sqlType, 'tinyint(1)') !== false) return 'boolean';
    if (strpos($sqlType, 'datetime') !== false || strpos($sqlType, 'timestamp') !== false || strpos($sqlType, 'date') !== false) return 'date';
    if (strpos($sqlType, 'json') !== false) return 'object';
    return 'string';
}

function toCamelCase($string, $capitalizeFirstCharacter = false) 
{
    $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    if (!$capitalizeFirstCharacter) {
        $str[0] = strtolower($str[0]);
    }
    return $str;
}

function singularize($word) {
    // A very basic singularize function. For more complex words, we might need a library, but this works for most simple cases.
    if (substr($word, -3) == 'ies') {
        return substr($word, 0, -3) . 'y';
    } elseif (substr($word, -1) == 's' && substr($word, -2) != 'ss') {
        return substr($word, 0, -1);
    }
    return $word;
}

$indexContent = "";

foreach ($tables as $table) {
    // Skip Laravel specific tables if desired
    if (in_array($table, ['migrations', 'failed_jobs', 'personal_access_tokens', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches'])) continue;

    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $modelName = toCamelCase(singularize($table), true);
    
    $schemaDef = [];
    foreach ($columns as $col) {
        $field = $col['Field'];
        if ($field === 'id') continue; // Handled by createModel automatically as _id or id depending on logic
        if ($field === 'created_at' || $field === 'updated_at') continue; // Handled by timestamps option
        
        $tsType = mapSqlTypeToTs($col['Type']);
        $isRequired = $col['Null'] === 'NO' && $col['Default'] === null ? 'true' : 'false';
        
        $def = "{ type: \"$tsType\"";
        if ($isRequired === 'true') {
            $def .= ", required: true";
        }
        if ($col['Key'] === 'UNI') {
            $def .= ", unique: true";
        }
        $def .= " }";
        $schemaDef[] = "  $field: $def";
    }
    
    $schemaString = "{\n" . implode(",\n", $schemaDef) . "\n}";
    
    $tsContent = "import { createModel } from \"../db/createModel\";\n\n";
    $tsContent .= "export const {$modelName}Schema = $schemaString as const;\n\n";
    $tsContent .= "export const {$modelName} = createModel(\"$table\", {$modelName}Schema, { timestamps: true });\n";

    $fileName = toCamelCase(singularize($table)) . ".ts";
    file_put_contents("$outputDir/$fileName", $tsContent);
    
    echo "Generated $fileName for table $table\n";
    
    $indexContent .= "export * from \"./" . toCamelCase(singularize($table)) . "\";\n";
}

file_put_contents("$outputDir/index.ts", $indexContent);
echo "Generated index.ts\n";
echo "Done.\n";
