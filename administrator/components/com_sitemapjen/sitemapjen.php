<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 * @package    sitemapjen
 * @joomlaVer 3.2
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

// создаем объект контроллера и выполняем действие соответствующее запросу
$controller = JControllerLegacy::getInstance( 'Sitemapjen' );
 $input = JFactory::getApplication()->input;
$controller->execute( $input->getCmd('task') );
$controller->redirect();