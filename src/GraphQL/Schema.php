<?php

namespace Game\GraphQL;

/**
 * Class Schema
 * Handles GraphQL schema loading and validation
 * 
 * @package Game\GraphQL
 */
class Schema {
    /** @var string Schema file path */
    private const SCHEMA_PATH = __DIR__ . '/../../schema/schema.graphql';

    /** @var string Schema contents */
    private $schema;

    /** @var array Loaded resolvers */
    private $resolvers = [];

    /**
     * Schema constructor
     *
     * @throws \Exception If schema file cannot be loaded
     */
    public function __construct() {
        $this->loadSchema();
        $this->loadResolvers();
    }

    /**
     * Get schema contents
     *
     * @return string GraphQL schema
     */
    public function getSchema(): string {
        return $this->schema;
    }

    /**
     * Get resolver for type
     *
     * @param string $type Type name
     * @return Resolver|null Resolver instance
     */
    public function getResolver(string $type): ?Resolver {
        return $this->resolvers[$type] ?? null;
    }

    /**
     * Load schema from file
     *
     * @throws \Exception If schema file cannot be read
     */
    private function loadSchema(): void {
        if (!file_exists(self::SCHEMA_PATH)) {
            throw new \Exception("Schema file not found");
        }

        $this->schema = file_get_contents(self::SCHEMA_PATH);
        if ($this->schema === false) {
            throw new \Exception("Failed to read schema file");
        }
    }

    /**
     * Load resolver classes
     */
    private function loadResolvers(): void {
        $resolverPath = __DIR__ . '/Resolvers';
        foreach (glob($resolverPath . '/*Resolver.php') as $file) {
            $className = 'Game\\GraphQL\\Resolvers\\' . basename($file, '.php');
            if (class_exists($className)) {
                $type = str_replace('Resolver', '', basename($file, '.php'));
                $this->resolvers[$type] = new $className();
            }
        }
    }
} 