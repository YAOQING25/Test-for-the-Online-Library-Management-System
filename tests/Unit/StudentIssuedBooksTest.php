<?php

use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestDatabase;

class StudentIssuedBooksTest extends TestCase
{
    private $db;
    private $testDb;

    protected function setUp(): void
    {
        $this->testDb = TestDatabase::getInstance();
        $this->db = $this->testDb->getConnection();
        
        try {
            // Disable foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            // Clear tables
            $this->db->exec('DELETE FROM tblissuedbookdetails');
            $this->db->exec('DELETE FROM tblbooks');
            $this->db->exec('DELETE FROM tblstudents');
            $this->db->exec('DELETE FROM tblcategory');
            $this->db->exec('DELETE FROM tblauthors');
            
            // Reset auto increment
            $this->db->exec('ALTER TABLE tblissuedbookdetails AUTO_INCREMENT = 1');
            $this->db->exec('ALTER TABLE tblbooks AUTO_INCREMENT = 1');
            $this->db->exec('ALTER TABLE tblstudents AUTO_INCREMENT = 1');
            $this->db->exec('ALTER TABLE tblcategory AUTO_INCREMENT = 1');
            $this->db->exec('ALTER TABLE tblauthors AUTO_INCREMENT = 1');
            
            // Enable foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            // Add test category
            $this->db->exec("INSERT INTO tblcategory (CategoryName, Status, CreationDate) 
                           VALUES ('TestProgramming', 1, NOW())");
                            
            // Add test author
            $this->db->exec("INSERT INTO tblauthors (AuthorName, CreationDate) 
                           VALUES ('Test John Doe', NOW())");
            
            // Add test books
            $this->db->exec("INSERT INTO tblbooks (BookName, CatId, AuthorId, ISBNNumber, BookPrice, RegDate) 
                           VALUES ('Test PHP Programming', 1, 1, '9781234567897', 29.99, NOW())");
            $this->db->exec("INSERT INTO tblbooks (BookName, CatId, AuthorId, ISBNNumber, BookPrice, RegDate) 
                           VALUES ('Test Java Programming', 1, 1, '9789876543210', 39.99, NOW())");
                            
            // Add test students
            $this->db->exec("INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
                           VALUES ('TEST123', 'Test Student', 'test123@example.com', '1234567890', '" . md5('Test@123') . "', 1)");
            $this->db->exec("INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
                           VALUES ('TEST456', 'Another Test Student', 'test456@example.com', '0987654321', '" . md5('Test@456') . "', 1)");
            
            // Issue books to first student
            $this->db->exec("INSERT INTO tblissuedbookdetails (StudentId, BookId, IssuesDate, ReturnDate) 
                           VALUES (1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))");
            $this->db->exec("INSERT INTO tblissuedbookdetails (StudentId, BookId, IssuesDate, ReturnDate, RetrunStatus) 
                           VALUES (1, 2, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), 1)");
            
            // Start transaction for test isolation
            if (!$this->testDb->isInTransaction()) {
                $this->testDb->beginTransaction();
            }
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database setup failed: ' . $e->getMessage());
        }
    }

    public function testStudentViewIssuedBooks()
    {
        $studentId = 1;
        
        // Get all books issued to student (both current and returned)
        $stmt = $this->db->prepare('
            SELECT i.id, b.BookName, b.ISBNNumber, i.IssuesDate, i.ReturnDate, i.RetrunStatus, i.Fine
            FROM tblissuedbookdetails i
            JOIN tblbooks b ON i.BookId = b.id
            WHERE i.StudentId = ?
            ORDER BY i.id ASC
        ');
        $stmt->execute([$studentId]);
        $issuedBooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(2, $issuedBooks);
        
        // Check first book (current)
        $this->assertEquals('Test PHP Programming', $issuedBooks[0]['BookName']);
        $this->assertEquals('9781234567897', $issuedBooks[0]['ISBNNumber']);
        $this->assertEquals(0, (int)$issuedBooks[0]['RetrunStatus']);
        
        // Check second book (returned)
        $this->assertEquals('Test Java Programming', $issuedBooks[1]['BookName']);
        $this->assertEquals('9789876543210', $issuedBooks[1]['ISBNNumber']);
        $this->assertEquals(1, (int)$issuedBooks[1]['RetrunStatus']);
    }

    public function testStudentWithNoIssuedBooks()
    {
        $studentId = 2; // Second student with no issued books
        
        // Get all books issued to student
        $stmt = $this->db->prepare('
            SELECT i.id, b.BookName, b.ISBNNumber, i.IssuesDate, i.ReturnDate, i.RetrunStatus, i.Fine
            FROM tblissuedbookdetails i
            JOIN tblbooks b ON i.BookId = b.id
            WHERE i.StudentId = ?
        ');
        $stmt->execute([$studentId]);
        $issuedBooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(0, $issuedBooks);
    }

    public function testCurrentlyIssuedBooks()
    {
        $studentId = 1;
        
        // Get only currently issued books (not returned)
        $stmt = $this->db->prepare('
            SELECT i.id, b.BookName, b.ISBNNumber, i.IssuesDate, i.ReturnDate
            FROM tblissuedbookdetails i
            JOIN tblbooks b ON i.BookId = b.id
            WHERE i.StudentId = ? AND (i.RetrunStatus = 0 OR i.RetrunStatus IS NULL)
        ');
        $stmt->execute([$studentId]);
        $currentBooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $currentBooks);
        $this->assertEquals('Test PHP Programming', $currentBooks[0]['BookName']);
    }

    public function testReturnedBooks()
    {
        $studentId = 1;
        
        // Get only returned books
        $stmt = $this->db->prepare('
            SELECT i.id, b.BookName, b.ISBNNumber, i.IssuesDate, i.ReturnDate, i.Fine
            FROM tblissuedbookdetails i
            JOIN tblbooks b ON i.BookId = b.id
            WHERE i.StudentId = ? AND i.RetrunStatus = 1
        ');
        $stmt->execute([$studentId]);
        $returnedBooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $returnedBooks);
        $this->assertEquals('Test Java Programming', $returnedBooks[0]['BookName']);
    }

    protected function tearDown(): void
    {
        try {
            // Roll back any changes made during the test
            if ($this->testDb->isInTransaction()) {
                $this->testDb->rollbackTransaction();
            }
            
            // Don't attempt to truncate tables in tearDown to avoid foreign key issues
        } catch (\PDOException $e) {
            // Just log but don't fail the test
            error_log('Teardown error: ' . $e->getMessage());
        }
        
        $this->db = null;
        $this->testDb = null;
    }
}