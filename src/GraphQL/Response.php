<?php

namespace Game\GraphQL;

/**
 * Class Response
 * Handles GraphQL response formatting and caching
 * 
 * @package Game\GraphQL
 */
class Response {
    /** @var array Response data */
    private $data;

    /** @var array|null Response errors */
    private $errors;

    /** @var array Response metadata */
    private $extensions;

    /** @var int Cache TTL in seconds */
    private const CACHE_TTL = 300;

    /**
     * Response constructor
     *
     * @param array $data Response data
     * @param array|null $errors Response errors
     * @param array $extensions Response metadata
     */
    public function __construct(array $data = [], ?array $errors = null, array $extensions = []) {
        $this->data = $data;
        $this->errors = $errors;
        $this->extensions = $extensions;
    }

    /**
     * Convert response to array
     *
     * @return array Formatted response
     */
    public function toArray(): array {
        $response = [
            'data' => $this->data
        ];

        if ($this->errors !== null) {
            $response['errors'] = array_map(function ($error) {
                return $error instanceof Error ? $error->toArray() : $error;
            }, $this->errors);
        }

        if (!empty($this->extensions)) {
            $response['extensions'] = $this->extensions;
        }

        return $response;
    }

    /**
     * Add error to response
     *
     * @param Error|array $error Error to add
     * @return void
     */
    public function addError($error): void {
        if ($this->errors === null) {
            $this->errors = [];
        }
        $this->errors[] = $error;
    }

    /**
     * Add extension data
     *
     * @param string $key Extension key
     * @param mixed $value Extension value
     * @return void
     */
    public function addExtension(string $key, $value): void {
        $this->extensions[$key] = $value;
    }

    /**
     * Cache response
     *
     * @param string $cacheKey Cache key
     * @return bool Success status
     */
    public function cache(string $cacheKey): bool {
        if (empty($this->errors)) {
            return apcu_store(
                "graphql_response_{$cacheKey}",
                $this->toArray(),
                self::CACHE_TTL
            );
        }
        return false;
    }

    /**
     * Get cached response
     *
     * @param string $cacheKey Cache key
     * @return array|false Cached response or false if not found
     */
    public static function getFromCache(string $cacheKey) {
        return apcu_fetch("graphql_response_{$cacheKey}");
    }

    /**
     * Clear cached response
     *
     * @param string $cacheKey Cache key
     * @return bool Success status
     */
    public static function clearCache(string $cacheKey): bool {
        return apcu_delete("graphql_response_{$cacheKey}");
    }
} 