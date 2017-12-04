<?php

namespace openWebX;

/*
 	phpMQTT
	A simple php class to connect/publish/subscribe to an MQTT broker

*/

/*
	Licence

	Copyright (c) 2010 Blue Rhinos Consulting | Andrew Milsted
	andrew@bluerhinos.co.uk | http://www.bluerhinos.co.uk

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
	
*/

/* phpMQTT */
/**
 * Class phpMQTT
 * @package Bluerhinos
 */
/**
 * Class phpMQTT
 * @package Bluerhinos
 */
class phpMQTT {
    
    /**
     * @var
     */
    private $socket;                /* holds the socket	*/
    /**
     * @var int
     */
    private $msgid = 1;             /* counter for message id */
    /**
     * @var int
     */
    public $keepalive = 10;         /* default keepalive timmer */
    /**
     * @var
     */
    public $timesinceping;          /* host unix time, used to detect disconects */
    /**
     * @var array
     */
    public $topics = [];            /* used to store currently subscribed topics */
    /**
     * @var bool
     */
    public $debug = false;          /* should output debug messages */
    /**
     * @var
     */
    public $address;                /* broker address */
    /**
     * @var
     */
    public $port;                   /* broker port */
    /**
     * @var
     */
    public $clientid;               /* client id sent to brocker */
    /**
     * @var
     */
    public $will;                   /* stores the will of the client */
    /**
     * @var
     */
    private $username;              /* stores username */
    /**
     * @var
     */
    private $password;              /* stores password */
    /**
     * @var
     */
    public $cafile;
    
    /**
     * phpMQTT constructor.
     *
     * @param string      $address
     * @param string      $port
     * @param string      $clientid
     * @param null|string $cafile
     */
    public function __construct (string $address, string $port, string $clientid, ?string $cafile = null) {
        $this->broker($address, $port, $clientid, $cafile);
    }
    
    /**
     * @return mixed
     */
    public function getSocket () {
        return $this->socket;
    }
    
    /**
     * @param mixed $socket
     */
    public function setSocket ($socket) {
        $this->socket = $socket;
    }
    
    /**
     * @return int
     */
    public function getMsgid (): int {
        return $this->msgid;
    }
    
    /**
     * @param int $msgid
     */
    public function setMsgid (int $msgid) {
        $this->msgid = $msgid;
    }
    
    /**
     * @return int
     */
    public function getKeepalive (): int {
        return $this->keepalive;
    }
    
    /**
     * @param int $keepalive
     */
    public function setKeepalive (int $keepalive) {
        $this->keepalive = $keepalive;
    }
    
    /**
     * @return mixed
     */
    public function getTimesinceping () {
        return $this->timesinceping;
    }
    
    /**
     * @param mixed $timesinceping
     */
    public function setTimesinceping ($timesinceping) {
        $this->timesinceping = $timesinceping;
    }
    
    /**
     * @return array
     */
    public function getTopics (): array {
        return $this->topics;
    }
    
    /**
     * @param array $topics
     */
    public function setTopics (array $topics) {
        $this->topics = $topics;
    }
    
    /**
     * @return bool
     */
    public function getDebug (): bool {
        return $this->debug;
    }
    
    /**
     * @param bool $debug
     */
    public function setDebug (bool $debug) {
        $this->debug = $debug;
    }
    
    /**
     * @return mixed
     */
    public function getAddress () {
        return $this->address;
    }
    
    /**
     * @param mixed $address
     */
    public function setAddress ($address) {
        $this->address = $address;
    }
    
    /**
     * @return mixed
     */
    public function getPort () {
        return $this->port;
    }
    
    /**
     * @param mixed $port
     */
    public function setPort ($port) {
        $this->port = $port;
    }
    
    /**
     * @return mixed
     */
    public function getClientid () {
        return $this->clientid;
    }
    
    /**
     * @param mixed $clientid
     */
    public function setClientid ($clientid) {
        $this->clientid = $clientid;
    }
    
    /**
     * @return mixed
     */
    public function getWill () {
        return $this->will;
    }
    
    /**
     * @param mixed $will
     */
    public function setWill ($will) {
        $this->will = $will;
    }
    
