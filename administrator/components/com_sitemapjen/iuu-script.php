<?php
// инстлляционный скрипт
defined('_JEXEC') or die('Restricted access');

class com_sitemapjenInstallerScript {

	private $ver = '1.2.0';
	private $home = '';
	
	public function __construct( $adapter ){
		$this->home = urlencode( $_SERVER['HTTP_HOST'] );
		$v = explode( '.', $this->ver );
		if( intval($v[1]) < 10 ){  $v[1] = '0'.$v[1];  }
		if( intval($v[2]) < 10 ){  $v[2] = '0'.$v[2];  }
		$this->ver = implode( '', $v );
		$this->key = md5( $this->domain.' msc '.$this->ver );
	}

	function install( $parent ){
		// http://api.kutsevalov.name/install?key=cd63fd11b759117fc45a3101e1b2b712&extension=msc&ver=10000&domain=kutsevalov.name
		@file_get_contents( 'http://api.kutsevalov.name/install?key='.$this->key.'&ver='.$this->ver.'&domain='.$this->domain.'&extension=smj' );
		return true;
	}

	function uninstall( $parent ){
		// http://api.kutsevalov.name/delete?key=cd63fd11b759117fc45a3101e1b2b712&extension=msc&ver=10000&domain=kutsevalov.name
		@file_get_contents( 'http://api.kutsevalov.name/delete?key='.$this->key.'&ver='.$this->ver.'&domain='.$this->domain.'&extension=smj' );
		return true;
	}

	function update( $parent ){ }

	function preflight( $type, $parent ){
		return true;
	}

	function postflight( $type, $parent ){
		return true;
	}
	
}