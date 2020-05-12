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

}