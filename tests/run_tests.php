<?php
// 测试运行脚本

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Library Management System Test Runner ===\n\n";

// 初始化测试数据库
echo "Initializing test database...\n";
require_once __DIR__ . '/_support/Database/init_test_db.php';

// 运行测试
echo "\nRunning tests...\n";
// 使用适合当前操作系统的路径分隔符
$command = PHP_OS === 'WINNT' ? 'vendor\bin\codecept.bat run unit --steps' : 'vendor/bin/codecept run unit --steps';
passthru($command, $return_code);

echo "\nTest execution completed with code: $return_code\n";

if ($return_code === 0) {
    echo "All tests passed successfully!\n";
} else {
    echo "Some tests failed. Please check the output above for details.\n";
}

echo "\n=== Test Runner Finished ===\n";
?>