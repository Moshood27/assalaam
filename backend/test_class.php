<?php

require __DIR__ . '/vendor/autoload.php';

try {
    if (class_exists('League\Flysystem\AwsS3V3\PortableVisibilityConverter')) {
        echo "Class found!\n";
        new \League\Flysystem\AwsS3V3\PortableVisibilityConverter();
        echo "Successfully instantiated!\n";
    } else {
        echo "Class NOT found!\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
