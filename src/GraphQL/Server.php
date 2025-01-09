<?php

namespace Game\GraphQL;

/**
 * Class Server
 * Handles GraphQL request processing and response generation
 * 
 * @package Game\GraphQL
 */
class Server {
    /** @var Schema GraphQL schema instance */
    private $schema;

    /** @var Context Request context */
    private $context;

    /** @var array Request variables */
    private $variables;

    /** @var string Operation name */
    private $operationName;

    /**
     * Server constructor
     *
     * @param array $headers Request headers
     */
    public function __construct(array $headers) {
        $this->schema = new Schema();
        $this->context = new Context($headers);
    }

    /**
     * Process GraphQL request
     *
     * @param string $query GraphQL query
     * @param array|null $variables Query variables
     * @param string|null $operationName Operation name
     * @return array Response data
     */
    public function process(string $query, ?array $variables = null, ?string $operationName = null): array {
        $this->variables = $variables ?? [];
        $this->operationName = $operationName;

        try {
            $this->validateRequest($query);
            $result = $this->executeQuery($query);
            return $this->formatResponse($result);
        } catch (\Throwable $e) {
            return $this->formatError($e);
        }
    }

    /**
     * Execute GraphQL query
     *
     * @param string $query GraphQL query
     * @return array Query result
     */
    private function executeQuery(string $query): array {
        $resolver = $this->schema->getResolver($this->operationName);
        if (!$resolver) {
            throw new \Exception("Resolver not found for operation: {$this->operationName}");
        }

        $this->context->validateAccess($this->operationName);
        return $resolver->resolve($query, $this->variables, $this->context);
    }

    /**
     * Validate GraphQL request
     *
     * @param string $query GraphQL query
     * @throws \Exception If validation fails
     */
    private function validateRequest(string $query): void {
        if (empty($query)) {
            throw new \Exception("Query cannot be empty");
        }

        // Additional validation could be added here
        // - Query complexity analysis
        // - Depth limitation
        // - Rate limiting
    }

    /**
     * Format successful response
     *
     * @param array $data Response data
     * @return array Formatted response
     */
    private function formatResponse(array $data): array {
        return [
            'data' => $data,
            'errors' => null
        ];
    }

    /**
     * Format error response
     *
     * @param \Throwable $error Error instance
     * @return array Formatted error response
     */
    private function formatError(\Throwable $error): array {
        $graphqlError = new Error($error);
        return [
            'data' => null,
            'errors' => [$graphqlError->toArray()]
        ];
    }
} 