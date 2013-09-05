<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_ANBIETER_RENDERER_CLASS');

// HINWEIS: Falls Sie in Ihren Klassen einen Konstruktor verwenden, vergessen
// Sie nicht, den Konstruktor der Elternklasse ueber parent::__construct() 
// aufzurufen!
class MOBIL_ANBIETER_RENDERER_CLASS extends WISY_ANBIETER_RENDERER_CLASS
{
	
	var $kursId;
	
	function renderDetailsMobile(&$db, $id, $kursId)
	{
		$this->kursId = $kursId;
		$this->renderDetails($db, $id);
	}
	
	function renderDetails(&$db, $id)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderDetails($db, $id);
		
		global $wisyPortal;
		global $wisyPortalEinstellungen;
		
		// load anbieter
		$db->query("SELECT * FROM anbieter WHERE id=$id");
		if( !$db->next_record() || $db->f('freigeschaltet')!=1 ) {
			echo 'Dieser Anbieterdatensatz ist nicht freigeschaltet.'; // it exists, however, we've checked this [here]
			return;
		}
		
		$din_nr			= stripslashes($db->f('din_nr'));
		$suchname		= stripslashes($db->f('suchname'));
		$postname		= stripslashes($db->f('postname'));
		$strasse		= stripslashes($db->f('strasse'));
		$plz			= stripslashes($db->f('plz'));
		$ort			= stripslashes($db->f('ort'));
		$stadtteil		= stripslashes($db->f('stadtteil'));
		$land			= stripslashes($db->f('land'));
		$anspr_tel		= stripslashes($db->f('anspr_tel'));
		$anspr_fax		= stripslashes($db->f('anspr_fax'));
		$anspr_name		= stripslashes($db->f('anspr_name'));
		$anspr_email	= stripslashes($db->f('anspr_email'));
		$anspr_zeit		= stripslashes($db->f('anspr_zeit'));
		
