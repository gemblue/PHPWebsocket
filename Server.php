<?php

/**
 * WebSocket
 * 
 * Simple WebSocket class for learning purpose. Wrapping a socket PHP API.
 * 
 * @package WebSocket
 * @author Gemblue
 */

class WebSocket {

	/** Props */
	public $server;
	public $address;
	public $port;
	public $clients;
	
	/**
	 * Construct
	 * 
	 * With address and port.
	 * 
	 * @return void
	 */
	public function __construct($address, $port) {

		$this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$this->address = $address;
		$this->port = $port;

		socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->server, 0, $port);
		socket_listen($this->server);

	}

	/**
	 * Send
	 * 
	 * Kirim pesan ke semua client, sebelumnya di encode json dulu.
	 * 
	 * @return bool
	 */
	function send($message) {
		
		// Build json dengan seal.
		$raw = $this->seal(json_encode([
			'message'=> $message
		]));
		
		foreach($this->clients as $client)
		{
			@socket_write($client, $raw, strlen($raw));
		}
		
		return true;
	}

	/**
	 * Unseal
	 * 
	 * Karena socket receive masih mentah, kita harus unseal dulu.
	 *
	 * @return string
	 */
	public function unseal($socketData) {

		$length = ord($socketData[1]) & 127;

		if ($length == 126) {
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		} elseif ($length == 127) {
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		} else {
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}
		
		$socketData = "";
		
		for ($i = 0; $i < strlen($data); ++$i) {
			$socketData .= $data[$i] ^ $masks[$i%4];
		}
		
		return $socketData;
	}

	/**
	 * Seal
	 * 
	 * Untuk mengirimkan data seal.
	 * 
	 * @return string
	 */
	function seal($socketData) {

		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($socketData);
		
		if ($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif ($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif ($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);

		return $header.$socketData;
	}

	/**
	 * Handshake
	 * 
	 * Mengirimkan handshake headers ke client yang connect.
	 * 
	 * @return void
	 */
	function handshake($header, $socket, $address, $port) {

		$headers = array();
		$lines = preg_split("/\r\n/", $header);
		foreach($lines as $line)
		{
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
			{
				$headers[$matches[1]] = $matches[2];
			}
		}
		
		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		
		$buffer   = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n";
		$buffer  .= "Upgrade: websocket\r\n";
		$buffer  .= "Connection: Upgrade\r\n";
		$buffer  .= "WebSocket-Origin: $address\r\n";
		$buffer  .= "WebSocket-Location: ws://$address:$port/demo/shout.php\r\n";
		$buffer  .= "Sec-WebSocket-Accept:$secAccept\r\n\r\n";

		socket_write($socket,$buffer,strlen($buffer));

	}
	
	/**
	 * Run 
	 * 
	 * Running websocket server.
	 * 
	 * @return void
	 */
	public function run() {

		// Masukan koneksi server kedalam clients.
		$this->clients = [
			$this->server
		];

		// Set address and port.
		$address = $this->address;
		$port = $this->port;

		// Log message
		echo "Listening ..\n";

		// Unlimited loop.
		while (true) 
		{
			$newClients = $this->clients;
			
			socket_select($newClients, $null, $null, 0, 10);
			
			// Jika koneksi socket sekarang ada dalam clients.
			if (in_array($this->server, $newClients)) 
			{
				// Terima koneksi baru ..
				$newSocket = socket_accept($this->server);
				
				// Masukan dalam client/container socket.
				$this->clients[] = $newSocket;
				
				// Baca data masuk dari tunnel socket yang masuk tadi, browser biasanya mengirim header.
				$header = socket_read($newSocket, 1024);
				
				// Handshake, kirim balik data balasan.
				$this->handshake($header, $newSocket, $address, $port);
				
				// Beri pesan, ada client baru bergabung, ke semua connected client.
				socket_getpeername($newSocket, $ip);
				$this->send("Client dengan ip {$ip} baru saja bergabung");
				
				$index = array_search($this->server, $newClients);
				unset($newClients[$index]);
			}
			
			foreach ($newClients as $newClientsResource) 
			{	
				// Selama unlimited loop, terima data kiriman dari client, dari method "websocket.send" pada browser.
				while(socket_recv($newClientsResource, $socketData, 1024, 0) >= 1)
				{
					// Jika ada data diterima, baru proses
					if ($socketData) {
						
						// Terima data dari client, kemudian unseal dan decode json.
						$socketMessage = $this->unseal($socketData);
						$messageObj = json_decode($socketMessage);
						
						if (isset($messageObj->name) && isset($messageObj->message)) {
							// Kirim kembali, broadcast ke semua connected client.
							$this->send("{$messageObj->name} : {$messageObj->message}");
						}
						
						break 2;
					}
				}
				
				// Dalam looping juga selalu cek, client ada yang keluar apa engga. 
				// Caranya baca dari socket read berdasarkan connected client, kalau keluar kasih pesan out.
				$socketData = @socket_read($newClientsResource, 1024, PHP_NORMAL_READ);
				
				// False berarti keluar tunnel.
				if ($socketData === false) 
				{
					// Beri pesan keluar.
					socket_getpeername($newClientsResource, $ip);
					$this->send("Client dengan ip {$ip} baru saja keluar");
					
					// Hapus current index dari connected client.
					$index = array_search($newClientsResource, $this->clients);
					unset($this->clients[$index]);	
				}
			}
		}

		socket_close($this->server);
	}
}

$WebSocket = new WebSocket("127.0.0.1", 8090);
$WebSocket->run();

?>