    /**
     * @return mixed
     */
    public function getUsername () {
        return $this->username;
    }
    
    /**
     * @param mixed $username
     */
    public function setUsername ($username) {
        $this->username = $username;
    }
    
    /**
     * @return mixed
     */
    public function getPassword () {
        return $this->password;
    }
    
    /**
     * @param mixed $password
     */
    public function setPassword ($password) {
        $this->password = $password;
    }
    
    /**
     * @return mixed
     */
    public function getCafile () {
        return $this->cafile;
    }
    
    /**
     * @param mixed $cafile
     */
    public function setCafile ($cafile) {
        $this->cafile = $cafile;
    }
    
    /**
     * @param string      $address
     * @param string      $port
     * @param string      $clientid
     * @param null|string $cafile
     *
     * @return bool
     */
    public function broker (string $address, string $port, string $clientid, ?string $cafile = null) : bool {
        $this->setAddress($address);
        $this->setPort($port);
        $this->setClientid($clientid);
        $this->setCafile($cafile);
        return true;
    }
    
    /**
     * @param bool        $clean
     * @param null|string $will
     * @param null|string $username
     * @param null|string $password
     *
     * @return bool
     */
    public function connect_auto (bool $clean = true, ?string $will = null, ?string $username = null, ?string $password = null) : bool {
        while ($this->connect($clean, $will, $username, $password) == false) {
            sleep(10);
        }
        return true;
    }
    
    /**
     * @param bool        $clean
     * @param null|string $will
     * @param null|string $username
     * @param null|string $password
     *
     * @return bool
     */
    public function connect (bool $clean = true, ?string $will = null, ?string $username = null, ?string $password = null) : bool {
        if ($will) {
            $this->setWill($will);
        }
        if ($username) {
            $this->setUsername($username);
        }
        if ($password) {
            $this->setPassword($password);
        }
        
        if ($this->getCafile()) {
            $socketContext = stream_context_create([
                "ssl" => [
                    "verify_peer_name" => true,
                    "cafile" => $this->getCafile(),
                ]
            ]);
            $this->setSocket(stream_socket_client("tls://" . $this->address . ":" . $this->port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $socketContext));
        } else {
            $this->setSocket(stream_socket_client("tcp://" . $this->address . ":" . $this->port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT));
        }
        
        if (!$this->getSocket()) {
            if ($this->getDebug()) {
                error_log("stream_socket_create() $errno, $errstr \n");
            }
            return false;
        }
        
        stream_set_timeout($this->getSocket(), 5);
        stream_set_blocking($this->getSocket(), 0);
        
        $i = 0;
        $buffer = "";
        $buffer .= chr(0x00);
        $i++;
        $buffer .= chr(0x06);
        $i++;
        $buffer .= chr(0x4d);
        $i++;
        $buffer .= chr(0x51);
        $i++;
        $buffer .= chr(0x49);
        $i++;
        $buffer .= chr(0x73);
        $i++;
        $buffer .= chr(0x64);
        $i++;
        $buffer .= chr(0x70);
        $i++;
        $buffer .= chr(0x03);
        $i++;
        
        //No Will
        $var = 0;
        if ($clean) {
            $var += 2;
        }
        
        //Add will info to header
        if ($this->getWill() != null) {
            $var += 4; // Set will flag
            $var += ($this->getWill()['qos'] << 3); //Set will qos
            if ($this->getWill()['retain']) {
                $var += 32;
            } //Set will retain
        }
        
        if ($this->getUsername() != null) {
            $var += 128;
        }    //Add username to header
        if ($this->getPassword() != null) {
            $var += 64;
        }    //Add password to header
        
        $buffer .= chr($var);
        $i++;
        
        //Keep alive
        $buffer .= chr($this->getKeepalive() >> 8);
        $i++;
        $buffer .= chr($this->getKeepalive() & 0xff);
        $i++;
        
        $buffer .= $this->strwritestring($this->getClientid(), $i);
        
        //Adding will to payload
        if ($this->getWill() != null) {
            $buffer .= $this->strwritestring($this->getWill()['topic'], $i);
            $buffer .= $this->strwritestring($this->getWill()['content'], $i);
        }
        
        if ($this->getUsername()) {
            $buffer .= $this->strwritestring($this->getUsername(), $i);
        }
        if ($this->getPassword()) {
            $buffer .= $this->strwritestring($this->getPassword(), $i);
        }
        
        $head = "  ";
        $head{0} = chr(0x10);
        $head{1} = chr($i);
        
        fwrite($this->getSocket(), $head, 2);
        fwrite($this->getSocket(), $buffer);
        
        $string = $this->read(4);
        
        if (ord($string{0}) >> 4 == 2 && $string{3} == chr(0)) {
            if ($this->getDebug()) {
                echo "Connected to Broker\n";
            }
        } else {
            error_log(sprintf("Connection failed! (Error: 0x%02x 0x%02x)\n", ord($string{0}), ord($string{3})));
            return false;
        }
        $this->setTimesinceping(time());
        return true;
    }
    
