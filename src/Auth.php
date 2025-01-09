<?php

namespace Game;

/**
 * Class Auth
 * Handles player authentication, registration, and session management
 * 
 * @package Game
 */
class Auth {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var int Number of days before a session token expires */
    private const TOKEN_EXPIRY_DAYS = 30;

    /**
     * Auth constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Register a new player
     *
     * @param string $username The desired username (3-20 characters)
     * @param string $password The password (minimum 8 characters)
     * @return array Array containing token and expiry information
     * @throws \Exception If username is taken or validation fails
     */
    public function register(string $username, string $password): array {
        if (strlen($username) < 3 || strlen($username) > 20) {
            throw new \Exception("Username must be between 3 and 20 characters");
        }

        if (strlen($password) < 8) {
            throw new \Exception("Password must be at least 8 characters");
        }

        // Check if username exists
        $stmt = $this->db->prepare("SELECT 1 FROM players WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new \Exception("Username already taken");
        }

        // Create player
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO players (username, password_hash)
            VALUES (?, ?)
            RETURNING id
        ");
        $stmt->execute([$username, $passwordHash]);
        $playerId = $stmt->fetchColumn();

        // Initialize combat stats
        $stmt = $this->db->prepare("
            INSERT INTO combat_stats (player_id)
            VALUES (?)
        ");
        $stmt->execute([$playerId]);

        // Initialize financial stats
        $stmt = $this->db->prepare("
            INSERT INTO financial_stats (player_id)
            VALUES (?)
        ");
        $stmt->execute([$playerId]);

        // Create and return session
        return $this->createSession($playerId);
    }

    /**
     * Authenticate a player and create a new session
     *
     * @param string $username The player's username
     * @param string $password The player's password
     * @return array Array containing token and expiry information
     * @throws \Exception If credentials are invalid
     */
    public function login(string $username, string $password): array {
        $stmt = $this->db->prepare("
            SELECT id, password_hash 
            FROM players 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $player = $stmt->fetch();

        if (!$player || !password_verify($password, $player['password_hash'])) {
            throw new \Exception("Invalid username or password");
        }

        return $this->createSession($player['id']);
    }

    /**
     * Validate an existing session token
     *
     * @param string $token The session token to validate
     * @return array|null Player session data if valid, null if invalid
     */
    public function validateSession(string $token): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                s.player_id,
                p.username,
                s.expires_at
            FROM sessions s
            JOIN players p ON p.id = s.player_id
            WHERE s.token = ?
            AND s.expires_at > CURRENT_TIMESTAMP
        ");
        $stmt->execute([$token]);
        $session = $stmt->fetch();

        if (!$session) {
            return null;
        }

        // Update last activity
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET last_activity = CURRENT_TIMESTAMP
            WHERE token = ?
        ");
        $stmt->execute([$token]);

        return [
            'player_id' => $session['player_id'],
            'username' => $session['username'],
            'expires_at' => $session['expires_at']
        ];
    }

    /**
     * End a player's session
     *
     * @param string $token The session token to invalidate
     */
    public function logout(string $token): void {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE token = ?");
        $stmt->execute([$token]);
    }

    /**
     * Create a new session for a player
     *
     * @param string $playerId The ID of the player
     * @return array Array containing token and expiry information
     */
    private function createSession(string $playerId): array {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_DAYS . ' days'));

        $stmt = $this->db->prepare("
            INSERT INTO sessions (player_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$playerId, $token, $expiresAt]);

        return [
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }
} 