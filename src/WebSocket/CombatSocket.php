<?php

namespace Game\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use RdKafka\KafkaConsumer;

class CombatSocket implements MessageComponentInterface {
    private $clients;
    private $consumer;
    private $healthCheck;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->healthCheck = new HealthCheck();
        
        // Set up Kafka consumer for combat events
        $conf = new \RdKafka\Conf();
        $conf->set('group.id', 'combat_socket_group');
        $conf->set('metadata.broker.list', 'kafka:9092');
        
        $this->consumer = new KafkaConsumer($conf);
        $this->consumer->subscribe(['combat_logs', 'hospital_stays', 'combat_stats']);
        
        $this->startConsumer();
    }

    private function startConsumer() {
        while (true) {
            $message = $this->consumer->consume(120*1000);
            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $this->healthCheck->recordKafkaMessage();
                // Broadcast combat updates to relevant clients
                $data = json_decode($message->payload, true);
                foreach ($this->clients as $client) {
                    if ($this->shouldReceiveUpdate($client, $data)) {
                        $client->send($message->payload);
                    }
                }
            }
        }
    }

    private function shouldReceiveUpdate($client, $data) {
        // Check if client should receive this update based on player/faction ID
        $playerData = $client->playerData ?? null;
        if (!$playerData) return false;

        if (isset($data['player_id']) && $data['player_id'] === $playerData['id']) {
            return true;
        }

        if (isset($data['faction_id']) && $data['faction_id'] === $playerData['faction_id']) {
            return true;
        }

        return false;
    }

    /**
     * Handle new WebSocket connection
     *
     * @param ConnectionInterface $conn Connection instance
     * @return void
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->healthCheck->updateClientCount($this->clients->count());
    }

    /**
     * Handle WebSocket connection close
     *
     * @param ConnectionInterface $conn Connection instance
     * @return void
     */
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->healthCheck->updateClientCount($this->clients->count());
    }

    /**
     * Handle incoming WebSocket messages
     *
     * @param ConnectionInterface $from Connection that sent the message
     * @param string $msg The message
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        // Handle authentication message
        if (isset($data['type']) && $data['type'] === 'auth') {
            $from->playerData = $data['player'];
            return;
        }

        // Handle subscription requests
        if (isset($data['type']) && $data['type'] === 'subscribe') {
            if (isset($data['topics'])) {
                $from->subscriptions = array_unique(
                    array_merge($from->subscriptions ?? [], $data['topics'])
                );
            }
            return;
        }
    }

    /**
     * Handle WebSocket errors
     *
     * @param ConnectionInterface $conn Connection instance
     * @param \Exception $e Error instance
     * @return void
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->healthCheck->recordError();
        error_log("WebSocket error: " . $e->getMessage());
        $conn->close();
    }

    /**
     * Filter messages based on topic subscriptions
     *
     * @param ConnectionInterface $client Client connection
     * @param string $topic Message topic
     * @return bool Whether client should receive message
     */
    private function isSubscribed(ConnectionInterface $client, string $topic): bool {
        return in_array($topic, $client->subscriptions ?? []);
    }

    /**
     * Start the WebSocket server
     *
     * @return void
     */
    public static function run(): void {
        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer(
                new \Ratchet\WebSocket\WsServer(
                    new self()
                )
            ),
            8080
        );

        $server->run();
    }

    // ... rest of WebSocket implementation ...
} 