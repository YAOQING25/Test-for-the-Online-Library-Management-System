<?php
namespace Tests\Support\Database;

require_once __DIR__ . '/../Config/test_config.php';

use Tests\Support\Config\TestDatabase;

class TestDatabaseSetup {
    private $db;

    public function __construct() {
        $this->db = TestDatabase::getInstance();
    }

    /**
     * 创建测试数据库并导入表结构
     */
    public function setupTestDatabase() {
        try {
            // 创建测试数据库（如果不存在）
            $pdo = new \PDO("mysql:host=" . TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // 检查数据库是否存在，如果不存在则创建
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . TEST_DB_NAME);
            $pdo->exec("USE " . TEST_DB_NAME);
            
            // 创建表结构
            $this->createTables($pdo);
            
            echo "Test database setup completed successfully.\n";
            return true;
        } catch (\PDOException $e) {
            echo "Database setup error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 创建测试数据库表
     */
    private function createTables($pdo) {
        // 管理员表
        $pdo->exec("DROP TABLE IF EXISTS `admin`");
        $pdo->exec("CREATE TABLE `admin` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `FullName` varchar(100) DEFAULT NULL,
            `AdminEmail` varchar(120) DEFAULT NULL,
            `UserName` varchar(100) NOT NULL,
            `Password` varchar(100) NOT NULL,
            `updationDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        // 作者表
        $pdo->exec("DROP TABLE IF EXISTS `tblauthors`");
        $pdo->exec("CREATE TABLE `tblauthors` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `AuthorName` varchar(159) DEFAULT NULL,
            `creationDate` timestamp NULL DEFAULT current_timestamp(),
            `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        // 图书表
        $pdo->exec("DROP TABLE IF EXISTS `tblbooks`");
        $pdo->exec("CREATE TABLE `tblbooks` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `BookName` varchar(255) DEFAULT NULL,
            `CatId` int(11) DEFAULT NULL,
            `AuthorId` int(11) DEFAULT NULL,
            `ISBNNumber` varchar(13) DEFAULT NULL,
            `BookPrice` DECIMAL(10,2) DEFAULT NULL,
            `RegDate` timestamp NULL DEFAULT current_timestamp(),
            `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        // 分类表
        $pdo->exec("DROP TABLE IF EXISTS `tblcategory`");
        $pdo->exec("CREATE TABLE `tblcategory` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `CategoryName` varchar(150) DEFAULT NULL,
            `Status` int(1) DEFAULT NULL,
            `CreationDate` timestamp NULL DEFAULT current_timestamp(),
            `UpdationDate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        // 借书记录表
        $pdo->exec("DROP TABLE IF EXISTS `tblissuedbookdetails`");
        $pdo->exec("CREATE TABLE `tblissuedbookdetails` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `BookId` int(11) DEFAULT NULL,
            `StudentID` varchar(150) DEFAULT NULL,
            `IssuesDate` timestamp NULL DEFAULT current_timestamp(),
            `ReturnDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
            `RetrunStatus` int(1) DEFAULT NULL,
            `fine` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        // 学生表
        $pdo->exec("DROP TABLE IF EXISTS `tblstudents`");
        $pdo->exec("CREATE TABLE `tblstudents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `StudentId` varchar(100) DEFAULT NULL,
            `FullName` varchar(120) DEFAULT NULL,
            `EmailId` varchar(120) DEFAULT NULL,
            `MobileNumber` char(11) DEFAULT NULL,
            `Password` varchar(120) DEFAULT NULL,
            `Status` int(1) DEFAULT NULL,
            `RegDate` timestamp NULL DEFAULT current_timestamp(),
            `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `StudentId` (`StudentId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    }

    /**
     * 清空所有表数据
     */
    public function cleanupTestData() {
        $conn = $this->db->getConnection();
        $tables = ['admin', 'tblauthors', 'tblbooks', 'tblcategory', 'tblissuedbookdetails', 'tblstudents'];
        
        foreach ($tables as $table) {
            $conn->exec("TRUNCATE TABLE `$table`");
        }
        
        return true;
    }

    /**
     * 插入测试数据
     */
    public function seedTestData() {
        $conn = $this->db->getConnection();
        
        // 插入管理员测试数据
        $conn->exec("INSERT INTO `admin` (`FullName`, `AdminEmail`, `UserName`, `Password`) VALUES
            ('Test Admin', 'testadmin@example.com', 'testadmin', 'e10adc3949ba59abbe56e057f20f883e')"); // 密码: 123456
        
        // 插入作者测试数据
        $conn->exec("INSERT INTO `tblauthors` (`AuthorName`) VALUES
            ('Test Author 1'),
            ('Test Author 2')");
        
        // 插入分类测试数据
        $conn->exec("INSERT INTO `tblcategory` (`CategoryName`, `Status`) VALUES
            ('Test Category 1', 1),
            ('Test Category 2', 1)");
        
        // 插入图书测试数据
        $conn->exec("INSERT INTO `tblbooks` (`BookName`, `CatId`, `AuthorId`, `ISBNNumber`, `BookPrice`) VALUES
            ('Test Book 1', 1, 1, 1234567890, 25),
            ('Test Book 2', 2, 2, 9876543210, 30)");
        
        // 插入学生测试数据
        $conn->exec("INSERT INTO `tblstudents` (`StudentId`, `FullName`, `EmailId`, `MobileNumber`, `Password`, `Status`) VALUES
            ('TEST001', 'Test Student 1', 'student1@example.com', '1234567890', 'e10adc3949ba59abbe56e057f20f883e', 1),
            ('TEST002', 'Test Student 2', 'student2@example.com', '9876543210', 'e10adc3949ba59abbe56e057f20f883e', 1)");
        
        return true;
    }
}
?>