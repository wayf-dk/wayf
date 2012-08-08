<?php

$config = array(
    'jakob' => array(
        'class' => 'Jakob',
        'options' => array(
            // Database configuration
            'dsn'      => 'mysql:host=HOST;dbname=DATABASE',
            'username' => 'USERNAME',
            'password' => 'PASSWORD',
            'table'    => 'TABLE',

            // Salt used when calculating jobhash values
            'salt'     => 'SECRETSALT',

            // URL for JAKOB jobs
            'joburl'   => 'JAKOBJOBURL',

            // Consumer
            'consumerkey' => 'KEY',
            'consumersecret' => 'SECRET',
        ),    
    ),
    'targetedid' => array(
        'class' => 'TargetedId',
        'options' => array(
            'salt' => 'SECRETSALT',
            'attribute' => 'eduPersonPrincipalName',
        ),    
    ),
);
