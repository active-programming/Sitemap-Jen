<?php
// инстлляционный скрипт
defined('_JEXEC') or die('Restricted access');

class Com_sitemapjenInstallerScript {

	public function __construct($adapter) {	}

	function install($parent) {
		return true;
	}

	function uninstall($parent) {
		return true;
	}

	function update($parent) {
        return true;
    }

	function preflight($type, $parent) {
		return true;
	}

	function postflight($type, $parent) {
		return true;
	}
	
}