<?php
namespace WAYF;

class Gearman {

	/**
		The gearman class provides a thin naive api on top of the gearman protocol.
		
		By default every call is blocking, but it is possible to use a timeout parameter
		which limits the time - in milli secs - a call waits for the gearman job server. 
		Calls returns null from a timout.
		
		Very simple examples of use:
		
		client:
			$c = new \dk\wayf\gearman();
			$data = 'sample';
			$res = $c->submit_job('reverse', '', $data);
			$jh = $res[1];
			$res = $c->response();
			print $res[$c::payload]; # 'elpmas'
		
		worker:
			$w = new \dk\wayf\gearman();

			while (!$w->pre_sleep(100)) {
				# do something usefull .. every 1/10th of a second ...
			};
			$job = $w->grab_job();
			$rev = strrev($job[$w->payload]);
			$w->work_complete($jh, $rev);
			sleep(1); # if your program exits after work_complete tcp needs time to send the data ..
			
		grab_job() returns:
		
		array(
			<cmd>, JOB_ASSIGN or NO_JOB
			<job handle>,
			<function name>,
			<data>
		)
		
		The worker grab_job call is always 'non-blocking' per the gearman protocol ie. it returns
		NO_JOB if the job server hasn't a job for the worker. If you want to wait/block for at job use
		pre_sleep(). If you use pre_sleep(100) it will return nothing every 1/10th second and you
		can then do something useful before calling it again. If it returns NOOP a job is available
		at the job server and you can grab it with grab_job();

		response() return:
		
		array(
			<cmd>,  WORK_DATA, WORK_WARNING, WORK_STATUS, WORK_COMPLETE, WORK_FAIL or WORK_EXCEPTION
					se gearman.org for further info - WORK_COMPLETE or WORK_FAIL marks the end of
					the job, the others are used to communicate partial results or status back
					from the worker.
			<job handle>, you will have to match with an earlier result from submit_job()
			<data>,
		)
		
		The payload data is always passed in by ref - to minimize copying.
		
		Very simple examples of use:
		
		client:
			$c = new \dk\wayf\gearman();
			$data = 'sample';
			$jh = $c->submit_job('reverse', '', $data);
			$res = $c->response();
			print $res[$c::payload]; # 'elpmas'
		
		worker:
			$w = new \dk\wayf\gearman();

			while (!$w->pre_sleep(100)) {
				# do something usefull .. every 1/10th of a second ...
			};
			$job = $w->grab_job();
			$rev = strrev($job[$w->payload]);
			$w->work_complete($jh, $rev);
			sleep(1); # if your program exits after work_complete tcp needs time to send the data ..
			
			This class does not support _BG calls.
			
	*/
	
    const CAN_DO = 1;
    const CANT_DO = 2;
    const RESET_ABILITIES = 3;
    const PRE_SLEEP = 4;
    const NOOP = 6;
    const SUBMIT_JOB = 7;
    const JOB_CREATED = 8;
    const GRAB_JOB = 9;
    const NO_JOB = 10;
    const JOB_ASSIGN = 11;
    const WORK_STATUS = 12;
    const WORK_COMPLETE = 13;
    const WORK_FAIL = 14;
    const GET_STATUS = 15;
    const ECHO_REQ = 16;
    const ECHO_RES = 17;
    const SUBMIT_JOB_BG = 18;
    const ERROR = 19;
    const STATUS_RES = 20;
    const SUBMIT_JOB_HIGH = 21;
    const SET_CLIENT_ID = 22;
    const CAN_DO_TIMEOUT = 23;
    const ALL_YOURS = 24;
    const WORK_EXCEPTION = 25;
    const OPTION_REQ = 26;
    const OPTION_RES = 27;
    const WORK_DATA = 28;
    const WORK_WARNING = 29;
    const GRAB_JOB_UNIQ = 30;
    const JOB_ASSIGN_UNIQ = 31;

    const EWOULDBLOCK = 11;
    const EAGAIN = 11;
    const SUCCESS = 0;

    const packettype = 0;
    const jobhandle = 1;
    const functionname = 2;
    const payload = 3;
    const numerator = 2;
    const denominator = 3;

    private $jobserver;

