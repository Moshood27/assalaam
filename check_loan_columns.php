<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=33060;dbname=coop_assalaam", "sail_assalaam", "pass_assalaam");
    $stmt = $pdo->query("DESCRIBE loan");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
