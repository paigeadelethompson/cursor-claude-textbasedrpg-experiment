<?php

namespace Game\GraphQL;

/**
 * Class Error
 * Handles GraphQL error formatting and logging
 * 
 * @package Game\GraphQL
 */
class Error {
    /** @var array Error data */
    private $data;

    /** @var bool Whether to include debug information */
    private const INCLUDE_DEBUG_INFO = false;

    /**
     * Error constructor
     *
     * @param \Throwable $error Original error
     * @param array $locations GraphQL query locations
     * @param array $path Error path in response
     */
    public function __construct(\Throwable $error, array $locations = [], array $path = []) {
        $this->data = [
            'message' => $error->getMessage(),
            'locations' => $locations,
            'path' => $path
        ];

        if (self::INCLUDE_DEBUG_INFO) {
            $this->addDebugInfo($error);
        }

        $this->logError($error);
    }

    /**
     * Get formatted error data
     *
     * @return array Formatted error
     */
    public function toArray(): array {
        return $this->data;
    }

    /**
     * Add debug information to error
     *
     * @param \Throwable $error Original error
     */
    private function addDebugInfo(\Throwable $error): void {
        $this->data['debug'] = [
            'type' => get_class($error),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString()
        ];
    }

    /**
     * Log error to system log
     *
     * @param \Throwable $error Original error
     */
    private function logError(\Throwable $error): void {
        error_log(sprintf(
            "[GraphQL Error] %s in %s:%d\n%s",
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString()
        ));
    }
} 