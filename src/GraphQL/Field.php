<?php

namespace Game\GraphQL;

/**
 * Class Field
 * Represents a GraphQL field definition and resolver
 * 
 * @package Game\GraphQL
 */
class Field {
    /** @var string Field name */
    private $name;

    /** @var string Field type */
    private $type;

    /** @var callable Field resolver */
    private $resolver;

    /** @var array Field arguments */
    private $args;

    /** @var string|null Field description */
    private $description;

    /**
     * Field constructor
     *
     * @param string $name Field name
     * @param string $type Field type
     * @param callable $resolver Field resolver function
     * @param array $args Field arguments
     * @param string|null $description Field description
     */
    public function __construct(
        string $name,
        string $type,
        callable $resolver,
        array $args = [],
        ?string $description = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->resolver = $resolver;
        $this->args = $args;
        $this->description = $description;
    }

    /**
     * Resolve field value
     *
     * @param mixed $parent Parent value
     * @param array $args Field arguments
     * @param Context $context Request context
     * @return mixed Resolved value
     */
    public function resolve($parent, array $args, Context $context) {
        return ($this->resolver)($parent, $args, $context);
    }

    /**
     * Get field definition
     *
     * @return array Field definition
     */
    public function getDefinition(): array {
        $definition = [
            'type' => $this->type,
            'args' => $this->args
        ];

        if ($this->description !== null) {
            $definition['description'] = $this->description;
        }

        return $definition;
    }

    /**
     * Get field name
     *
     * @return string Field name
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get field type
     *
     * @return string Field type
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get field arguments
     *
     * @return array Field arguments
     */
    public function getArgs(): array {
        return $this->args;
    }
} 