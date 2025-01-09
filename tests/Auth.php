<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Game\Auth;
use Game\Player;

class AuthTest extends TestCase {
    private $auth;
    private $db;

    protected function setUp(): void {
        $this->db = $this->createMock(\PDO::class);
        $this->auth = new Auth($this->db);
    }

    public function testSuccessfulLogin(): void {
        $username = 'testuser';
        $password = 'password123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn([
            'id' => 'user-1',
            'username' => $username,
            'password_hash' => $hashedPassword
        ]);

        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->auth->login($username, $password);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($username, $result['player']['username']);
    }

    public function testFailedLoginWithWrongPassword(): void {
        $username = 'testuser';
        $hashedPassword = password_hash('rightpassword', PASSWORD_DEFAULT);

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn([
            'id' => 'user-1',
            'username' => $username,
            'password_hash' => $hashedPassword
        ]);

        $this->db->method('prepare')->willReturn($stmt);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->auth->login($username, 'wrongpassword');
    }

    public function testRegisterNewUser(): void {
        $username = 'newuser';
        $password = 'password123';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn(false); // Username doesn't exist
        $stmt->expects($this->once())->method('execute');

        $this->db->method('prepare')->willReturn($stmt);
        $this->db->method('lastInsertId')->willReturn('new-user-id');

        $result = $this->auth->register($username, $password);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($username, $result['player']['username']);
    }
} 