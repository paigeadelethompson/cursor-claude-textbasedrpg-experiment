<?php

namespace Game\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use RdKafka\KafkaConsumer;

class MarketSocket implements MessageComponentInterface {
    private $clients;
    private $consumer;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        
        // Set up Kafka consumer for market events
        $conf = new \RdKafka\Conf();
        $conf->set('group.id', 'market_socket_group');
        $conf->set('metadata.broker.list', 'kafka:9092');
        
        $this->consumer = new KafkaConsumer($conf);
        $this->consumer->subscribe([
            'marketplace_listings',
            'stock_prices',
            'stock_transactions',
            'cd_rates',
            'interest_transactions'
        ]);
        
        $this->startConsumer();
    }

    private function startConsumer() {
        while (true) {
            $message = $this->consumer->consume(120*1000);
            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $data = json_decode($message->payload, true);
                $topic = $message->topic_name;

                foreach ($this->clients as $client) {
                    if ($this->shouldReceiveUpdate($client, $data, $topic)) {
                        $client->send($message->payload);
                    }
                }
            }
        }
    }

    private function shouldReceiveUpdate($client, $data, $topic): bool {
        $playerData = $client->playerData ?? null;
        if (!$playerData || !$this->isSubscribed($client, $topic)) {
            return false;
        }

        // Filter marketplace updates
        if ($topic === 'marketplace_listings') {
            // Send if public listing or player is seller/buyer
            return !isset($data['seller_id']) || 
                   $data['seller_id'] === $playerData['id'] ||
                   ($data['buyer_id'] ?? null) === $playerData['id'];
        }

        // Filter stock transactions
        if ($topic === 'stock_transactions') {
            return $data['player_id'] === $playerData['id'];
        }

        // CD and interest updates
        if (in_array($topic, ['cd_rates', 'interest_transactions'])) {
            return $data['player_id'] === $playerData['id'];
        }

        // Stock prices are public
        if ($topic === 'stock_prices') {
            return true;
        }

        return false;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (isset($data['type']) && $data['type'] === 'auth') {
            $from->playerData = $data['player'];
            return;
        }

        if (isset($data['type']) && $data['type'] === 'subscribe') {
            if (isset($data['topics'])) {
                $from->subscriptions = array_unique(
                    array_merge($from->subscriptions ?? [], $data['topics'])
                );
            }
            return;
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        error_log("Market WebSocket error: " . $e->getMessage());
        $conn->close();
    }

    private function isSubscribed(ConnectionInterface $client, string $topic): bool {
        return in_array($topic, $client->subscriptions ?? []);
    }

    public static function run(): void {
        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer(
                new \Ratchet\WebSocket\WsServer(
                    new self()
                )
            ),
            8081  // Different port from CombatSocket
        );

        $server->run();
    }
} 