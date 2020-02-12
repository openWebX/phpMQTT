<?php declare(strict_types=1);

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

/**
 * Class phpMQTT
 * @package openWebX
 */
class phpMQTT {
    
    /**
     * @var resource
     */
    private $socket;                /* holds the socket	*/
    /**
     * @var int
     */
    private int $msgId = 1;             /* counter for message id */
    /**
     * @var int
     */
    public int $keepalive = 10;         /* default keepalive timmer */
    /**
     * @var int
     */
    public int $timeSincePing = 0;          /* host unix time, used to detect disconects */
    /**
     * @var array
     */
    public array $topics = [];            /* used to store currently subscribed topics */
    /**
     * @var bool
     */
    public bool $debug = false;          /* should output debug messages */
    /**
     * @var string|null
     */
    public ?string $address = null;                /* broker address */
    /**
     * @var string|null
     */
    public ?string $port = null;                   /* broker port */
    /**
     * @var string|null
     */
    public ?string $clientId = null;               /* client id sent to broker */
    /**
     * @var array|null
     */
    public ?array $will = null;                   /* stores the will of the client */
    /**
     * @var string|null
     */
    private ?string $username = null;              /* stores username */
    /**
     * @var string|null
     */
    private ?string $password = null;              /* stores password */
    /**
     * @var string|null
     */
    public ?string $caFile = null;
    
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
     * @param string      $address
     * @param string      $port
     * @param string      $clientid
     * @param null|string $cafile
     *
     * @return bool
     */
    public function broker (string $address, string $port, string $clientid, ?string $cafile = null) : bool {
        $this->address = $address;
        $this->port = $port;
        $this->clientId = $clientid;
        $this->caFile = $cafile;
        return true;
    }
    
    /**
     * @param bool        $clean
     * @param null|array $will
     * @param null|string $username
     * @param null|string $password
     *
     * @return bool
     */
    public function connect_auto (bool $clean = true, ?array $will = null, ?string $username = null, ?string $password = null) : bool {
        while ($this->connect($clean, $will, $username, $password) === false) {
            sleep(10);
        }
        return true;
    }
    
    /**
     * @param bool        $clean
     * @param null|array $will
     * @param null|string $username
     * @param null|string $password
     *
     * @return bool
     */
    public function connect (bool $clean = true, ?array $will = null, ?string $username = null, ?string $password = null) : bool {
        $this->will = $will;
        $this->username = $username;
        $this->password = $password;

        
        if ($this->caFile) {
            $socketContext = stream_context_create([
                'ssl' => [
                    'verify_peer_name' => true,
                    'cafile' => $this->caFile,
                ]
            ]);
            $this->socket = stream_socket_client('tls://' . $this->address . ':' . $this->port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $socketContext);
        } else {
            $this->socket = stream_socket_client('tcp://' . $this->address . ':' . $this->port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT);
        }
        
        if (!$this->socket) {
            if ($this->debug) {
                error_log("stream_socket_create() $errno, $errstr \n");
            }
            return false;
        }
        
        stream_set_timeout($this->socket, 5);
        stream_set_blocking($this->socket, false);
        
        $i = 0;
        $buffer = '';
        $buffer .= chr(0x00); $i++;
        $buffer .= chr(0x06); $i++;
        $buffer .= chr(0x4d); $i++;
        $buffer .= chr(0x51); $i++;
        $buffer .= chr(0x49); $i++;
        $buffer .= chr(0x73); $i++;
        $buffer .= chr(0x64); $i++;
        $buffer .= chr(0x70); $i++;
        $buffer .= chr(0x03); $i++;
        
        //No Will
        $var = 0;
        if ($clean) {
            $var += 2;
        }
        
        //Add will info to header
        if ($this->will) {
            $var += 4; // Set will flag
            $var += ($this->will['qos'] << 3); //Set will qos
            if ($this->will['retain']) {
                $var += 32;
            } //Set will retain
        }
        
        if ($this->username) {
            $var += 128;
        }    //Add username to header
        if ($this->password) {
            $var += 64;
        }    //Add password to header
        
        $buffer .= chr($var);
        $i++;
        
        //Keep alive
        $buffer .= chr($this->keepalive >> 8); $i++;
        $buffer .= chr($this->keepalive & 0xff); $i++;
        
        $buffer .= $this->writeString($this->clientId, $i);
        
        //Adding will to payload
        if ($this->will) {
            $buffer .= $this->writeString($this->will['topic'], $i);
            $buffer .= $this->writeString($this->will['content'], $i);
        }
        
        if ($this->username) {
            $buffer .= $this->writeString($this->username, $i);
        }
        if ($this->password) {
            $buffer .= $this->writeString($this->password, $i);
        }
        
        $head = '  ';
        $head[0] = chr(0x10);
        $head[1] = chr($i);
        
        fwrite($this->socket, $head, 2);
        fwrite($this->socket, $buffer);
        
        $string = $this->read(4);
        
        if (ord($string[0]) >> 4 === 2 && $string[3] === chr(0)) {
            if ($this->debug) {
                echo "Connected to Broker\n";
            }
        } else {
            error_log(sprintf("Connection failed! (Error: 0x%02x 0x%02x)\n", ord($string[0]), ord($string[3])));
            return false;
        }
        $this->timeSincePing = time();
        return true;
    }
    
