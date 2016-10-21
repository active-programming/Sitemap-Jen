<?php
/**
 * Web Interface of Jexter
 *
 * Run by command:
 * ```
 * php -S localhost:8008 -t jexter/
 * ```
 * works only for PHP server
 */

define('JEXTER_PORT', 8008);

if (!isset($_SERVER['SERVER_SOFTWARE']) || substr($_SERVER['SERVER_SOFTWARE'], 0, 3) !== 'PHP' ||
    !isset($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    echo 'What are you looking here?';
    exit(1);
}

require __DIR__ . '/web/WebHelper.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'main';

WebHelper::init()->execute($action);