		$ob = new G_BLOB_CLASS($db->fs('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;

		$firmenportraet	= trim(stripslashes($db->f('firmenportraet')));
		$date_modified	= stripslashes($db->f('date_modified'));
		$homepage		= stripslashes($db->f('homepage'));

		if( $homepage ) {
			if( substr($homepage, 0, 5) != 'http:'
			 && substr($homepage, 0, 6) != 'https:' ) {
			 	$homepage = 'http:/'.'/'.$homepage;
			}
		}

		
		$stichwoerter = $this->framework->loadStichwoerter($db, 'anbieter', $id);
		
		$seals = $this->framework->getSeals($db, array('anbieterId'=>$id));
	
		// prepare contact link
		if( $anspr_email )
		{
			$anspr_mail_link = "<a href=\"" . $this->createMailtoLink($anspr_email) . "\"><i>" .htmlentities($anspr_email). '</i></a>';
		}		
		
		flush();
					
		echo '<div id="wisy_anbieterdetails" class="automin_nojs hide_after">';
		echo '<div class="wisy_vcard">';
			echo '<div class="wisy_vcardcontent">';
			echo $this->renderCard($db, $id, $this->kursId, array('logo'=>false));
			echo '</div>';
		echo '</div><!-- /#wisy_vcard -->';
		echo '</div><!-- /#wisy_anbieterdetails -->';
		
		$qsuchname = strtr($suchname, ':,', '  ');
		while( strpos($qsuchname, '  ')!==false ) $qsuchname = str_replace('  ', ' ', $qsuchname);
		echo '<a id="wisy_alleangebote" href="' .$this->framework->getUrl('search', array('q'=>$qsuchname)). '">Zeige alle Angebote</a>';			
		
		echo '<div id="wisy_anbietersummary">';

			echo '<h1>' . htmlentities($suchname) . '</h1>';
			
			if( $firmenportraet != '' ) 
			{
				$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
				echo $wiki2html->run($firmenportraet);
			}
			
		echo '</div><!-- /#wisy_anbietersummary -->';

	}
	
	function renderCard(&$db, $anbieterId, $kursId, $param)
	{
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderCard($db, $anbieterId, $kursId, $param);
		
		global $wisyPortal;
		global $wisyPortalEinstellungen;
		
		// load anbieter
		$db->query("SELECT * FROM anbieter WHERE id=$anbieterId");
		if( !$db->next_record() || $db->f('freigeschaltet')!=1 ) {
			return 'Dieser Anbieterdatensatz existiert nicht oder nicht mehr oder ist nicht freigeschaltet.';
		}
		
		$kursId			= intval($kursId);
		$suchname		= $db->fs('suchname');
		$postname		= $db->fs('postname');
		$strasse		= $db->fs('strasse');
		$plz			= $db->fs('plz');
		$ort			= $db->fs('ort');
		$stadtteil		= $db->fs('stadtteil');
		$land			= $db->fs('land');
		$anspr_tel		= $db->fs('anspr_tel');
		$anspr_fax		= $db->fs('anspr_fax');
		$anspr_name		= $db->fs('anspr_name');
		$anspr_email	= $db->fs('anspr_email');
		$anspr_zeit		= $db->fs('anspr_zeit');
		$homepage		= $db->fs('homepage');
		
		$ob = new G_BLOB_CLASS($db->fs('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;
		
		// do what to do ...
		$ret  = '';
		$ret .= '<h3>'. htmlentities($postname? $postname : $suchname) . '</h3>';

		if( $strasse )
			$ret .= htmlentities($strasse);

		if( $plz || $ort )
			$ret .= '<br />' . htmlentities($plz) . ' ' . htmlentities($ort);

		if( $stadtteil ) {
			$ret .= ($plz||$ort)? '-' : '<br />';
			$ret .= htmlentities($stadtteil);
		}

		if( $land ) {
			$ret .= ($plz||$ort||$stadtteil)? ', ' : '<br />';
			$ret .= htmlentities($land);
		}

		if( $anspr_tel )
			$ret .= '<br />Tel:&nbsp;'.htmlentities($anspr_tel);

		if( $anspr_name || $anspr_zeit )
		{
			$ret .= '<br /><small>';
				if( $anspr_name )
					$ret .= 'Kontakt: ' . htmlentities($anspr_name);
				if( $anspr_zeit )
				{
					$ret .= $anspr_name? ', ' : '';
					$ret .= htmlentities($anspr_zeit);
				}
			$ret .= '</small>';
		}
		
		/* logo */
		if( $param['logo'] )
		{
			$ret .= '<br />';
			
			if( $param['logoLinkToAnbieter'] )
				$ret .= '<a href="'.$this->framework->getUrl('a', array('id'=>$anbieterId)).'">';
			
			if( $logo_w && $logo_h && $logo_name != '' )
			{
				$this->fit_to_rect($logo_w, $logo_h, 128, 64, $logo_w, $logo_h);
				$ret .= "<img vspace=\"5\" src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieterId/".urlencode($logo_name)."\" width=\"$logo_w\" height=\"$logo_h\" border=\"0\" alt=\"Anbieter Logo\" title=\"\" />";
				
				if( $param['logoLinkToAnbieter'] ) 
					$ret .= '<span class="noprint"><br /></span>';
			}
			
			if( $param['logoLinkToAnbieter'] )
				$ret .= '<i class="noprint">Zeige Anbieterdetails</i></a>';
		}
		
		/* Aktions-Buttons */
		$ret .= '<div class="wisy_actionbuttons clearfix">';
		
		// Anrufen
		if( $anspr_tel )
		{
			$fon = str_replace(array('/', '(', ')', '#'), '', $anspr_tel); // Klammern und Gatter usw. entfernen
			$fon = str_replace(' ', '-', $fon); // Leerzeichen in - umwandeln
			$count = 1;
			while($count) $fon = str_replace('--', '-', $fon, $count); // Doppelte -- entfernen
			$ret .= '<a class="wisy_call" title="Anbieter anrufen" href="tel:'. htmlentities($fon) .'">fon</a>';
		}
		
		// Homepage
		if( $homepage )
		{
			if( substr($homepage, 0, 5) != 'http:' && substr($homepage, 0, 6) != 'https:' ) {
			 	$homepage = 'http:/'.'/'.$homepage;
			}
			$ret .= '<a class="wisy_homepage" title="Anbieter-Website aufrufen" href="'. $homepage .'" target="_blank">www</a>';
		}
		
		// Mailen
		if( $anspr_email )
		{ 
			$ret .= '<a class="wisy_email" title="E-Mail an Anbieter senden" href="'. $this->createMailtoLink($anspr_email, $kursId) . '">@</a>';
		}
		
		$ret .= '</div>';
		
		return $ret;
	}
	
};
registerWisyClass('MOBIL_ANBIETER_RENDERER_CLASS');