<?php

ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

/**
 * WebSocket 
 * 
 * PHP WebSocket simple server, just wrap a PHP Socket API.
 * For learning purpose. This class was modified from reference tutorial, making procedural code to OOP.
 * 
 * Reference :
 * https://medium.com/@cn007b/super-simple-php-websocket-example-ea2cd5893575
 * 
 * @package WebSocket
 * @author Gemblue
 */

class WebSocket {

    /** Props */
    public $server;
    public $client;

    /**
     * Create a socket
     */
    public function create(string $address, int $port) {

        // Create socket.
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $address, $port);
        socket_listen($this->server);
        
    }

    /**
     * Handshake.
     * 
     * Simple handshake to client with headers.
     */
    public function handshake() {
        // Set client.
        $this->client = socket_accept($this->server);

        // Read client.
        $request = socket_read($this->client, 5000);
        
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        $key = base64_encode(pack(
            'H*',
            sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
        
        // Setup headers.
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
        
        // Write headers.
        socket_write($this->client, $headers, strlen($headers));
    
    }

    /**
     * Emit
     * 
     * Method to emit message.
     */
    public function emit($message) {
        
        $response = chr(129) . chr(strlen($message)) . $message;
        socket_write($this->client, $response);

    }
}

// Let's consume the class.
$websocket = new WebSocket();

$websocket->create("127.0.0.1", 12345);
$websocket->handshake();
$websocket->emit('Halo kakak, lagi apa!');