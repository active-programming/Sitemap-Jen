<?php
/**
 * Main Action
 */

$task = empty($_GET['task']) ? 'display' : $_GET['task'];

// todo some stupid code there

WebHelper::getI()->render('main');


