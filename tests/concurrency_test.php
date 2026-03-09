<?php
/**
 * Concurrency Test - Invoice Number Uniqueness
 *
 * Simulates N concurrent invoice creations to verify
 * no duplicate invoice numbers are generated.
 *
 * Usage: php tests/concurrency_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/helpers/number_helper.php';
require_once BASE_PATH . '/app/core/Database.php';
require_once BASE_PATH . '/app/core/Model.php';
require_once BASE_PATH . '/app/models/Invoice.php';
require_once BASE_PATH . '/app/models/InvoiceItem.php';
require_once BASE_PATH . '/app/models/Payment.php';
require_once BASE_PATH . '/app/models/Currency.php';
require_once BASE_PATH . '/app/models/ExchangeRate.php';
require_once BASE_PATH . '/app/models/AuditLog.php';
require_once BASE_PATH . '/app/services/CurrencyService.php';
require_once BASE_PATH . '/app/services/InvoiceService.php';

$N = 10; // Number of concurrent processes
$phpBinary = PHP_BINARY;
$workerScript = __DIR__ . '/concurrency_worker.php';

echo "=======================================================\n";
echo " FakturKu - Concurrency Test: Invoice Number Uniqueness\n";
echo "=======================================================\n";
echo "Akan membuat $N invoice secara bersamaan...\n\n";

// Create worker script
$workerCode = '<?php
define("BASE_PATH", dirname(__DIR__));
require_once BASE_PATH . "/config/app.php";
require_once BASE_PATH . "/app/helpers/number_helper.php";
require_once BASE_PATH . "/app/core/Database.php";
require_once BASE_PATH . "/app/core/Model.php";
require_once BASE_PATH . "/app/models/Invoice.php";

$model = new Invoice();
try {
    $number = $model->generateNumber();
    echo "OK: $number\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
';
file_put_contents($workerScript, $workerCode);

// Launch N processes simultaneously
$processes = [];
$pipes = [];

for ($i = 0; $i < $N; $i++) {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open(
        "\"$phpBinary\" \"$workerScript\"",
        $descriptors,
        $pipe
    );
    if (is_resource($process)) {
        $processes[$i] = $process;
        $pipes[$i] = $pipe;
    }
}

// Collect results
$results = [];
$errors = [];
foreach ($processes as $i => $process) {
    $output = stream_get_contents($pipes[$i][1]);
    fclose($pipes[$i][0]);
    fclose($pipes[$i][1]);
    fclose($pipes[$i][2]);
    proc_close($process);

    $output = trim($output);
    if (str_starts_with($output, 'OK: ')) {
        $number = substr($output, 4);
        $results[] = $number;
        echo "  Process $i: $number\n";
    } else {
        $errors[] = $output;
        echo "  Process $i: FAILED - $output\n";
    }
}

// Cleanup
@unlink($workerScript);

echo "\n--- Hasil ---\n";
echo "Total proses: $N\n";
echo "Berhasil: " . count($results) . "\n";
echo "Gagal: " . count($errors) . "\n";

// Check uniqueness
$unique = array_unique($results);
if (count($unique) === count($results)) {
    echo "\n✅ PASS: Semua " . count($results) . " invoice number UNIK!\n";
} else {
    $duplicates = array_diff_assoc($results, $unique);
    echo "\n❌ FAIL: Ditemukan " . count($duplicates) . " duplikat!\n";
    echo "Duplikat: " . implode(', ', $duplicates) . "\n";
}

echo "\nNomor invoice yang digenerate:\n";
foreach ($results as $r) {
    echo "  - $r\n";
}

// Test webhook idempotency
echo "\n\n=======================================================\n";
echo " Idempotency Test: Duplicate Webhook Detection\n";
echo "=======================================================\n";

require_once BASE_PATH . '/app/models/Payment.php';

$paymentModel = new Payment();
$testKey = 'test_idempotency_' . bin2hex(random_bytes(8));

echo "Creating payment with idempotency key: $testKey\n";

try {
    // First insert
    $id1 = $paymentModel->create([
        'invoice_id'      => 0, // dummy for test
        'currency'        => 'IDR',
        'amount'          => 100000,
        'amount_in_base'  => 100000,
        'provider'        => 'test',
        'idempotency_key' => $testKey,
        'status'          => 'success',
    ]);
    echo "  Insert 1: OK (ID=$id1)\n";

    // Try duplicate
    try {
        $id2 = $paymentModel->create([
            'invoice_id'      => 0,
            'currency'        => 'IDR',
            'amount'          => 100000,
            'amount_in_base'  => 100000,
            'provider'        => 'test',
            'idempotency_key' => $testKey,
            'status'          => 'success',
        ]);
        echo "  Insert 2: ❌ FAIL - Duplikat seharusnya ditolak!\n";
    } catch (\PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'uk_idempotency')) {
            echo "  Insert 2: ✅ PASS - Duplikat ditolak (UNIQUE constraint)\n";
        } else {
            echo "  Insert 2: ERROR - " . $e->getMessage() . "\n";
        }
    }

    // Cleanup test data
    $db = Database::getInstance();
    $db->prepare("DELETE FROM payments WHERE idempotency_key = ?")->execute([$testKey]);
    echo "  Test data cleaned up.\n";

} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    echo "  (Pastikan database sudah di-migrate dan ada setidaknya 1 invoice)\n";
}

echo "\n✅ Semua tes selesai.\n";
