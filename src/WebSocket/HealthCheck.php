<?php

namespace Game\WebSocket;

class HealthCheck {
    private $startTime;
    private $lastKafkaMessage;
    private $connectedClients;
    private $messageCount;
    private $errorCount;

    public function __construct() {
        $this->startTime = time();
        $this->lastKafkaMessage = time();
        $this->connectedClients = 0;
        $this->messageCount = 0;
        $this->errorCount = 0;
    }

    public function recordKafkaMessage(): void {
        $this->lastKafkaMessage = time();
        $this->messageCount++;
    }

    public function recordError(): void {
        $this->errorCount++;
    }

    public function updateClientCount(int $count): void {
        $this->connectedClients = $count;
    }

    public function getStatus(): array {
        return [
            'uptime' => time() - $this->startTime,
            'connected_clients' => $this->connectedClients,
            'messages_processed' => $this->messageCount,
            'errors' => $this->errorCount,
            'last_kafka_message' => time() - $this->lastKafkaMessage,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
    }

    public function isHealthy(): bool {
        // Consider unhealthy if:
        // - No Kafka messages in last 5 minutes
        // - Error rate > 10%
        // - Memory usage > 90%
        
        $noRecentMessages = (time() - $this->lastKafkaMessage) > 300;
        $highErrorRate = $this->messageCount > 0 && 
                        ($this->errorCount / $this->messageCount) > 0.1;
        $highMemory = memory_get_usage(true) > (0.9 * ini_get('memory_limit'));

        return !($noRecentMessages || $highErrorRate || $highMemory);
    }
} 