    /**
     * @param int       $int
     * @param bool|null $nb
     *
     * @return null|string
     */
    public function read (int $int = 8192, ?bool $nb = false) : ?string {
        $string = "";
        $togo = $int;
        
        if ($nb) {
            return fread($this->getSocket(), $togo);
        }
        
        while (!feof($this->getSocket()) && $togo > 0) {
            $fread = fread($this->getSocket(), $togo);
            $string .= $fread;
            $togo = $int - strlen($string);
        }
        return $string;
    }
    
    /**
     * @param array $topics
     * @param int   $qos
     */
    public function subscribe (array $topics, int $qos = 0) : void {
        $i = 0;
        $buffer = "";
        $id = $this->getMsgid();
        $buffer .= chr($id >> 8);
        $i++;
        $buffer .= chr($id % 256);
        $i++;
        
        foreach ($topics as $key => $topic) {
            $buffer .= $this->strwritestring($key, $i);
            $buffer .= chr($topic["qos"]);
            $i++;
            $this->getTopics()[$key] = $topic;
        }
        
        $cmd = 0x80;
        //$qos
        $cmd += ($qos << 1);
        
        
        $head = chr($cmd);
        $head .= chr($i);
        
        fwrite($this->getSocket(), $head, 2);
        fwrite($this->getSocket(), $buffer, $i);
        $string = $this->read(2);
        
        $bytes = ord(substr($string, 1, 1));
        $this->read($bytes);
    }
    
    /**
     *
     */
    public function ping () : void {
        $head = chr(0xc0);
        $head .= chr(0x00);
        fwrite($this->getSocket(), $head, 2);
        if ($this->getDebug()) {
            echo "ping sent\n";
        }
    }
    
    /**
     *
     */
    public function disconnect () : void {
        $head = " ";
        $head{0} = chr(0xe0);
        $head{1} = chr(0x00);
        fwrite($this->getSocket(), $head, 2);
    }
    
    /**
     *
     */
    public function close () : void {
        $this->disconnect();
        stream_socket_shutdown($this->getSocket(), STREAM_SHUT_WR);
    }
    
    /**
     * @param string $topic
     * @param string $content
     * @param int    $qos
     * @param int    $retain
     */
    public function publish (string $topic, string $content, int $qos = 0, int $retain = 0) : void {
        
        $i = 0;
        $buffer = "";
        $buffer .= $this->strwritestring($topic, $i);

        if ($qos) {
            $id = $this->getMsgid();
            $this->setMsgid($id + 1);
            $buffer .= chr($id >> 8);
            $i++;
            $buffer .= chr($id % 256);
            $i++;
        }
        
        $buffer .= $content;
        $i += strlen($content);
        
        
        $head = " ";
        $cmd = 0x30;
        if ($qos) $cmd += $qos << 1;
        if ($retain) $cmd += 1;
        
        $head{0} = chr($cmd);
        $head .= $this->setmsglength($i);
        
        fwrite($this->getSocket(), $head, strlen($head));
        fwrite($this->getSocket(), $buffer, $i);
    }
    