    public function __construct($host = '127.0.0.1:4730')
    {
        $port = '';
        if (strpos($host, ':'))
            list($host, $port) = explode(':', $host);
        $host or $host = '127.0.0.1';
        $port or $port = 4730;
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $errno = socket_last_error($socket);
            $errstr = socket_strerror($errno);
            throw new \Exception("Can't create socket ($errno: $errstr)");
        }
        $ok = @socket_connect($socket, $host, $port);
        if (!$ok) {
            $errno = socket_last_error($socket);
            $errstr = socket_strerror($errno);
            throw new \Exception("Can't connect to jobserver $host:$port ($errno: $errstr)");
        }
        socket_set_block($socket);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => 0));
		socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
        $this->jobserver = $socket;
    }
    
    public function __destruct() {
    	@socket_clear_error($this->jobserver);
    }

    private function request(&$data)
    {
        $len = strlen($data);
        $wassent = $sent = 0;
        do {
            $sent = @socket_write($this->jobserver, substr($data, $sent));
            if ($sent === false) {
                $err = socket_last_error($this->jobserver);
                if (!in_array($err, array(SOCKET_EAGAIN, SOCKET_EWOULDBLOCK, SOCKET_EINPROGRESS))) {
                    $errstr = socket_strerror($err);
                    throw new \Exception("Could not write command to socket ($err: $errstr)");
                }
            }
            $wassent += $sent;
        } while ($wassent < $len);
    }

    private function request2($data)
    {
        $this->request($data);
    }

    private function read($size, $timeout = 0)
    {
        $to = array('sec' => floor($timeout / 1000), 'usec' => floor($timeout % 1000) * 1000);
        socket_set_option($this->jobserver, SOL_SOCKET, SO_RCVTIMEO, $to);
        $buffer = '';
        do {
            $data = @socket_read($this->jobserver, $size - strlen($buffer));
            if ($data === false || $data === '') {
                $err = socket_last_error($this->jobserver);
                if ($err == self::SUCCESS || $err == self::EWOULDBLOCK) return '';
                $errstr = socket_strerror($err);
                throw new Exception("Could not read from socket ($err: $errstr)");
            }
            $buffer .= $data;
        } while (strlen($buffer) < $size);
        return $buffer;
    }

    public function response($timeout = 0)
    {
        $header = $this->read(12, $timeout);
        if (!$header) return '';
        $resp = @unpack('a4magic/Ntype/Nlen', $header);
        if ($resp['magic'] !== "\0RES")
            throw new Exception('Not a gearman response: ' . print_r($resp, 1));
        return array_merge(array($resp['type']), explode(chr(0), $this->read($resp['len'], 0)));
    }

    public function submit_job($function, $uniqueid, &$data)
    {
        $this->request2(pack('xa*NNa*xa*x', 'REQ', self::SUBMIT_JOB, strlen($function) + 1 + strlen($uniqueid) + 1 + strlen($data), $function, $uniqueid));
        $this->request($data);
        $resp = $this->response();
        if ($resp[0] !== self::JOB_CREATED)
            throw new \Exception('Not a job_created response after submit_job: ' . print_r($resp, 1));
        return $resp[1];
    }

    public function option_req($option = 'Exceptions')
    {
        $this->request2(pack('xa*NNa*', 'REQ', self::OPTION_REQ, strlen($option), $option));
        $resp = $this->response();
        if ($resp[0] !== self::OPTION_RES && $resp[1] != $option)
            throw new \Exception("could not set option: $option: " . print_r($resp, 1));
        return $resp;
    }

    public function can_do($function)
    {
        $this->request2(pack('xa*NNa*', 'REQ', self::CAN_DO, strlen($function), $function));
    }

    public function cant_do($function)
    {
        $this->request2(pack('xa*NNa*', 'REQ', self::CANT_DO, strlen($function), $function));
    }

    public function reset_abilities()
    {
        $this->request2(pack('xa*NN', 'REQ', self::RESET_ABILITIES, 0));
    }

    public function grab_job()
    {
        $this->request2(pack('xa*NN', 'REQ', self::GRAB_JOB, 0));
        $resp = $this->response();
        if ($resp[0] == self::NOOP)
            $resp = $this->response();
        return $resp;
    }

    public function work_thing($jobhandle, $thing, &$data)
    {
        $this->request2(pack('xa*NNa*x', 'REQ', $thing, strlen($jobhandle) + 1 + strlen($data), $jobhandle));
        $this->request($data);
    }

    public function work_data($jobhandle, &$data)
    {
        $this->work_thing($jobhandle, self::WORK_DATA, $data);
    }

    public function work_warning($jobhandle, &$data)
    {
        $this->work_thing($jobhandle, self::WORK_WARNING, $data);
    }

    public function work_exception($jobhandle, &$data)
    {
        $this->work_thing($jobhandle, self::WORK_EXCEPTION, $data);
    }

    public function work_complete($jobhandle, &$data)
    {
        $this->work_thing($jobhandle, self::WORK_COMPLETE, $data);
    }

    public function work_status($jobhandle, $numerator, $denominator)
    {
        $datalen = strlen($jobhandle) + 1 + strlen($numerator) + 1 + strlen($denominator);
        $this->request2(pack('xa*NNa*xa*xa*', 'REQ', self::WORK_STATUS, $datalen, $jobhandle, $numerator, $denominator));
    }

    public function work_fail($jobhandle)
    {
        $this->request2(pack('xa*NNa*', 'REQ', self::WORK_FAIL, strlen($jobhandle), $jobhandle));
    }

    public function pre_sleep($timeout = 0)
    {
        $this->request2(pack('xa*NN', 'REQ', self::PRE_SLEEP, 0));
        $resp = $this->response($timeout);
        return $resp;
    }

    public function set_client_id($id)
    {
        $this->request2(pack('xa*NNa*', 'REQ', self::SET_CLIENT_ID, strlen($id), $id));
    }
}
