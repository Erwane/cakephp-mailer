<?php

use Cake\Core\Configure;
use Cake\Mailer\Email as CakeEmail;
use CakePhpMailer\Mailer\Email;

$config = [
    'EmailTransport' => ['default' => Configure::read('EmailTransport')],
    'Email' => ['default' => Configure::read('Email')],
];
if (empty($config['Email']['default'])) {
    $config = [
        'EmailTransport' => ['default' => CakeEmail::getConfigTransport('default')],
        'Email' => ['default' => CakeEmail::getConfig('default')],
    ];
}

Email::setConfig($config['Email']);
Email::setConfigTransport($config['EmailTransport']);
