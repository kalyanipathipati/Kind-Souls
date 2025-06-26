<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Ratchet\Server\IoServer;

class ChatServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection: ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection ({$conn->resourceId}) disconnected.\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        $conn->close();
    }
}

// ✅ FIXED: Correct way to initialize the WebSocket server
$loop = Loop::get();
$socket = new SocketServer('0.0.0.0:8080', [], $loop);
$server = new IoServer(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    $socket,
    $loop
);

echo "WebSocket Server started on ws://localhost:8080\n";
$loop->run();
