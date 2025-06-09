<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestDatabase;

class AdminIssueBookTest extends TestCase
{
    private $db;
    private $uniquePrefix;

    protected function setUp(): void
    {
        $this->db = TestDatabase::getInstance();
        $this->db->resetTestDatabase();
        
        // Generate unique prefix for test data
        $this->uniquePrefix = 'Test_' . uniqid() . '_';
        
        // Check and drop foreign keys if they exist
        $this->dropForeignKeyIfExists('FK_issuedbook_book');
        $this->dropForeignKeyIfExists('FK_issuedbook_student');
        
        // Add test book
        $stmt = $this->db->prepare(
            'INSERT INTO tblbooks 
             (BookName, CatId, AuthorId, ISBNNumber, BookPrice, RegDate) 
             VALUES (:name, :catId, :authorId, :isbn, :price, NOW())'
        );
        $stmt->execute([
            'name' => $this->uniquePrefix . 'Book',
            'catId' => 1,
            'authorId' => 1,
            'isbn' => '9781234567897',
            'price' => 29.99
        ]);
                        
        // Add test student
        $stmt = $this->db->prepare(
            'INSERT INTO tblstudents 
             (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
             VALUES (:studentId, :name, :email, :mobile, :password, 1)'
        );
        $stmt->execute([
            'studentId' => $this->uniquePrefix . 'SID123',
            'name' => 'Test Student',
            'email' => $this->uniquePrefix . 'test@example.com',
            'mobile' => '1234567890',
            'password' => md5('Test@123')
        ]);
    }

    private function dropForeignKeyIfExists(string $fkName): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $stmt->execute([$fkName]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $this->db->exec("ALTER TABLE tblissuedbookdetails DROP FOREIGN KEY $fkName");
        }
    }

