<?php

/**
 * Library Management System Test Runner
 * 
 * This script automates the process of running tests for the Library Management System.
 * It checks requirements, sets up the test database, and runs the test suite.
 */

echo "=== Library Management System Test Runner ===\n\n";

// Check PHP version
echo "Checking PHP version... ";
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "FAILED\n";
    echo "Error: PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}
echo "OK (PHP " . PHP_VERSION . ")\n";

// Check if Composer is installed
echo "Checking Composer... ";
exec('composer --version', $composerOutput, $composerReturnVar);
if ($composerReturnVar !== 0) {
    echo "FAILED\n";
    echo "Error: Composer is not installed or not in PATH\n";
    exit(1);
}
echo "OK (" . trim($composerOutput[0]) . ")\n";

// Check if vendor directory exists, if not run composer install
echo "Checking dependencies... ";
if (!is_dir('vendor')) {
    echo "Installing...\n";
    passthru('composer install', $composerInstallReturnVar);
    if ($composerInstallReturnVar !== 0) {
        echo "Error: Failed to install dependencies\n";
        exit(1);
    }
    echo "Dependencies installed successfully\n";
} else {
    echo "OK\n";
}

// Check MySQL connection
echo "Checking MySQL connection... ";
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "OK\n";
} catch (PDOException $e) {
    echo "FAILED\n";
    echo "Error: Could not connect to MySQL: " . $e->getMessage() . "\n";
    echo "Please check your MySQL connection settings\n";
    exit(1);
}

// Setup test database
echo "\nSetting up test database...\n";
passthru('php tests/setup_test_db.php', $setupDbReturnVar);
if ($setupDbReturnVar !== 0) {
    echo "Error: Failed to set up test database\n";
    exit(1);
}

// Run tests
echo "\nRunning tests...\n";
if (PHP_OS_FAMILY === 'Windows') {
    passthru('.\vendor\bin\phpunit', $phpunitReturnVar);
} else {
    passthru('./vendor/bin/phpunit', $phpunitReturnVar);
}

// Generate coverage report if requested
if (in_array('--coverage', $argv)) {
    echo "\nGenerating coverage report...\n";
    passthru('./vendor/bin/phpunit --coverage-html tests/coverage', $coverageReturnVar);
    if ($coverageReturnVar === 0) {
        echo "Coverage report generated in tests/coverage/index.html\n";
    }
}

echo "\n=== Test Runner Completed ===\n";
exit($phpunitReturnVar);