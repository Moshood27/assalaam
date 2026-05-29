<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=coop_assalaam', 'sail_assalaam', 'pass_assalaam');
    $stmt = $pdo->query('DESCRIBE loan');
    echo "Columns in 'loan' table:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
