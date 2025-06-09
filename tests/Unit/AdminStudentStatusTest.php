<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestDatabase;

class AdminStudentStatusTest extends TestCase
{
    private $db;
    private $uniquePrefix;
    private $activeStudentId;
    private $blockedStudentId;

    protected function setUp(): void
    {
        $this->db = TestDatabase::getInstance();
        $this->db->beginTransaction();
        
        // Generate unique prefix for test data
        $this->uniquePrefix = 'Test_' . uniqid() . '_';
        
        // Add an active student
        $stmt = $this->db->prepare(
            'INSERT INTO tblstudents 
             (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
             VALUES (:studentId, :name, :email, :mobile, :password, :status)'
        );
        $stmt->execute([
            'studentId' => $this->uniquePrefix . 'ACTIVE',
            'name' => 'Active Student',
            'email' => 'active@' . $this->uniquePrefix . 'example.com',
            'mobile' => '1234567890',
            'password' => md5('Test@123'),
            'status' => 1
        ]);
        $this->activeStudentId = $this->db->lastInsertId();
        
        // Add a blocked student
        $stmt->execute([
            'studentId' => $this->uniquePrefix . 'BLOCKED',
            'name' => 'Blocked Student',
            'email' => 'blocked@' . $this->uniquePrefix . 'example.com',
            'mobile' => '0987654321',
            'password' => md5('Test@123'),
            'status' => 0
        ]);
        $this->blockedStudentId = $this->db->lastInsertId();
    }

    public function testBlockActiveStudent()
    {
        // Verify student is active
        $stmt = $this->db->prepare('SELECT Status FROM tblstudents WHERE id = ?');
        $stmt->execute([$this->activeStudentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, (int)$student['Status']);
        
        // Block student
        $stmt = $this->db->prepare('UPDATE tblstudents SET Status = 0, UpdationDate = NOW() WHERE id = ?');
        $result = $stmt->execute([$this->activeStudentId]);
        $this->assertTrue($result);
        
        // Verify student is now blocked
        $stmt = $this->db->prepare('SELECT Status FROM tblstudents WHERE id = ?');
        $stmt->execute([$this->activeStudentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, (int)$student['Status']);
    }

    public function testActivateBlockedStudent()
    {
        // Verify student is blocked
        $stmt = $this->db->prepare('SELECT Status FROM tblstudents WHERE id = ?');
        $stmt->execute([$this->blockedStudentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, (int)$student['Status']);
        
        // Activate student
        $stmt = $this->db->prepare('UPDATE tblstudents SET Status = 1, UpdationDate = NOW() WHERE id = ?');
        $result = $stmt->execute([$this->blockedStudentId]);
        $this->assertTrue($result);
        
        // Verify student is now active
        $stmt = $this->db->prepare('SELECT Status FROM tblstudents WHERE id = ?');
        $stmt->execute([$this->blockedStudentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, (int)$student['Status']);
    }

    public function testBlockedStudentCannotLogin()
    {
        // Block the active student
        $stmt = $this->db->prepare('UPDATE tblstudents SET Status = 0 WHERE id = ?');
        $stmt->execute([$this->activeStudentId]);
        
        // Get the student email
        $stmt = $this->db->prepare('SELECT EmailId FROM tblstudents WHERE id = ?');
        $stmt->execute([$this->activeStudentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $email = $student['EmailId'];
        
        // Try to login with blocked account
        $stmt = $this->db->prepare('
            SELECT * FROM tblstudents 
            WHERE EmailId = ? AND Password = ? AND Status = 1
        ');
        $stmt->execute([$email, md5('Test@123')]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Should fail because Status = 0
        $this->assertFalse($result);
    }

    public function testActivatedStudentCanLogin()
    {
        // Activate the blocked student
        $stmt = $this->db->prepare('UPDATE tblstudents SET Status = 1 WHERE id = ?');
        $stmt->execute([$this->blockedStudentId]);
        
        // Get the student email
        $stmt = $this->db->prepare('SELECT EmailId FROM tblstudents WHERE id = ?');
        $stmt->execute([$this->blockedStudentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $email = $student['EmailId'];
        
        // Try to login with activated account
        $stmt = $this->db->prepare('
            SELECT * FROM tblstudents 
            WHERE EmailId = ? AND Password = ? AND Status = 1
        ');
        $stmt->execute([$email, md5('Test@123')]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Should succeed because Status = 1
        $this->assertNotFalse($result);
    }

    protected function tearDown(): void
    {
        if ($this->db->isInTransaction()) {
            $this->db->rollbackTransaction();
        }
    }
} 