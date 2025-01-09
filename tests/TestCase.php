<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Game\Types\PropertyHookType;

abstract class TestCase extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        
        // Ensure PropertyHookType exists
        if (!class_exists(PropertyHookType::class)) {
            throw new \RuntimeException('PropertyHookType class is required for tests');
        }
    }

    protected function createPropertyHook($initialValue = null) {
        return new PropertyHookType($initialValue);
    }
} 