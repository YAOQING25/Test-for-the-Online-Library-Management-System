<?php

use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestDatabase;

class LoginTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = TestDatabase::getInstance();
        $this->db->beginTransaction();
        
        // Add test admin with hashed password
        $stmt = $this->db->prepare(
            'INSERT INTO admin (UserName, Password) 
             VALUES (:username, :password)'
        );
        $stmt->execute([
            'username' => 'admin',
            'password' => password_hash('admin@123', PASSWORD_DEFAULT)
        ]);
        
        // Add test student with hashed password
        $stmt = $this->db->prepare(
            'INSERT INTO tblstudents 
             (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
             VALUES (:StudentId, :FullName, :EmailId, :MobileNumber, :Password, :Status)'
        );
        $stmt->execute([
            'StudentId' => 'STD001',
            'FullName' => 'Test Student',
            'EmailId' => 'test@gmail.com',
            'MobileNumber' => '1234567890',
            'Password' => password_hash('Test@123', PASSWORD_DEFAULT),
            'Status' => 1
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->rollbackTransaction();
    }

    public function testValidAdminLogin()
    {
        $stmt = $this->db->prepare('SELECT * FROM admin WHERE UserName = ?');
        $stmt->execute(['admin']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($admin);
        $this->assertTrue(
            password_verify('admin@123', $admin['Password']),
            'Admin password verification failed'
        );
    }

    public function testValidStudentLogin()
    {
        $stmt = $this->db->prepare('SELECT * FROM tblstudents WHERE EmailId = ? AND Status = 1');
        $stmt->execute(['test@gmail.com']);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($student);
        $this->assertTrue(
            password_verify('Test@123', $student['Password']),
            'Student password verification failed'
        );
    }

    public function testLoginTestCases()
    {
        $testCases = [
            [
                'name' => 'Invalid username',
                'table' => 'tblstudents',
                'username' => 'unknownUser',
                'password' => 'anyPass123',
                'expected' => false
            ],
            [
                'name' => 'Malformed email',
                'table' => 'tblstudents',
                'username' => 'useratexample.com',
                'password' => 'Test@123',
                'expected' => false
            ],
            [
                'name' => 'Wrong password',
                'table' => 'admin',
                'username' => 'admin',
                'password' => 'wrongpass',
                'expected' => false
            ],
            [
                'name' => 'Inactive student',
                'table' => 'tblstudents',
                'username' => 'inactive@gmail.com',
                'password' => 'Inactive@123',
                'expected' => false,
                'setup' => function() {
                    $stmt = $this->db->prepare(
                        'INSERT INTO tblstudents 
                         (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
                         VALUES (:StudentId, :FullName, :EmailId, :MobileNumber, :Password, :Status)'
                    );
                    $stmt->execute([
                        'StudentId' => 'STD002',
                        'FullName' => 'Inactive Student',
                        'EmailId' => 'inactive@gmail.com',
                        'MobileNumber' => '0987654321',
                        'Password' => password_hash('Inactive@123', PASSWORD_DEFAULT),
                        'Status' => 0
                    ]);
                }
            ]
        ];

        foreach ($testCases as $case) {
            if (isset($case['setup'])) {
                $case['setup']();
            }

            $stmt = $this->db->prepare(
                "SELECT * FROM {$case['table']} WHERE " . 
                ($case['table'] === 'admin' ? 'UserName' : 'EmailId') . " = ?"
            );
            $stmt->execute([$case['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $result = password_verify($case['password'], $user['Password']);
                if ($case['table'] === 'tblstudents') {
                    $result = $result && ($user['Status'] == 1);
                }
            } else {
                $result = false;
            }
            
            $this->assertEquals(
                $case['expected'],
                $result,
                "Test case '{$case['name']}' failed"
            );
        }
    }

    public function testPasswordSecurity()
    {
        // Test password hashing
        $password = 'Secure@123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->assertTrue(password_verify($password, $hash));
        
        // Test password strength requirements
        $weakPasswords = [
            'password',      // Too common
            '12345678',     // All numbers
            'TestTest',     // No special chars
            'Test@12',      // Too short
            str_repeat('A', 21) // Too long
        ];
        
        // Define a proper regex pattern for password validation
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$/';
        
        foreach ($weakPasswords as $weak) {
            // Use preg_match directly and check that it returns 0 (no match) or false (error)
            $result = preg_match($pattern, $weak);
            $this->assertFalse((bool)$result, "Weak password '$weak' should not pass validation");
        }
    }
}