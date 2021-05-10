<?php

use Cake\Core\Configure;

// Load config files
Configure::load('PayPal.app_paypal');
if (file_exists(ROOT . DS . 'config' . DS . 'app_paypal.php')) {
    Configure::load('app_paypal');
}
