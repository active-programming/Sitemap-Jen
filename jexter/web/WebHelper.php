<?php

define('JWH_DIR', __DIR__ . '/');

/**
 * WebHelper
 */
class WebHelper
{

    private static $instance = null;

    protected function __construct()
    {
    }

    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getI()
    {
        return self::init();
    }

    /**
     * @param string $action
     */
    public function execute($action = 'main')
    {
        $actionFile = JWH_DIR . 'actions/' . $action . '.php';
        if (is_file($actionFile)) {
            include_once($actionFile);
        } else {
            echo 'ACTION "' . $action . '" NOT FOUND!';
            exit(1);
        }
    }

    /**
     * @param string $view
     */
    public function render($view = 'main')
    {
        $viewFile = JWH_DIR . 'views/' . $view . '.php';
        if (is_file($viewFile)) {
            include_once($viewFile);
        } else {
            echo 'VIEW "' . $view . '" NOT FOUND!';
            exit(1);
        }
    }

}