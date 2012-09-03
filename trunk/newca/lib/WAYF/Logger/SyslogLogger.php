<?php
/**
 * JAKOB
 *
 * @category   WAYF
 * @package    JAKOB
 * @subpackage Logger
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  Copyright (c) 2011 Jacob Christiansen, WAYF (http://www.wayf.dk)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @version    $Id: FileLogger.php 30 2011-08-19 10:50:15Z jach@wayf.dk $
 * @link       $URL: https://jakob.googlecode.com/svn/trunk/lib/WAYF/Logger/FileLogger.php $
 */

/**
 * @namespace
 */
namespace WAYF\Logger;

/**
 * @uses WAYF\Logger
 */
use WAYF\Logger;

/**
 * Sys logger
 *
 * Implements the \WAYF\Logger interface to provide an logger that will write 
 * all logging to the OS syslog.
 *
 * @author Jacob Christiansen <jach@wayf.dk>
 */
class SyslogLogger implements Logger
{
    private $_logtosyslog = array(
        JAKOB_ERROR => LOG_ERR,
        JAKOB_WARNING => LOG_WARNING,
        JAKOB_INFO => LOG_INFO,
        JAKOB_DEBUG => LOG_DEBUG,
    );

    public function __construct($config)
    {
        $facility = isset($config['facility']) ? $config['facility'] : LOG_USER);
        $processname = isset($config['processname']) ? $config['processname'] : 'NEWCA');

        openlog($processname, LOG_PID, $facility);
    }

    /**
     * Log message
     *
     * @param  $level   Severity level
     * @param  $message Log message
     * @return void
     */
    public function log($level, $message)
    {
        syslog($this->_logtosyslog[$level], $message);
    }
}