    /**
     * @param int       $int
     * @param bool|null $nb
     *
     * @return null|string
     */
    public function read (int $int = 8192, ?bool $nb = false) : ?string {
        $string = '';
        $togo = $int;
        
        if ($nb) {
            return fread($this->socket, $togo);
        }
        
        while (!feof($this->socket) && $togo > 0) {
            $fread = fread($this->socket, $togo);
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
        $buffer = '';
        $id = $this->msgId;
        $buffer .= chr($id >> 8); $i++;
        $buffer .= chr($id % 256); $i++;
        
        foreach ($topics as $key => $topic) {
            $buffer .= $this->writeString($key, $i);
            $buffer .= chr($topic['qos']); $i++;
            $this->topics[$key] = $topic;
        }
        
        $cmd = 0x80;
        //$qos
        $cmd += ($qos << 1);
        
        
        $head = chr($cmd);
        $head .= chr($i);
        
        fwrite($this->socket, $head, 2);
        fwrite($this->socket, $buffer, $i);
        $string = $this->read(2);
        
        $bytes = ord($string[1]);
        $this->read($bytes);
    }
    
    /**
     *
     */
    public function ping () : void {
        $head = chr(0xc0);
        $head .= chr(0x00);
        fwrite($this->socket, $head, 2);
        if ($this->debug) {
            echo "ping sent\n";
        }
    }
    
    /**
     *
     */
    public function disconnect () : void {
        $head = ' ';
        $head[0] = chr(0xe0);
        $head[1] = chr(0x00);
        fwrite($this->socket, $head, 2);
    }
    
    /**
     *
     */
    public function close () : void {
        $this->disconnect();
        stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
    }
    
    /**
     * @param string $topic
     * @param string $content
     * @param int    $qos
     * @param int    $retain
     */
    public function publish (string $topic, string $content, int $qos = 0, int $retain = 0) : void {
        
        $i = 0;
        $buffer = '';
        $buffer .= $this->writeString($topic, $i);

        if ($qos) {
            $id = $this->msgId;
            $this->msgId = $id + 1;
            $buffer .= chr($id >> 8); $i++;
            $buffer .= chr($id % 256); $i++;
        }
        
        $buffer .= $content;
        $i += strlen($content);
        
        
        $head = ' ';
        $cmd = 0x30;
        if ($qos) {
            $cmd += $qos << 1;
        }
        if ($retain) {
            ++$cmd;
        }
        
        $head[0] = chr($cmd);
        $head .= $this->setMsgLength($i);
        
        fwrite($this->socket, $head, strlen($head));
        fwrite($this->socket, $buffer, $i);
    }
    
    /**
     * @param string $msg
     */
    public function message (string $msg) : void {
        $tlen = (ord($msg[0]) << 8) + ord($msg[1]);
        $topic = substr($msg, 2, $tlen);
        $msg = substr($msg, ($tlen + 2));
        $found = 0;
        foreach ($this->topics as $key => $top) {
            if (is_callable($top['function']) && preg_match('/^' . str_replace('#', '.*',
                        str_replace('+', "[^\/]*",
                            str_replace('/', "\/",
                                str_replace('$', '\$',
                                    $key)))) . '$/', $topic)) {
                                        call_user_func($top['function'], $topic, $msg);
                                        $found = 1;
                                    }
        }
        
        if (!$found && $this->debug) {
            echo "msg received but no match in subscriptions\n";
        }
    }
    
    /**
     * @param bool $loop
     *
     * @return int|null
     */
    public function proc (bool $loop = true) : ?int {
        
        if (1) {

            if (feof($this->socket)) {
                if ($this->debug) {
                    echo "eof receive going to reconnect for good measure\n";
                }
                fclose($this->socket);
                $this->connect_auto(false);
                if (count($this->topics)) {
                    $this->subscribe($this->topics);
                }
            }
            
            $byte = $this->read(1, true);
            
            $string = '';
            
            if ($byte === '') {
                if ($loop) {
                    usleep(100000);
                }
                
            } else {
                
                $cmd = (int) (ord($byte) / 16);
                if ($this->debug) {
                    echo "Received: $cmd\n";
                }
                
                $multiplier = 1;
                $value = 0;
                do {
                    $digit = ord($this->read(1));
                    $value += ($digit & 127) * $multiplier;
                    $multiplier *= 128;
                } while (($digit & 128) !== 0);
                
                if ($this->debug) {
                    echo "Fetching: $value\n";
                }
                
                if ($value){
                    $string = $this->read($value);
                }
                
                
                if ($cmd) {
                    if ($cmd === 3) {
                        $this->message($string);
                    }
                    
                    $this->timeSincePing = time();
                }
            }
            
            if ($this->timeSincePing < (time() - $this->keepalive)) {
                if ($this->debug) {
                    echo "not found something so ping\n";
                }
                $this->ping();
            }
            
            
            if ($this->timeSincePing < (time() - ($this->keepalive * 2))) {
                if ($this->debug) {
                    echo "not seen a package in a while, disconnecting\n";
                }
                fclose($this->socket);
                $this->connect_auto(false);
                if (count($this->topics)) {
                    $this->subscribe($this->topics);
                }
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
    public function getMsgLength (?string &$msg, ?int &$i) : ?int {
        $multiplier = 1;
        $value = 0;
        do {
            $digit = ord($msg[$i]);
            $value += ($digit & 127) * $multiplier;
            $multiplier *= 128;
            $i++;
        } while (($digit & 128) !== 0);
        
        return $value;
    }
    
    
    /**
     * @param int $len
     *
     * @return null|string
     */
    public function setMsgLength (int $len) : ?string{
        $string = '';
        do {
            $digit = $len % 128;
            $len >>= 7;
            // if there are more digits to encode, set the top bit of this digit
            if ($len > 0) {
                $digit |= 0x80;
            }
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
    public function writeString (string $str, int &$i) : string {
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
    public function printString (string $string) : void {
        $strlen = strlen($string);
        for ($j = 0; $j < $strlen; $j++) {
            $num = ord($string[$j]);
            if ($num > 31) {
                $chr = $string[$j];
            } else {
                $chr = ' ';
            }
            printf("%4d: %08b : 0x%02x : %s \n", $j, $num, $num, $chr);
        }
    }
}
