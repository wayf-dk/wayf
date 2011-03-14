<?php

class sspmod_kvalidate_Logger implements Iterator, Countable
{
    const LEVEL_ERROR = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_NOTICE = 4;
    const LEVEL_SUCCESS = 8;

    protected $_logs = array();

    private $_position = 0;

    public function __construct()
    {
        $this->_position = 0;
    }

    public function logError($msg, $line, $id = 'DOCUMENT')
    {
        $this->_createLog($line, $id, $msg, self::LEVEL_ERROR);
    }

    public function logWarning($msg, $line, $id = 'DOCUMENT')
    {
        $this->_createLog($line, $id, $msg, self::LEVEL_WARNING);
    }

    public function logNotice($msg, $line, $id = 'DOCUMENT')
    {
        $this->_createLog($line, $id, $msg, self::LEVEL_NOTICE);
    }

    public function logSuccess($msg, $line, $id = 'DOCUMENT')
    {
        $this->_createLog($line, $id, $msg, self::LEVEL_SUCCESS);
    }

    public function log($msg, $line, $id = 'DOCUMENT', $level = self::LEVEL_ERROR)
    {
        $this->_createLog($line, $id, $msg, $level);
    }

    private function _createLog($line, $id, $msg, $level)
    {
        $log = new sspmod_kvalidate_Log();
        $log->line = $line;
        $log->id = $id;
        $log->msg = $msg;
        $log->level = $level;

        $this->_logs[] = $log;
        unset($log);
    }

    public function getLogs()
    {
        return $this->_logs;
    }

    // Iterator methods
    public function rewind()
    {
        $this->_position = 0;
    }

    public function current() {
        return $this->_logs[$this->_position];
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        ++$this->_position;
    }

    public function valid() {
        return isset($this->_logs[$this->_position]);
    }

    // Countable method
    public function count()
    {
        return count($this->_logs);
    }
}
