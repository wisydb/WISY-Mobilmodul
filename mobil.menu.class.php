<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_MENU_CLASS');

class MOBIL_MENU_CLASS extends WISY_MENU_CLASS
{
	
	function __construct(&$framework, $param)
	{
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::__construct(&$framework, $param);
		
		// constructor
		$this->framework =& $framework;
		$this->prefix = 'mobil.' . $param['prefix'];
	}
};

registerWisyClass('MOBIL_MENU_CLASS');