    /**
     * @param string $msg
     */
    public function message (string $msg) : void {
        $tlen = (ord($msg{0}) << 8) + ord($msg{1});
        $topic = substr($msg, 2, $tlen);
        $msg = substr($msg, ($tlen + 2));
        $found = 0;
        foreach ($this->getTopics() as $key => $top) {
            if (preg_match("/^" . str_replace("#", ".*",
                    str_replace("+", "[^\/]*",
                        str_replace("/", "\/",
                            str_replace("$", '\$',
                                $key)))) . "$/", $topic)) {
                if (is_callable($top['function'])) {
                    call_user_func($top['function'], $topic, $msg);
                    $found = 1;
                }
            }
        }
        
        if ($this->getDebug() && !$found) {
            echo "msg recieved but no match in subscriptions\n";
        }
    }
    
    /**
     * @param bool $loop
     *
     * @return int|null
     */
    public function proc (bool $loop = true) : ?int {
        
        if (1) {
            //$sockets = [$this->getSocket()];
            //$w = $e = null;
            //$cmd = 0;
            
            //$byte = fgetc($this->socket);
            if (feof($this->getSocket())) {
                if ($this->getDebug()) {
                    echo "eof receive going to reconnect for good measure\n";
                }
                fclose($this->getSocket());
                $this->connect_auto(false);
                if (count($this->getTopics())) {
                    $this->subscribe($this->getTopics());
                }
            }
            
            $byte = $this->read(1, true);
            
            $string = '';
            
            if (!strlen($byte)) {
                if ($loop) {
                    usleep(100000);
                }
                
            } else {
                
                $cmd = (int) (ord($byte) / 16);
                if ($this->getDebug()) {
                    echo "Recevid: $cmd\n";
                }
                
                $multiplier = 1;
                $value = 0;
                do {
                    $digit = ord($this->read(1));
                    $value += ($digit & 127) * $multiplier;
                    $multiplier *= 128;
                } while (($digit & 128) != 0);
                
                if ($this->getDebug()) {
                    echo "Fetching: $value\n";
                }
                
                if ($value){
                    $string = $this->read($value);
                }
                
                
                if ($cmd) {
                    switch ($cmd) {
                        case 3:
                            $this->message($string);
                            break;
                    }
                    
                    $this->setTimesinceping(time());
                }
            }
            
            if ($this->getTimesinceping() < (time() - $this->getKeepalive())) {
                if ($this->getDebug()) {
                    echo "not found something so ping\n";
                }
                $this->ping();
            }
            
            
            if ($this->getTimesinceping() < (time() - ($this->getKeepalive() * 2))) {
                if ($this->getDebug()) {
                    echo "not seen a package in a while, disconnecting\n";
                }
                fclose($this->getSocket());
                $this->connect_auto(false);
                if (count($this->getTopics()))
                    $this->subscribe($this->getTopics());
            }
            
        }
        return 1;
    }
    
    /**
     * @param null|string $msg
     * @param int|null    $i
     *
     * @return int|null
     */
    public function getmsglength (?string &$msg, ?int &$i) : ?int {
        $multiplier = 1;
        $value = 0;
        do {
            $digit = ord($msg{$i});
            $value += ($digit & 127) * $multiplier;
            $multiplier *= 128;
            $i++;
        } while (($digit & 128) != 0);
        
        return $value;
    }
    
    
    /**
     * @param int $len
     *
     * @return null|string
     */
    public function setmsglength (int $len) : ?string{
        $string = "";
        do {
            $digit = $len % 128;
            $len = $len >> 7;
            // if there are more digits to encode, set the top bit of this digit
            if ($len > 0)
                $digit = ($digit | 0x80);
            $string .= chr($digit);
        } while ($len > 0);
        
        return $string;
    }
    
    /**
     * @param string $str
     * @param int    $i
     *
     * @return string
     */
    public function strwritestring (string $str, int &$i) : string {
        $len = strlen($str);
        $msb = $len >> 8;
        $lsb = $len % 256;
        $ret = chr($msb);
        $ret .= chr($lsb);
        $ret .= $str;
        $i += ($len + 2);
        
        return $ret;
    }
    
    /**
     * @param string $string
     */
    function printstr (string $string) : void {
        $strlen = strlen($string);
        for ($j = 0; $j < $strlen; $j++) {
            $num = ord($string{$j});
            if ($num > 31) {
                $chr = $string{$j};
            } else {
                $chr = " ";
            }
            printf("%4d: %08b : 0x%02x : %s \n", $j, $num, $num, $chr);
        }
    }
}
