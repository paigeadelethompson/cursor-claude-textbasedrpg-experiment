<?php

namespace Game\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use RdKafka\KafkaConsumer;

class MarketplaceSocket implements MessageComponentInterface {
    private $clients;
    private $consumer;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        
        // Set up Kafka consumer
        $conf = new \RdKafka\Conf();
        $conf->set('group.id', 'marketplace_socket_group');
        $conf->set('metadata.broker.list', 'kafka:9092');
        
        $this->consumer = new KafkaConsumer($conf);
        $this->consumer->subscribe(['marketplace_listings']);
        
        // Start consuming messages
        $this->startConsumer();
    }

    private function startConsumer() {
        while (true) {
            $message = $this->consumer->consume(120*1000);
            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                // Broadcast change to all connected clients
                foreach ($this->clients as $client) {
                    $client->send($message->payload);
                }
            }
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Handle client messages if needed
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
} 