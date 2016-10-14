<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Editors-xtd.pagebreak
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Editor MCS Insert button
 */
class PlgButtonMcsinsert extends JPlugin
{

	protected $autoloadLanguage = true;

	/**
	 * Display the button
	 * @param   string  $name  The name of the button to add
	 * @return array A two element array of (imageName, textToInsert)
	 */
	public function onDisplay($name)
	{
		$button          = new JObject;
		$button->modal   = true;
		$button->class   = 'btn';
		$button->link    = 'index.php?option=com_mycityselector&amp;view=popup&amp;layout=popup&amp;tmpl=popup';
		$button->text    = JText::_('MCS');
		$button->name    = 'MCS Insert';
		$button->options = "{handler: 'iframe', size: {x: 600, y: 500}}";
		return $button;
	}

}
