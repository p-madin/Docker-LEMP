<?php
// app/test_session_tree.php
require_once __DIR__ . '/Class files/db.php';
require_once __DIR__ . '/Class files/config.php';
require_once __DIR__ . '/Class files/session.php';

// Setup
$sessKey = 'TEST_SESSION_' . uniqid();
$_COOKIE['session'] = $sessKey;

// Insert Session manually to avoid header redirection in seed()
// Note: config.php creates $db connection
try {
    $q = $db->prepare("INSERT INTO tblSession (sessChars, sessTransactionActive) VALUES (:key, 0)");
    $q->bindParam("key", $sessKey);
    $q->execute();
} catch (Exception $e) {
    die("Setup Failed: " . $e->getMessage());
}

$sc = new SessionController($db);
$sc->sessPK = $db->lastInsertId();

$failures = 0;
function assertEq($algo, $expected, $actual) {
    global $failures;
    // Normalize logic: strict equality
    if ($expected === $actual) {
        echo "[PASS] $algo\n";
    } else {
        echo "[FAIL] $algo\n";
        echo "Expected:\n"; print_r($expected);
        echo "Actual:\n"; print_r($actual);
        $failures++;
    }
}

echo "Running Session Tree Tests...\n";

// Test 1: Scalar
$sc->setPrimary('scalar', 'hello');
assertEq('Scalar', 'hello', $sc->getPrimary('scalar'));

// Test 2: List
$list = ['a', 'b', 'c'];
$sc->setPrimary('list', $list);
assertEq('List', $list, $sc->getPrimary('list'));

// Test 3: Assoc
$assoc = ['x' => '1', 'y' => '2'];
$sc->setPrimary('assoc', $assoc);
assertEq('Assoc', $assoc, $sc->getPrimary('assoc'));

// Test 4: Complex
$tree = [
    'user' => [
        'name' => 'John',
        'roles' => ['admin', 'editor']
    ],
    'settings' => [
        'theme' => 'dark',
        'notifications' => [
            'email' => '1',
            'sms' => '0'
        ]
    ]
];
$sc->setPrimary('tree', $tree);
assertEq('Tree', $tree, $sc->getPrimary('tree'));

// Test 5: Empty Array
$empty = [];
$sc->setPrimary('empty', $empty);
assertEq('Empty', $empty, $sc->getPrimary('empty'));

// Test 6: Mixed List (Branch forced)
$mixed = ['a', 'b'=>'2'];
$sc->setPrimary('mixed', $mixed);
// PHP treats this as [0=>'a', 'b'=>'2']
assertEq('Mixed', $mixed, $sc->getPrimary('mixed'));

// Test 7: Detach
$sc->detachPrimary('tree');
assertEq('Detach', null, $sc->getPrimary('tree'));


// --- Random Tree Generator ---
function generateRandomTree($depth = 0, $maxDepth = 3, $maxWidth = 3) {
    if ($depth >= $maxDepth) {
        // Return scalar leaf
        return 'value_' . uniqid();
    }

    $numItems = rand(1, $maxWidth);
    $arr = [];
    
    // Determine if this array should be a list or assoc
    $isAssoc = rand(0, 1);
    
    for ($i = 0; $i < $numItems; $i++) {
        // "at least 2 branches" logic: 
        // We'll trust randomness but ensure meaningful depth.
        // For the root call, we might want to force array children.
        
        $key = $isAssoc ? 'key_' . uniqid() : $i;
        
        // Randomly decide if child is array or scalar
        // Higher probability of array if not at max depth
        $isBranch = rand(0, 10) > 3; 

        if ($isBranch) {
            $arr[$key] = generateRandomTree($depth + 1, $maxDepth, $maxWidth);
        } else {
            $arr[$key] = 'leaf_' . uniqid();
        }
    }
    
    // Ensure at least 2 branches for complexity (approximated by size > 1)
    if (count($arr) < 2) {
        $arr['extra_' . uniqid()] = generateRandomTree($depth + 1, $maxDepth, $maxWidth);
    }
    
    return $arr;
}

echo "\n--- Additional Stress Tests ---\n";

// Test 8: 10 Randomized Arrays
echo "Running 10 Randomized Tree Tests...\n";
for($i=1; $i<=10; $i++){
    $data = generateRandomTree(0, 4, 4); // Depth 4, Width 4
    $key = "rnd_$i";
    $sc->setPrimary($key, $data);
    $retrieved = $sc->getPrimary($key);
    
    // Compare Json Encoded strings
    $expectedJson = json_encode($data);
    $actualJson = json_encode($retrieved);
    
    if($expectedJson === $actualJson){
        echo ".";
    } else {
        echo "F";
        $failures++;
        echo "\nFailed Random Test #$i\n";
    }
    $sc->detachPrimary($key);
}
echo "\n";


// Test 9: 100 Randomized Arrays Benchmark
echo "Running 100 Randomized Tree Benchmark (<100s)...\n";

$totalSet = 0;
$totalGet = 0;
$totalDetach = 0;
$startTotal = microtime(true);

for($i=1; $i<=100; $i++){
    $data = generateRandomTree(0, 4, 4); // Slightly smaller for bulk test
    $key = "bench_$i";
    
    // Profile setPrimary
    $t0 = microtime(true);
    $sc->setPrimary($key, $data);
    $totalSet += (microtime(true) - $t0);

    // Profile getPrimary
    $t1 = microtime(true);
    $retrieved = $sc->getPrimary($key);
    $totalGet += (microtime(true) - $t1);
    
    if(json_encode($data) !== json_encode($retrieved)){
        echo "F";
        $failures++;
    }
    
    // Profile detachPrimary
    $t2 = microtime(true);
    $sc->detachPrimary($key);
    $totalDetach += (microtime(true) - $t2);
}

$endTotal = microtime(true);
$duration = $endTotal - $startTotal;

echo "\nBenchmark Breakdown (100 runs):\n";
echo "Total Time:   " . number_format($duration, 4) . " s\n";
echo "  setPrimary: " . number_format($totalSet, 4) . " s (Avg: " . number_format($totalSet/100, 5) . " s)\n";
echo "  getPrimary: " . number_format($totalGet, 4) . " s (Avg: " . number_format($totalGet/100, 5) . " s)\n";
echo "  detach:     " . number_format($totalDetach, 4) . " s (Avg: " . number_format($totalDetach/100, 5) . " s)\n";

if($duration < 100){
    echo "[PASS] Benchmark Time (<100s)\n";
} else {
    echo "[FAIL] Benchmark Time (>100s)\n";
    $failures++;
}


// Cleanup
$db->prepare("DELETE FROM tblSession WHERE sessChars = ?")->execute([$sessKey]);

if ($failures == 0) echo "\nALL TESTS PASSED\n";
else echo "\n$failures TESTS FAILED\n";

?>
