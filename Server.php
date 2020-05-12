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
 *
 */

class WebSocket {

    public $address;
    public $port;
    public $server;
    public $client;
    public $key;

    /**
     * Construct a socket
     */
    public function __construct(string $address, int $port) {

        $this->address = $address;
        $this->post = $port;

        if (!$this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new Exception('Failed to create a socket');
        }
    }

    public function setOption() {
        
        if (!socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1)) {
            throw new Exception('Failed to set option');
        }

        return $this;
    
    }

    public function bind() {
        
        
        if (!socket_bind($this->server, $this->address, $this->port)) {
            throw new Exception('Failed to bind');
        }

        return $this;
    
    }

    public function listen() {
        
        if (!socket_listen($this->server)) {
            throw new Exception('Failed to listen');
        }
        
        if (!$this->client = socket_accept($this->server)) {
            throw new Exception('Failed to accept');
        }

        return $this;
    
    }

    /**
     * Handshake
     * 
     * Writing websocket protocol headers
     */
    public function handshake() {

        $request = socket_read($this->client, 5000);

        // Match websocket key
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        
        $this->key = base64_encode(pack(
            'H*',
            sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));

        // Build websocket content header!
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "server: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: {$this->key}\r\n\r\n";
        
        // Writing a content.
        socket_write($this->client, $headers, strlen($headers));

        // Return log message, outside the loop.
        echo "Writing headers .. \n";

    }

    /**
     * Broadcast
     * 
     * Writing socket content with loop.
     */
    public function broadcast(string $message) {
        
        socket_write($this->client, chr(129) . chr(strlen($message)) . $message);
 
     }
     
     /**
      * Close.
      */
     public function __destruct() 
     {
         socket_close($this->socket);
     }

}

// Mantap, sekarang kita coba.
$websocket = new WebSocket("127.0.0.1", 12345);

$websocket->setOption()
          ->bind()
          ->listen()
          ->handshake();

$websocket->broadcast('Halo kakak!');