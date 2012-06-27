<?php

namespace WAYF;

class ExceptionHandler {
    
    private $_errortype = array (
        E_ERROR              => 'EERROR',
        E_WARNING            => 'WARNING',
        E_PARSE              => 'PARSING EROR',
        E_NOTICE             => 'NOTICE',
        E_CORE_ERROR         => 'Core Error',
        E_CORE_WARNING       => 'Core Warning',
        E_COMPILE_ERROR      => 'Compile Error',
        E_COMPILE_WARNING    => 'Compile Warning',
        E_USER_ERROR         => 'User Error',
        E_USER_WARNING       => 'User Warning',
        E_USER_NOTICE        => 'User Notice',
        E_STRICT             => 'Runtime Notice',
        E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
    );
    
    private $_logger = null;

    public function setLogger(\WAYF\Logger $logger)
    {
        $this->_logger = $logger;
    }

    public function handleException($exception)
    {
        if (!is_null($this->_logger)) {
            $trace = $this->_buildTrace($exception);

            if (is_a($exception, 'ErrorException')) {
                $this->_logger->log(JAKOB_ERROR, $this->_errortype[$exception->getSeverity()] . ' - Uncaught exception: `' . get_class($exception) . '` with message: ' . $exception->getMessage());
            } else {
                $this->_logger->log(JAKOB_ERROR, 'Uncaught exception: `' . get_class($exception) . '` with message: ' . $exception->getMessage());
            }
            $this->_logger->log(JAKOB_ERROR, 'Stack trace');
            foreach ($trace AS $line) {
                $this->_logger->log(JAKOB_ERROR, "\t" . $line);
            }
        }

        // Please note that relying on php_sapi_name() is not 100 % solid. 
        // Please see http://dk.php.net/manual/en/function.php-sapi-name.php#89858
        if (php_sapi_name() != 'cli') {
            $data = array(
                'errortitle' => 'Unhandled error',
                'errormsg' => $exception->getMessage(),    
            );
            $template = new \WAYF\Template();
            $template->setTemplate('error')->setData($data)->render(); 
        }
    }

    private function _buildTrace(\Exception $exception)
    {
        $backtrace = array();

        /* Position in the top function on the stack. */
        $pos = $exception->getFile() . ':' . $exception->getLine();

        foreach($exception->getTrace() as $t) {

            $function = $t['function'];
            if(array_key_exists('class', $t)) {
                $function = $t['class'] . '::' . $function;
            }

            $backtrace[] = $pos . ' (' . $function . ')';

            if(array_key_exists('file', $t)) {
                $pos = $t['file'] . ':' . $t['line'];
            } else {
                $pos = '[builtin]';
            }
        }

        $backtrace[] = $pos . ' (N/A)'; 
        return $backtrace;
    }
}
