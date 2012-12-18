<?php

namespace WAYF;

class MDFetcher
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function grabFeed()
    {
        // Verify feed can be located
        $headers = get_headers($this->config['feedurl']);
        if (strpos($headers[0],'200')===false) {
            throw new \RuntimeException('Feed URL does not resolve correctly');
        }

        // Grab metadata feed
        $xml = file_get_contents($this->config['feedurl']);

        return $xml;
    }
}
