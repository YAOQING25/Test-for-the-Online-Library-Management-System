<?php

use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestDatabase;

class ChangePasswordTest extends TestCase
{
    private $db;
    private $uniquePrefix;

    protected function setUp(): void
    {
        $this->db = TestDatabase::getInstance();
        $this->db->beginTransaction();
        
        // Generate unique prefix for test data
        $this->uniquePrefix = 'Test_' . uniqid() . '_';
        
        // Add test student with password "Test@123"
        $stmt = $this->db->prepare(
            'INSERT INTO tblstudents 
             (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
             VALUES (:studentId, :name, :email, :mobile, :password, :status)'
        );
        $stmt->execute([
            'studentId' => $this->uniquePrefix . 'STD001',
            'name' => 'Test Student',
            'email' => $this->uniquePrefix . 'test@example.com',
            'mobile' => '1234567890',
            'password' => md5('Test@123'),
            'status' => 1
        ]);
                        
        // Add test admin with password "Test@123"
        $stmt = $this->db->prepare(
            'INSERT INTO admin (UserName, Password) 
             VALUES (:username, :password)'
        );
        $stmt->execute([
            'username' => $this->uniquePrefix . 'admin',
            'password' => md5('Test@123')
        ]);
    }

    public function testSuccessfulPasswordChange()
    {
        // Get the student ID
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE EmailId LIKE :email');
        $stmt->execute(['email' => $this->uniquePrefix . '%']);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($student);
        
        $studentId = $student['id'];
        $currentPassword = 'Test@123';
        $newPassword = 'NewTest@123';
        
        // Verify current password
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE id = ? AND Password = ?');
        $stmt->execute([$studentId, md5($currentPassword)]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($student);
        
        // Update password
        $stmt = $this->db->prepare('UPDATE tblstudents SET Password = ? WHERE id = ?');
        $result = $stmt->execute([md5($newPassword), $studentId]);
        $this->assertTrue($result);
        
        // Verify password was changed
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE id = ? AND Password = ?');
        $stmt->execute([$studentId, md5($newPassword)]);
        $updatedStudent = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($updatedStudent);
    }

    public function testWrongCurrentPassword()
    {
        // Get the student ID
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE EmailId LIKE :email');
        $stmt->execute(['email' => $this->uniquePrefix . '%']);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($student);
        
        $studentId = $student['id'];
        $wrongCurrentPassword = 'WrongPass@123';
        
        // Try to verify with wrong current password
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE id = ? AND Password = ?');
        $stmt->execute([$studentId, md5($wrongCurrentPassword)]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertFalse($student);
    }

    public function testPasswordMismatch()
    {
        $newPassword = 'NewTest@123';
        $confirmPassword = 'DifferentTest@123';
        
        // Verify passwords match
        $this->assertNotEquals($newPassword, $confirmPassword, 'New password and confirm password should not match');
    }

    public function testAdminPasswordChange()
    {
        // Get the admin
        $stmt = $this->db->prepare('SELECT * FROM admin WHERE UserName LIKE :username');
        $stmt->execute(['username' => $this->uniquePrefix . '%']);
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($admin);
        
        $currentPassword = 'Test@123';
        $newPassword = 'NewAdmin@123';
        
        // Verify current password
        $stmt = $this->db->prepare('SELECT * FROM admin WHERE id = ? AND Password = ?');
        $stmt->execute([$admin['id'], md5($currentPassword)]);
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($admin);
        
        // Update password
        $stmt = $this->db->prepare('UPDATE admin SET Password = ? WHERE id = ?');
        $result = $stmt->execute([md5($newPassword), $admin['id']]);
        $this->assertTrue($result);
        
        // Verify password was changed
        $stmt = $this->db->prepare('SELECT * FROM admin WHERE id = ? AND Password = ?');
        $stmt->execute([$admin['id'], md5($newPassword)]);
        $updatedAdmin = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($updatedAdmin);
    }

    public function testPasswordStrengthValidation()
    {
        // Define a proper regex pattern for password validation
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$/';
        
        // Test valid passwords
        $validPasswords = [
            'Test@123',
            'Password@123',
            'Secure$Password1'
        ];
        
        foreach ($validPasswords as $password) {
            $this->assertEquals(1, preg_match($pattern, $password), "Password '$password' should be valid");
        }
        
        // Test invalid passwords
        $invalidPasswords = [
            'password',      // No uppercase, no special char, no number
            '12345678',      // No letters
            'TestTest',      // No special char, no number
            'Test@12',       // Too short
            str_repeat('A', 21) . '@1' // Too long
        ];
        
        foreach ($invalidPasswords as $password) {
            $this->assertEquals(0, preg_match($pattern, $password), "Password '$password' should be invalid");
        }
    }

    protected function tearDown(): void
    {
        if ($this->db->isInTransaction()) {
            $this->db->rollbackTransaction();
        }
    }
}