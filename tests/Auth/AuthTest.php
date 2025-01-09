<?php

namespace Tests\Auth;

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

    /** @test */
    public function successful_login(): void {
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

    /** @test */
    public function failed_login_with_wrong_password(): void {
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

    /** @test */
    public function register_new_user(): void {
        $username = 'newuser';
        $password = 'password123';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);
        $stmt->expects($this->once())->method('execute');

        $this->db->method('prepare')->willReturn($stmt);
        $this->db->method('lastInsertId')->willReturn('new-user-id');

        $result = $this->auth->register($username, $password);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($username, $result['player']['username']);
    }
} 