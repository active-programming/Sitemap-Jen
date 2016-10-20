<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 * @package    sitemapjen
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view' );
jimport( 'joomla.html.html' );

class SitemapjenViewDefault extends JViewLegacy {
	// выводит список просканированных ссылок
	
	function display($tpl = null)
    {
		// заголовок
        JToolbarHelper::title(JText::_('Sitemap Jen'), 'big-ico');
		$links = $this->get('Links');
		$pagination = $this->get('Pagination');
		// передаем данные из модели в шаблон
		$this->links = $links;
		$this->pagination = $pagination;
		if (count($links) > 0) {
			// кнопки операций
            JToolbarHelper::custom('to_ignore', 'unpublish', '123', 'В исключение', true, false);
            JToolbarHelper::custom('clear_links', 'delete', null, 'Удалить все', false, false);
		}
		$this->token = JHtml::_('form.token');
		parent::display($tpl);
	}
 
}