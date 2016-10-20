<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 * @package    sitemapjen
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
jimport('joomla.html.html');

class SitemapjenViewOptions extends JViewLegacy {
	
	function display($tpl = null)
    {
        JToolbarHelper::title(JText::_( 'Sitemap Jen / Настройки' ), 'big-ico');
		JToolbarHelper::save('save_options');
		$options = $this->get('Options');
		$this->options = $options;
		$this->token = JHtml::_('form.token');
		parent::display($tpl);
	}

}