<?php

namespace Game\Types;

class PropertyHookType {
    private $value;
    private $hooks = [];

    public function __construct($value = null) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $oldValue = $this->value;
        $this->value = $value;
        
        foreach ($this->hooks as $hook) {
            $hook($value, $oldValue);
        }
    }

    public function addHook(callable $hook) {
        $this->hooks[] = $hook;
    }
} 