<?php

namespace Game\GraphQL;

/**
 * Class Query
 * Handles GraphQL query parsing and validation
 * 
 * @package Game\GraphQL
 */
class Query {
    /** @var string Raw query string */
    private $query;

    /** @var array Parsed query data */
    private $parsed;

    /** @var int Maximum query depth */
    private const MAX_DEPTH = 5;

    /**
     * Query constructor
     *
     * @param string $query GraphQL query string
     */
    public function __construct(string $query) {
        $this->query = $query;
        $this->parse();
    }

    /**
     * Get operation type (query/mutation)
     *
     * @return string Operation type
     */
    public function getOperationType(): string {
        return $this->parsed['operation'] ?? 'query';
    }

    /**
     * Get selected fields
     *
     * @return array Selected fields
     */
    public function getSelectedFields(): array {
        return $this->parsed['fields'] ?? [];
    }

    /**
     * Get query variables
     *
     * @return array Query variables
     */
    public function getVariables(): array {
        return $this->parsed['variables'] ?? [];
    }

    /**
     * Parse raw query string
     *
     * @throws \Exception If parsing fails
     */
    private function parse(): void {
        // Basic query parsing
        if (!preg_match('/^(query|mutation)\s+(\w+)/', $this->query, $matches)) {
            throw new \Exception("Invalid query format");
        }

        $this->parsed = [
            'operation' => $matches[1],
            'name' => $matches[2],
            'fields' => $this->parseFields(),
            'variables' => $this->parseVariables()
        ];

        $this->validateDepth($this->parsed['fields']);
    }

    /**
     * Parse query fields
     *
     * @return array Parsed fields
     */
    private function parseFields(): array {
        // Field parsing implementation
        // This is a simplified version - actual implementation would be more complex
        $fields = [];
        preg_match_all('/(\w+)\s*{([^}]+)}/', $this->query, $matches);
        
        foreach ($matches[1] as $i => $field) {
            $fields[$field] = $this->parseSubfields($matches[2][$i]);
        }

        return $fields;
    }

    /**
     * Validate query depth
     *
     * @param array $fields Field structure
     * @param int $depth Current depth
     * @throws \Exception If maximum depth exceeded
     */
    private function validateDepth(array $fields, int $depth = 0): void {
        if ($depth > self::MAX_DEPTH) {
            throw new \Exception("Query exceeds maximum depth");
        }

        foreach ($fields as $subfields) {
            if (is_array($subfields)) {
                $this->validateDepth($subfields, $depth + 1);
            }
        }
    }
} 