<?php

$config = array(
    'jakob' => array(
        'class' => 'Jakob',
        'options' => array(
            // Database configuration
            'dsn'      => 'mysql:host=localhost;dbname=jakob',
            'username' => 'jakobca',
            'password' => 'diller',
            'table'    => 'jakob__configuration',

            // Salt used when calculating jobhash values
            'salt'     => 'pezo340fkvd3afnywz3ab2fuwf5enj8h',

            // URL for JAKOB jobs
            'joburl'   => 'http://jakob.test.wayf.dk/job/',

            // Consumer
            'consumerkey' => 'wayf',
            'consumersecret' => '09984b3e4aa39d21f68b3d751fb4fa5b93a6ddb9',
        ),    
    ),
    'targetedid' => array(
        'class' => 'TargetedId',
        'options' => array(
            'salt' => 'jd2s6mkitweyw3fbb9hovqglxemp35es',
            'attribute' => 'eduPersonPrincipalName',
        ),    
    ),
);
