<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Auth;

class AuthResolver extends Resolver {
    public function register(array $args): array {
        $auth = new Auth();
        return $auth->register($args['username'], $args['password']);
    }

    public function login(array $args): array {
        $auth = new Auth();
        return $auth->login($args['username'], $args['password']);
    }

    public function logout(): bool {
        if (!isset($this->context['token'])) {
            return false;
        }

        $auth = new Auth();
        $auth->logout($this->context['token']);
        return true;
    }
} 