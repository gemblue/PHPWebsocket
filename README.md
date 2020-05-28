# PHPWebsocket

Simple PHP Websocket Library / Server for Fun. Just making a wrapper from PHP Socket API. Modifying source code from 
reference and making simple OOP for a clean code.

## Dependency

PHP Socket Library
https://www.php.net/manual/en/book.sockets.php

## Install

Create a exercise folder. Open it and run composer require.

```
composer require gemblue/php-websocket
```

## Run Server

- Make server file executable

```
sudo chmod +x ./vendor/gemblue/php-websocket/bin/server
```

- Run websocket server with port option

```
./vendor/gemblue/php-websocket/bin/server port:3000
```

Then it will show success output like 

```
Listening incoming request on port 3000 ..
```

## Prepare client.

Create a HTML file for websocket client, for example `index.html` :

```html
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">

<div class="container">

	<div class="card mt-5 mb-3">
		<div class="card-body">
			<div id="output"></div>
		</div>
	</div>

	<div class="form-group">
		<label>Nama</label>
		<input id="name" class="form-control"/>
	</div>

	<div class="form-group">
		<label>Pesan</label>
		<textarea id="message" class="form-control"></textarea>
	</div>

	<button id="btn-send" class="btn btn-success">Send</button>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<script>  
	function showMessage(messageHTML) {
		$('#output').append(messageHTML);
	}

	$(document).ready(function(){
		var websocket = new WebSocket("ws://127.0.0.1:3000");
		websocket.onopen = function(event) {
			showMessage("<div class='text-success'>Berhasil masuk room ..</div>");		
		}
		websocket.onmessage = function(event) {
			var Data = JSON.parse(event.data);
			showMessage("<div>"+Data.message+"</div>");
			$('#message').val('');
		};
		
		websocket.onerror = function(event){
			showMessage("<div>Problem due to some Error</div>");
		};
		websocket.onclose = function(event){
			showMessage("<div>Connection Closed</div>");
		}; 
		
		$('#btn-send').on("click",function(event){
			event.preventDefault();
			var messageJSON = {
				name: $('#name').val(),
				message: $('#message').val()
			};
			websocket.send(JSON.stringify(messageJSON));
		});
	});
</script>
```

Open `index.html` with your browser. Use 2 tab/browser for simulation. Output will be like this :

![Sample](https://i.ibb.co/PGgH8vy/screenshot-ibb-co-2020-05-14-19-17-38.png)

## Reference

- https://stackoverflow.com/questions/42955033/php-client-web-socket-to-send-messages/43121475
- https://phppot.com/php/simple-php-chat-using-websocket
- https://medium.com/@cn007b/super-simple-php-websocket-example-ea2cd5893575
