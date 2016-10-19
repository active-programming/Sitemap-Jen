<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 */

$www = (substr($_SERVER['SERVER_NAME'], 0, 4) != 'www.') ? false : true; // for filtering of www aliases
define('WWW_ALIAS', $www);
$scheme = (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') ? 'http://' : 'https://';
define('HTTP_SCHEME', $scheme);
define('WEB_DOMAIN', $scheme . $_SERVER['SERVER_NAME']);
define('IS_CURL', function_exists('curl_init'));
define('WEB_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/');

session_start();

require_once(__DIR__ . '/SPDO.php');
require_once(__DIR__ . '/functions.php');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'cron'; // по умолчанию считаем, что запущено кроном (из консоли)

if ($mode == 'cron') { // проверяем возможную активность ajax-запросов, при вызове скрипта через cron
    $lastTime = 0;
    is_file(__DIR__ . '/.last_time') and require(__DIR__ . '/.last_time'); // -> $lastTime
    if ($lastTime > 0 && (time() - $lastTime) < 60) exit; // не чаще 1 раза в минуту в режиме CRON
}

include $_SERVER['DOCUMENT_ROOT'] . '/configuration.php';
if (SPDO::connect(new JConfig()) !== true) {
    exit(json_encode(['error' => 100, 'logs' => ['Ошибка подключения к БД: ' . SPDO::getError()]]));
}

SPDO::loadSettings( ($action=='init') );
// проверяем список исключаемых адресов, если он пуст, сканируем robots.txt на наличие disallow
if (trim(SPDO::getSetting('ignore_list')) == '') {
    SPDO::setSetting('ignore_list', parseRobotstxt());
}

// Запуск
if ($action == 'init') {
    echo doInit();
} elseif ($action == 'stop') {
    echo doStop();
} else {
    // action "scan" or other
    if (SPDO::getSetting('task_status') == 'in_work') {
        if (SPDO::getSetting('task_action') == 'scan') {
            // сканируем сайт
            echo doScan($mode);
        } elseif (SPDO::getSetting('task_action') == 'generate') {
            // генерируем sitemap на основе самых обновленных страниц
            // читаем ссылки из базы, с сортировкой по дате
            echo doGenerate($mode);
        } else {
            if ($mode == 'ajax') {
                echo json_encode(['error' => 10, 'logs' => ['Ошибка запроса: неизвестная команда']]);
            } else {
                saveLog(['Ошибка запроса: неизвестная команда']);
            }
        }
    } else {
        // нет активной задачи
        if ($mode == 'ajax') {
            echo json_encode(['error' => 0, 'logs' => []]);
        }
    }
}

SPDO::close();