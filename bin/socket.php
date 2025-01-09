#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

if ($argc !== 2) {
    die("Usage: php socket.php [combat|market]\n");
}

$type = $argv[1];

switch ($type) {
    case 'combat':
        \Game\WebSocket\CombatSocket::run();
        break;
    case 'market':
        \Game\WebSocket\MarketSocket::run();
        break;
    default:
        die("Invalid socket type. Use 'combat' or 'market'.\n");
} 