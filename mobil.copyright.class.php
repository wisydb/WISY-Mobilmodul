<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_COPYRIGHT_CLASS');

// HINWEIS: Falls Sie in Ihren Klassen einen Konstruktor verwenden, vergessen
// Sie nicht, den Konstruktor der Elternklasse ueber parent::__construct() 
// aufzurufen!
class MOBIL_COPYRIGHT_CLASS extends WISY_COPYRIGHT_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		parent::__construct($framework);
	}	
	
	function renderCopyright(&$db, $table, $recordId)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderCopyright(&$db, $table, $recordId);
		
		$copyright = '';
	
		// search by stichwort
		if( $copyright == '' && ($table=='kurse' || $table=='anbieter') )
		{
			$sql = "SELECT notizen x FROM stichwoerter s, {$table}_stichwort a
					 WHERE a.attr_id=s.id AND a.primary_id=$recordId AND s.eigenschaften=2048 AND s.notizen LIKE '%copyright.$table%' ORDER BY a.structure_pos;";
			$db->query($sql);
			while( $db->next_record() )
			{
				$test = explodeSettings(stripslashes($db->f('x')));
				
				if( $test["mobil.copyright.$table"] != '' )
				{
					$copyright = $test["mobil.copyright.$table"];

				} else if( $test["copyright.$table"] != '' )
				{
					$copyright = $test["copyright.$table"];
				}
			}
		}
	
		// search by user id
		if( $copyright == '' )
		{
			$sql = "SELECT settings x FROM user s, $table a
					 WHERE a.user_created=s.id AND a.id=$recordId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$test = explodeSettings(stripslashes($db->f('x')));
				
				if( $test["mobil.copyright.$table"] != '' )
				{
					$copyright = $test["mobil.copyright.$table"];

				} else if( $test["copyright.$table"] != '' )
				{
					$copyright = $test["copyright.$table"];
					break;
				}
			}
		}
	
		// search by group id
		if( $copyright == '' )
		{
			$sql = "SELECT settings x FROM user_grp s, $table a
					 WHERE a.user_grp=s.id AND a.id=$recordId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$test = explodeSettings(stripslashes($db->f('x')));
				
				if( $test["mobil.copyright.$table"] != '' )
				{
					$copyright = $test["mobil.copyright.$table"];

				} else if( $test["copyright.$table"] != '' )
				{
					$copyright = $test["copyright.$table"];
				}
			}
		}
	
		// render
		if( $copyright != '' )
		{
			return '<p id="wisy_copyright_footer">' . $copyright . '</p>';
		}
	}
}
registerWisyClass('MOBIL_COPYRIGHT_CLASS');