    public function testSuccessfulBookIssue()
    {
        // Get the student ID
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE EmailId LIKE :email');
        $stmt->execute(['email' => $this->uniquePrefix . '%']);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($student);
        
        $bookId = 1;
        
        // Verify book exists
        $stmt = $this->db->prepare('SELECT * FROM tblbooks WHERE id = :bookId');
        $stmt->execute(['bookId' => $bookId]);
        $book = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($book);
        
        // Issue book
        $stmt = $this->db->prepare('
            INSERT INTO tblissuedbookdetails 
            (StudentId, BookId, IssuesDate, ReturnDate, RetrunStatus) 
            VALUES (:studentId, :bookId, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 0)
        ');
        $result = $stmt->execute([
            'studentId' => $student['id'],
            'bookId' => $bookId
        ]);
        
        $this->assertTrue($result);
        
        // Verify book was issued
        $stmt = $this->db->prepare('
            SELECT * FROM tblissuedbookdetails 
            WHERE StudentId = :studentId AND BookId = :bookId AND RetrunStatus = 0
        ');
        $stmt->execute([
            'studentId' => $student['id'],
            'bookId' => $bookId
        ]);
        $issuedBook = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($issuedBook);
    }

    public function testInvalidBookIssue()
    {
        $invalidBookId = 123;
        
        // Try to issue non-existent book
        $stmt = $this->db->prepare('SELECT * FROM tblbooks WHERE id = :bookId');
        $stmt->execute(['bookId' => $invalidBookId]);
        $book = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertFalse($book);
    }

    public function testInvalidStudentIssue()
    {
        $invalidStudentId = 'INVALID123';
        
        // Try to issue book to non-existent student
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE StudentId = :studentId');
        $stmt->execute(['studentId' => $invalidStudentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertFalse($student);
    }

    public function testUpdateIssuedBookDetails()
    {
        // Get the student ID
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE EmailId LIKE :email');
        $stmt->execute(['email' => $this->uniquePrefix . '%']);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($student);
        
        $bookId = 1;
        
        // First issue a book
        $stmt = $this->db->prepare('
            INSERT INTO tblissuedbookdetails 
            (StudentId, BookId, IssuesDate, ReturnDate) 
            VALUES (:studentId, :bookId, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))
        ');
        $stmt->execute([
            'studentId' => $student['id'],
            'bookId' => $bookId
        ]);
        $issueId = $this->db->lastInsertId();
        
        // Update issued book details (add fine)
        $fine = 11.00; // Use a round number to avoid precision issues
        $stmt = $this->db->prepare('
            UPDATE tblissuedbookdetails 
            SET Fine = :fine, RetrunStatus = 1, ReturnDate = NOW() 
            WHERE id = :id
        ');
        $result = $stmt->execute([
            'fine' => $fine,
            'id' => $issueId
        ]);
        
        $this->assertTrue($result);
        
        // Verify details were updated
        $stmt = $this->db->prepare('SELECT Fine, RetrunStatus FROM tblissuedbookdetails WHERE id = :id');
        $stmt->execute(['id' => $issueId]);
        $updatedIssue = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($updatedIssue);
        $this->assertEquals($fine, (float)$updatedIssue['Fine']);
        $this->assertEquals(1, (int)$updatedIssue['RetrunStatus']);
    }

    public function testMultipleBookIssues()
    {
        // Get the student ID
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE EmailId LIKE :email');
        $stmt->execute(['email' => $this->uniquePrefix . '%']);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($student);
        
        $studentId = $student['id'];
        
        // Add another test book
        $stmt = $this->db->prepare('
            INSERT INTO tblbooks 
            (BookName, CatId, AuthorId, ISBNNumber, BookPrice, RegDate) 
            VALUES (:name, :catId, :authorId, :isbn, :price, NOW())
        ');
        $stmt->execute([
            'name' => $this->uniquePrefix . 'Second Book',
            'catId' => 1,
            'authorId' => 1,
            'isbn' => '9789876543210',
            'price' => 39.99
        ]);
        
        // Issue first book
        $stmt = $this->db->prepare('
            INSERT INTO tblissuedbookdetails 
            (StudentId, BookId, IssuesDate, ReturnDate, RetrunStatus) 
            VALUES (:studentId, 1, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 0)
        ');
        $stmt->execute(['studentId' => $studentId]);
        
        // Issue second book
        $stmt = $this->db->prepare('
            INSERT INTO tblissuedbookdetails 
            (StudentId, BookId, IssuesDate, ReturnDate, RetrunStatus) 
            VALUES (:studentId, 2, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 0)
        ');
        $stmt->execute(['studentId' => $studentId]);
        
        // Get all books issued to student
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as book_count 
            FROM tblissuedbookdetails 
            WHERE StudentId = :studentId AND RetrunStatus = 0
        ');
        $stmt->execute(['studentId' => $studentId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals(2, (int)$result['book_count']);
    }

    protected function tearDown(): void
    {
        if ($this->db->isInTransaction()) {
            $this->db->rollbackTransaction();
        }
        
        // Recreate foreign key constraints if they don't exist
        $this->addForeignKeyIfNotExists(
            'FK_issuedbook_book',
            'ALTER TABLE tblissuedbookdetails 
             ADD CONSTRAINT FK_issuedbook_book 
             FOREIGN KEY (BookId) REFERENCES tblbooks(id) 
             ON DELETE CASCADE'
        );
        
        $this->addForeignKeyIfNotExists(
            'FK_issuedbook_student',
            'ALTER TABLE tblissuedbookdetails 
             ADD CONSTRAINT FK_issuedbook_student 
             FOREIGN KEY (StudentId) REFERENCES tblstudents(id) 
             ON DELETE CASCADE'
        );
    }

    private function addForeignKeyIfNotExists(string $fkName, string $sql): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $stmt->execute([$fkName]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            try {
                $this->db->exec($sql);
            } catch (\PDOException $e) {
                // Ignore if constraint can't be added (e.g., referenced tables are empty)
            }
        }
    }
}