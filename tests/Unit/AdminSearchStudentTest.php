<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestDatabase;

class AdminSearchStudentTest extends TestCase
{
    private $db;
    private $uniquePrefix;
    private $studentIds = [];

    protected function setUp(): void
    {
        $this->db = TestDatabase::getInstance();
        $this->db->beginTransaction();
        
        // Generate unique prefix for test data
        $this->uniquePrefix = 'Test_' . uniqid() . '_';
        
        // Add multiple test students
        $this->addTestStudents(5);
    }

    private function addTestStudents($count)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tblstudents 
             (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
             VALUES (:studentId, :name, :email, :mobile, :password, :status)'
        );
        
        for ($i = 1; $i <= $count; $i++) {
            $studentId = $this->uniquePrefix . 'SID' . $i;
            $this->studentIds[] = $studentId;
            
            $stmt->execute([
                'studentId' => $studentId,
                'name' => 'Student ' . $i,
                'email' => 'student' . $i . '@' . $this->uniquePrefix . 'example.com',
                'mobile' => '12345' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'password' => md5('Test@123' . $i),
                'status' => 1
            ]);
        }
    }

    public function testSearchStudentByValidId()
    {
        // Test searching for each student by ID
        foreach ($this->studentIds as $studentId) {
            $stmt = $this->db->prepare('
                SELECT * FROM tblstudents 
                WHERE StudentId = ?
            ');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $this->assertNotFalse($student);
            $this->assertEquals($studentId, $student['StudentId']);
        }
    }

    public function testSearchStudentByPartialId()
    {
        // Test searching by partial ID
        $partialId = $this->uniquePrefix . 'SID';
        
        $stmt = $this->db->prepare('
            SELECT * FROM tblstudents 
            WHERE StudentId LIKE ?
            ORDER BY id ASC
        ');
        $stmt->execute([$partialId . '%']);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(5, $students);
        
        // Verify all returned students have IDs starting with the partial ID
        foreach ($students as $student) {
            $this->assertStringStartsWith($partialId, $student['StudentId']);
        }
    }

    public function testSearchStudentByInvalidId()
    {
        $invalidId = 'NonExistentID';
        
        $stmt = $this->db->prepare('
            SELECT * FROM tblstudents 
            WHERE StudentId = ?
        ');
        $stmt->execute([$invalidId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertFalse($student);
    }

    public function testSearchStudentWithWildcards()
    {
        // Test searching with wildcards
        $wildcardSearch = $this->uniquePrefix . 'SID_';
        
        $stmt = $this->db->prepare('
            SELECT * FROM tblstudents 
            WHERE StudentId LIKE ?
            ORDER BY id ASC
        ');
        $stmt->execute([str_replace('_', '%', $wildcardSearch)]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertGreaterThan(0, count($students));
        
        // Verify all returned students have IDs matching the pattern
        foreach ($students as $student) {
            $this->assertMatchesRegularExpression('/' . str_replace('_', '.', $wildcardSearch) . '/', $student['StudentId']);
        }
    }

    protected function tearDown(): void
    {
        if ($this->db->isInTransaction()) {
            $this->db->rollbackTransaction();
        }
    }
} 