<?php

namespace Game\GraphQL;

/**
 * Class Context
 * Handles GraphQL request context and authentication state
 * 
 * @package Game\GraphQL
 */
class Context {
    /** @var array Request context data */
    private $data;

    /** @var Auth Authentication service */
    private $auth;

    /** @var array Protected mutations requiring authentication */
    private const PROTECTED_MUTATIONS = [
        'createMarketListing',
        'buyMarketListing',
        'buyStock',
        'sellStock',
        'createCD',
        'withdrawCD',
        'travelTo',
        'train',
        'attack'
    ];

    /**
     * Context constructor
     *
     * @param array $headers Request headers
     */
    public function __construct(array $headers) {
        $this->auth = new \Game\Auth();
        $this->data = [
            'player_id' => null,
            'is_authenticated' => false,
            'headers' => $headers
        ];

        $this->authenticate();
    }

    /**
     * Get context data
     *
     * @return array Context data
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * Check if current request is authenticated
     *
     * @return bool Authentication status
     */
    public function isAuthenticated(): bool {
        return $this->data['is_authenticated'];
    }

    /**
     * Validate access for operation
     *
     * @param string $operationName GraphQL operation name
     * @throws \Exception If authentication required but not provided
     */
    public function validateAccess(string $operationName): void {
        if (in_array($operationName, self::PROTECTED_MUTATIONS) && !$this->isAuthenticated()) {
            throw new \Exception("Authentication required");
        }
    }

    /**
     * Get authenticated player ID
     *
     * @return string|null Player ID if authenticated
     */
    public function getPlayerId(): ?string {
        return $this->data['player_id'];
    }

    /**
     * Authenticate request using token
     */
    private function authenticate(): void {
        $token = $this->extractToken();
        if (!$token) return;

        $session = $this->auth->validateSession($token);
        if ($session) {
            $this->data['player_id'] = $session['player_id'];
            $this->data['is_authenticated'] = true;
        }
    }

    /**
     * Extract authentication token from headers
     *
     * @return string|null Authentication token if present
     */
    private function extractToken(): ?string {
        $header = $this->data['headers']['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)/', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
} 