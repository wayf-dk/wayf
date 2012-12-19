<?php
namespace WAYF;

interface EventLoggeInterface {

    public function __construct($config);

    public function log(\WAYF\Event $event);
}
