<?php
namespace WAYF;

class ErrorHandler {
    public function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
