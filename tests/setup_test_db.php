<?php

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create test database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS library_test");
    echo "Test database created or already exists.\n";
    
    // Connect to the test database
    $pdo = new PDO("mysql:host=$host;dbname=library_test", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create admin table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            FullName VARCHAR(100),
            AdminEmail VARCHAR(120),
            UserName VARCHAR(100),
            Password VARCHAR(100),
            updationDate TIMESTAMP NULL
        )
    ");
    
    // Create tblstudents table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblstudents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            StudentId VARCHAR(100),
            FullName VARCHAR(120),
            EmailId VARCHAR(120),
            MobileNumber CHAR(11),
            Password VARCHAR(120),
            Status INT,
            RegDate TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UpdationDate TIMESTAMP NULL
        )
    ");
    
    // Create tblcategory table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblcategory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            CategoryName VARCHAR(150),
            Status INT,
            CreationDate TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UpdationDate TIMESTAMP NULL
        )
    ");
    
    // Create tblauthors table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblauthors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            AuthorName VARCHAR(159),
            CreationDate TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UpdationDate TIMESTAMP NULL
        )
    ");
    
    // Create tblbooks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblbooks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            BookName VARCHAR(159),
            CatId INT,
            AuthorId INT,
            ISBNNumber VARCHAR(25),
            BookPrice DECIMAL(10,2),
            BookImage VARCHAR(250),
            isIssued INT DEFAULT 0,
            RegDate TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UpdationDate TIMESTAMP NULL
        )
    ");
    
    // Create tblissuedbookdetails table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblissuedbookdetails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            BookId INT,
            StudentId VARCHAR(150),
            IssuesDate TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            ReturnDate TIMESTAMP NULL,
            RetrunStatus INT DEFAULT 0,
            Fine DECIMAL(10,2)
        )
    ");
    
    echo "All test tables created successfully.\n";
    
    // Generate unique constraint names using timestamp
    $timestamp = time();
    $categoryConstraint = "FK_test_books_category_" . $timestamp;
    $authorConstraint = "FK_test_books_author_" . $timestamp;

    // Drop all possible existing foreign key constraints
    try {
        $pdo->exec("
            ALTER TABLE tblbooks
            DROP FOREIGN KEY IF EXISTS FK_books_category,
            DROP FOREIGN KEY IF EXISTS FK_books_author,
            DROP FOREIGN KEY IF EXISTS FK_test_books_category,
            DROP FOREIGN KEY IF EXISTS FK_test_books_author,
            DROP FOREIGN KEY IF EXISTS $categoryConstraint,
            DROP FOREIGN KEY IF EXISTS $authorConstraint
        ");
    } catch (PDOException $e) {
        // Ignore errors if constraints don't exist
    }

    // Add constraints with unique names for test database
    $pdo->exec("
        ALTER TABLE tblbooks
        ADD CONSTRAINT $categoryConstraint FOREIGN KEY (CatId) REFERENCES tblcategory(id) ON DELETE CASCADE,
        ADD CONSTRAINT $authorConstraint FOREIGN KEY (AuthorId) REFERENCES tblauthors(id) ON DELETE CASCADE
    ");
    
    echo "Foreign key constraints added successfully.\n";
    
    // Add unique constraints
    $pdo->exec("ALTER TABLE tblauthors ADD UNIQUE (AuthorName)");
    $pdo->exec("ALTER TABLE tblcategory ADD UNIQUE (CategoryName)");
    $pdo->exec("ALTER TABLE tblstudents ADD UNIQUE (EmailId)");
    
    echo "Unique constraints added successfully.\n";
    
    echo "Test database setup completed successfully.\n";
    
} catch (PDOException $e) {
    echo "Database setup error: " . $e->getMessage() . "\n";
    exit(1);
}