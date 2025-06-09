<?php
// 初始化测试数据库脚本
require_once __DIR__ . '/TestDatabaseSetup.php';

// 创建测试数据库设置实例
$dbSetup = new Tests\Support\Database\TestDatabaseSetup();

// 设置测试数据库
if ($dbSetup->setupTestDatabase()) {
    echo "Test database structure created successfully.\n";
    
    // 填充测试数据
    if ($dbSetup->seedTestData()) {
        echo "Test data seeded successfully.\n";
    } else {
        echo "Failed to seed test data.\n";
    }
} else {
    echo "Failed to setup test database.\n";
}
?>