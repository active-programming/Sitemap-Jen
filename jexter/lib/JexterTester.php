<?php
/*
 * JEXTER
 * Joomla extensions creator
 * @author Konstantin Kutsevalov (AdamasAntares) <konstantin@kutsevalov.name>
 * @version 1.0.0 alpha
 * @license GPL v3 (license.txt)
 */

namespace adamasantares\jexter;

// TODO этот класс будет делать тестирование расширений
// проверка корректности установки всех файлов и создания таблиц
// и тд и тп

if (!defined('JEXTER_DIR')) {
    define('JEXTER_DIR', realpath(__DIR__ . '/../'));
}


/**
 * Class JexterTester
 */
class JexterTester {

    private static $lastError = null;

    private static $lastErrorCode = null;


    /**
     * Build extension's tester
     * @param $args <p>
     * should have 1 key:<br/>
     *   "config" - local path to project config file ("config/project.json")
     * </p>
     * @return array Array
     */
    public static function run($args)
    {
        // todo :)

        return [];
    }

} 
