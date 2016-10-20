<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 * @package    sitemapjen
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class SitemapjenViewHelp extends JViewLegacy {
	
	function display($tpl = null)
    {
        JToolbarHelper::title('Sitemap Jen / Инструкция', 'big-ico');
		parent::display($tpl);
	}

}