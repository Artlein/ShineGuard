<?php

/*
	phpMQTT - A simple php class to connect/publish/subscribe to an MQTT broker
	By Andrew Milsted (andrew@bluerhinos.co.uk)
	Updated for ShineGuard Project
*/

class phpMQTT {

	private $socket; 			/* holds the socket 		*/
	private $msgid = 1;			/* counter for message id 	*/
	public $keepalive = 10;		/* default keepalive interval 	*/
	public $timesinceping;		/* used to calculate pings 	*/
	public $topics = array(); 	/* holds the topics we're subscribed to */
	public $debug = false;		/* debug mode 			*/
	public $address;			/* broker address 		*/
	public $port;				/* broker port 			*/
	public $clientid;			/* client id 			*/
	public $will;				/* will message 		*/
	private $username;			/* username 			*/
	private $password;			/* password 			*/

	public function __construct($address, $port, $clientid) {
		$this->address = $address;
		$this->port = $port;
		$this->clientid = $clientid;
	}

	/* connects to the broker 
		params: $clean - should the client send a clean session flag */
	public function connect($clean = true, $will = NULL, $user = NULL, $pass = NULL) {
		
		if($will) $this->will = $will;
		if($user) $this->username = $user;
		if($pass) $this->password = $pass;

		$address = gethostbyname($this->address);	
		$this->socket = fsockopen($address, $this->port, $errno, $errstr, 5);

		if (!$this->socket) {
		    if($this->debug) error_log("mqtt: error connecting $errno $errstr");
		    return false;
		}

		stream_set_timeout($this->socket, 5);
		stream_set_blocking($this->socket, 0);

		$i = 0;
		$buffer = "";

		$buffer .= chr(0x00); $i++; $buffer .= chr(0x04); $i++;
		$buffer .= chr(0x4d); $i++; $buffer .= chr(0x51); $i++;
		$buffer .= chr(0x54); $i++; $buffer .= chr(0x54); $i++;
		$buffer .= chr(0x04); $i++;

		//Connect Flags
		$var = 0;
		if($clean) $var += 2;

		//Keep alive
		$buffer .= chr($var); $i++;
		$buffer .= chr($this->keepalive >> 8); $i++;
		$buffer .= chr($this->keepalive & 0xff); $i++;

		$buffer .= $this->strwritestring($this->clientid, $i);

		//Connect payload
		if($this->will) {
			$buffer .= $this->strwritestring($this->will['topic'], $i);  
			$buffer .= $this->strwritestring($this->will['content'], $i);
		}

		if($this->username) $buffer .= $this->strwritestring($this->username, $i);
		if($this->password) $buffer .= $this->strwritestring($this->password, $i);

		$head = chr(0x10);
		$head .= $this->setmsglength($i);

		fwrite($this->socket, $head, strlen($head));
		fwrite($this->socket, $buffer, strlen($buffer));

	 	$stop = time() + $this->keepalive;
		while(!feof($this->socket) && $stop > time()) {
			$res = $this->read(4);
			if(strlen($res) > 0) {
				if(ord($res[0])>>4 == 2 && ord($res[3]) == 0) {
					if($this->debug) error_log("mqtt: connected");
					$this->timesinceping = time();
					return true;
				}
			}
			usleep(100000);
		}
	
		return false;
	}

	/* read: reads in so many bytes */
	private function read($int = 8192, $nb = false) {
		$string = "";
		$toread = $int;

		if($nb) {
			return fread($this->socket, $toread);
		}

		while (!feof($this->socket) && $toread > 0) {
			$fread = fread($this->socket, $toread);
			$string .= $fread;
			$toread -= strlen($fread);
		}

		return $string;
	}

	/* subscribe: subscribes to topics */
	public function subscribe($topics, $qos = 0) {
		$i = 0;
		$buffer = "";
		$id = $this->msgid;
		$buffer .= chr($id >> 8);  $i++;
		$buffer .= chr($id % 256);  $i++;

		foreach($topics as $key => $topic) {
			$buffer .= $this->strwritestring($key, $i);
			$buffer .= chr($topic['qos']); $i++;
			$this->topics[$key] = $topic; 
		}

		$cmd = 0x82; 
		//$cmd +=	$qos << 1;

		$head = chr($cmd);
		$head .= $this->setmsglength($i);

		fwrite($this->socket, $head, strlen($head));
		fwrite($this->socket, $buffer, strlen($buffer));
		$this->msgid++;

		return true;
	}

	/* proc: processes messages in the queue */
	public function proc($loop = true) {
		if(feof($this->socket)) return false;

		// Ping if needed
		if($this->timesinceping + $this->keepalive < time()) {
			if($this->debug) error_log("mqtt: pinging");
			$head = chr(0xc0);		
			$head .= chr(0x00);
			fwrite($this->socket, $head, 2);
			$this->timesinceping = time();
		}

		$sockets = array($this->socket);
		$w = NULL;
		$e = NULL;

		$num_changed_sockets = stream_select($sockets, $w, $e, 0, 80000);

		if($num_changed_sockets > 0) {
			$byte = $this->read(1);
			if(strlen($byte) > 0) {
				$cmd = ord($byte);
				// Read remaining length (variable length integer)
				$multiplier = 1;
				$bytes = 0;
				do {
					$digit = ord($this->read(1));
					$bytes += ($digit & 127) * $multiplier;
					$multiplier *= 128;
				} while (($digit & 128) != 0);

				$payload = $this->read($bytes);

				if(($cmd >> 4) == 3) {
					$topic_len = ord($payload[0]) << 8 | ord($payload[1]);
					$topic = substr($payload, 2, $topic_len);
					$msg = substr($payload, 2 + $topic_len);

					if(isset($this->topics[$topic])) {
						call_user_func($this->topics[$topic]['function'], $topic, $msg);
					}
				}
			}
		}

		if($loop) return true;
		return true;
	}

	/* publish: sends a message to the broker */
	public function publish($topic, $content, $qos = 0, $retain = 0) {
		$i = 0;
		$buffer = "";
		$buffer .= $this->strwritestring($topic, $i);
		//$buffer .= $this->strwritestring($content, $i);

		if($qos > 0) {
			$id = $this->msgid;
			$buffer .= chr($id >> 8);  $i++;
		    $buffer .= chr($id % 256);  $i++;
		    $this->msgid++;
		}

		$buffer .= $content;
		$i += strlen($content);

		$cmd = 0x30;
		if($retain) $cmd += 1;
		if($qos) $cmd += $qos << 1;

		$head = chr($cmd);
		$head .= $this->setmsglength($i);

		fwrite($this->socket, $head, strlen($head));
		fwrite($this->socket, $buffer, strlen($buffer));
	}

	/* writes a string for the mqtt protocol */
	private function strwritestring($str, &$i) {
		$ret = chr(strlen($str) >> 8);
		$ret .= chr(strlen($str) % 256);
		$ret .= $str;
		$i += (strlen($str) + 2);
		return $ret;
	}

	/* calculates the length of a message for the header */
	private function setmsglength($len) {
		$string = "";
		do {
		  $digit = $len % 128;
		  $len = floor($len / 128);
		  if ($len > 0) {
		    $digit = ($digit | 0x80);
		  }
		  $string .= chr($digit);
		} while ($len > 0);
		return $string;
	}

	/* disconnect from the broker */
	public function close() {
		$head = chr(0xe0);		
		$head .= chr(0x00);
		fwrite($this->socket, $head, 2);
		stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
		fclose($this->socket);
	}
}
