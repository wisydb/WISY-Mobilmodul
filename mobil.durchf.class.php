<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_DURCHF_CLASS');

// HINWEIS: Falls Sie in Ihren Klassen einen Konstruktor verwenden, vergessen
// Sie nicht, den Konstruktor der Elternklasse ueber parent::__construct() 
// aufzurufen!
class MOBIL_DURCHF_CLASS extends WISY_DURCHF_CLASS
{

	function formatDurchfuehrung(&$db, $kursId, $durchfuehrungId, $details = 0, $anbieterId = 0, $showAllDurchf = 1, $addText='')
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::formatDurchfuehrung($db, $kursId, $durchfuehrungId, $details, $anbieterId, $showAllDurchf, $addText);
		
		global $wisyPortalSpalten;
		
		$time = strftime("%Y-%m-%d 00:00:00");
		
		// get some tags
		if( $this->buFuTagIdsStr == '' )
		{
			$db->query("SELECT tag_id, tag_name FROM x_tags WHERE tag_name='Bildungsurlaub' OR tag_name='Fernunterricht';");
			while($db->next_record()) { 
				$this->buFuTagIdsStr .= ($this->buFuTagIdsStr==''? '' : ', ') . intval($db->f('tag_id'));
				$this->buFuTagIdsArr[ intval($db->f('tag_id')) ] = stripslashes($db->f('tag_name'));
			}
		}
		
		$fu = 0;
		$bu = 0;
		if( $this->buFuTagIdsStr != '' )
		{
			$db->query("SELECT tag_id FROM x_kurse_tags WHERE kurs_id=$kursId AND tag_id IN ($this->buFuTagIdsStr);");
			while( $db->next_record() ) {
				switch( $this->buFuTagIdsArr[ intval($db->f('tag_id')) ] ) {
					case 'Bildungsurlaub': $bu = 1; break;
					case 'Fernunterricht': $fu = 1; break;
				}
			}
		}
	
		// load data
		$db->query("SELECT nr, dauer, bemerkungen, preis, teilnehmer, kurstage, sonderpreis, sonderpreistage, plz, strasse, 
						   land, stadtteil, preishinweise, beginn, beginnoptionen, ende, ort, tagescode, stunden, zeit_von, zeit_bis, bg_nummer, bg_nummer_count
					  FROM durchfuehrung 
					 WHERE id=$durchfuehrungId");
	    if( $db->next_record() )
	    {
	    	$record  = $db->Record;
	    }
	    else
	    {
	    	$record = array('preis' => -1); // alle andere felder sind mit "leer" gut bedient
	    }
	    	
		// TERMIN
		$terminAttr = $details? '' : ' nowrap="nowrap"';
		$beginnsql		= $record['beginn'];
		$beginn			= $this->framework->formatDatum($beginnsql);
		$beginnoptionen = $this->formatBeginnoptionen($record['beginnoptionen']);
		$ende			= $details? $this->framework->formatDatum($record['ende']) : '';
		$zeit_von		= $details? $record['zeit_von'] : ''; if( $zeit_von=='00:00' ) $zeit_von = '';
		$zeit_bis		= $details? $record['zeit_bis'] : ''; if( $zeit_bis=='00:00' ) $zeit_bis = '';
		$bg_nummer = $db -> f('bg_nummer');
		$bg_nummer_count = $db -> f('bg_nummer_count');
		
		if (($wisyPortalSpalten & 2) > 0)
		{			
			if( $beginn )
			{
				echo '<span class="wisy_beginn">';
		        echo ($ende && $beginn!=$ende)? "$beginn - $ende" : $beginn;
				echo '</span>';
				if($details) echo '<br />';
				if( $beginnoptionen ) { echo ' <span class="wisy_beginnoptionen">(' . $beginnoptionen . ')</span>'; }
			}
			else if( $beginnoptionen )
			{
				echo ' <span class="wisy_beginnoptionen">' . $beginnoptionen . '</span>';
			}
				
			if( $zeit_von && $zeit_bis ) {
				echo ' <span class="wisy_zeitvonbis">' . $zeit_von . ' - ' . $zeit_bis . '</span>';
			}
			else if( $zeit_von ) {
				echo ' <span class="wisy_zeitvon">' . $zeit_von . '</span>';
			}
		}
		
		// ORT
		if (($wisyPortalSpalten & 32) > 0)
		{
			$strasse	= stripslashes($record['strasse']);
			$plz		= stripslashes($record['plz']);
			$ort		= stripslashes($record['ort']); // hier wird noch der Stadtteil angehängt
			$stadt		= $ort;
			$stadtteil	= stripslashes($record['stadtteil']);
			$land		= stripslashes($record['land']);
			
			// Link zu Ort auf Google Maps Extern auf Detailseite anzeigen, falls
			// 	mobil.kursdetails.durchf.maplink = 1
			if(trim($this->framework->iniRead('mobil.kursdetails.durchf.maplink')) == 1 && $ort && $strasse) {
				$maps_ort = $strasse;
				if($plz) $maps_ort .= ', ' . $plz;
				if($ort) $maps_ort .= ', ' . $ort;
				if($land) {
					$maps_ort .= ', ' . $land;
				} else {
					$maps_ort .= ', Deutschland';
				}
				$mapslink_html = ' <a class="wisy_ort_maplink" target="_blank" href="http://maps.google.com/maps?q=' . urlencode($maps_ort) .' ">Adresse in Google Maps</a>';
			}
			
			if( $ort && $stadtteil ) {
				if( strpos($ort, $stadtteil)===false ) {
					$ort = '<strong>'. htmlentities($ort) . '</strong>-' . htmlentities($stadtteil);
				}
				else {
					$ort = '<strong>'. htmlentities($ort) .'</strong>';
				}
			}
			else if( $ort ) {
				$ort = '<strong>'. htmlentities($ort) .'</strong>';
			}
			else if( $stadtteil ) {
				$ort = htmlentities($stadtteil);
				$stadt = $stadtteil;
			}
			if($strasse) {
				$strasse_html = '<span class="wisy_strasse">' . $strasse . '</span>';
			}
			
			if( is_object($this->framework->map) )
			{
				$termin  = $ende? "$beginn-$ende" : $beginn;
				if( $termin == '' )
				{
					$termin = $beginnoptionen;
				}
				
				if( $zeit_von && $zeit_bis )
				{
					$termin .= $termin? ', ' : '';
					$termin .= "$zeit_von-$zeit_bis&nbsp;Uhr"; 
				}
				else if( $zeit_von )
				{
					$termin .= $termin? ', ' : '';
					$termin .= "$zeit_von&nbsp;Uhr"; 
				}


				$this->framework->map->AddPoint(htmlspecialchars($strasse), htmlspecialchars($plz), htmlspecialchars($stadt), htmlspecialchars($land),
					$termin);
			}
			// In Kursdetails ORT erst nach KURSTAGE und DAUER ausgeben
			if($details) {
				$ort_output = $ort? '<br /><span class="wisy_ort">' . $ort . '</span>' : '';
				if($strasse_html) $ort_output .= ', ' . $strasse_html;
				if($mapslink_html) $ort_output .= $mapslink_html;
			} else {
				echo $ort? ', <span class="wisy_ort">' . $ort . '</span>' : '';
			}
		}
		
		// KURSTAGE & TAGESCODE
		if (($wisyPortalSpalten & 8) > 0)
		{
			$tagescodeAttr = $details? '' : ' align="center"';
	
				$kurstage = '';	
				if( $details && $this->framework->iniRead('details.kurstage', 1)==1 ) {			
					$kurstage = $this->formatKurstage(intval($record['kurstage']));
				}
				
				echo '<span class="wisy_kurstage"> ' . $this->formatTagescode($bu? 'bu' : ($fu? 6: $record['tagescode']), $details, $kurstage) . '</span>';
		}
		
		// DAUER
		if (($wisyPortalSpalten & 4) > 0)
		{
			echo $this->formatDauer($record['dauer'], $record['stunden'], '<span class="wisy_dauer"> %1</span>', '<span class="wisy_stunden"> (%1)</span>');
		}
		
		// TEILNEHMER
		if($details && $record['teilnehmer'])
		{
			echo '<span class="wisy_maxteilnehmer"> max. ' . intval($record['teilnehmer']) . ' Teilnehmer</span>';
		}
		
		// ORT (auf Kursdetailseite)
		if($details) echo $ort_output;
		
		// PREIS
		if (($wisyPortalSpalten & 16) > 0)
		{
			$preisAttr = ($details && $record['preishinweise']!='')? ' align=\"right\"' : ' nowrap="nowrap"';
			$temp = $this->formatPreis($record['preis'], $record['sonderpreis'], $record['sonderpreistage'], $record['beginn'], $details? stripslashes($record['preishinweise']) : '');
			echo $details ? '<br />' : '';
			if($temp != '') echo '<span class="wisy_preis"> ' . $this->shy($temp) . '</span>';
		}
		
		// NR
		if (($wisyPortalSpalten & 64) > 0)
		{
			$nr = stripslashes($record['nr']);
			echo $nr? '<span class="wisy_nr"> ' . htmlentities($nr) . '</span>' : '';
		}
		
		// BEMERKUNGEN
		if($details)
		{
			$has_bemerkungen = trim($record['bemerkungen'])? true : false;
			if( $has_bemerkungen ) {
				$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this);
				echo '<span class="wisy_bemerkungen">' . $wiki2html->run(stripslashes($record['bemerkungen'])) . '</span>';
			}
		}
	}
	
	function formatPreis($preis, $sonderpreis, $sonderpreistage, $beginn, $preishinweise, $html = 1)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::formatPreis($preis, $sonderpreis, $sonderpreistage, $beginn, $preishinweise, $html);
		
		// Preis formatieren
		if( $preis == -1 ) 
		{
			$ret = '';
		}
		else if( $preis == 0 )
		{
			$ret = 'kostenlos';
		}
		else 
		{
			if( $html ) {
				$ret = "$preis&nbsp;&euro;";
			}
			else {
				$ret = "$preis&nbsp;EUR";
			}
			
			if( $preis>0
			 && $sonderpreis>0 
			 && $sonderpreis<$preis )
			{
				$beginn = explode(' ', str_replace('-', ' ', $beginn));
				$beginn = mktime(0, 0, 0, $beginn[1], $beginn[2], $beginn[0]) - $sonderpreistage*86400;
				if( time() >= $beginn ) {
					if( $html ) {
						$ret = "<strike>$ret</strike><br /><span class=\"red\">" . $this->formatPreis($sonderpreis, -1, 0, 0, '', $html) . '</span>';
					}
					else {
						$ret = $this->formatPreis($sonderpreis, -1, 0, 0, '', $html) . " (bisheriger Preis: $ret)";
					}
				}
			}
		}	
		if( $preishinweise )
		{
			if( $html ) {
				$ret .= ' <span class="wisy_preishinweise">' . htmlentities($preishinweise) . '</span>';
			}
			else {
				$ret .= " ($preishinweise)";
			}
		}	
		return $ret;
	}
	
	
	function formatTagescode($tagescode /*id or 'bu'*/, $details, $addText = '')
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::formatTagescode($tagescode, $details, $addText);
		
		/* -- todo: get rid of the icons ...
		
		return $ret;
		*/
		$ret = '';
		$icons = $this->framework->iniRead('img.icons', 'skww');
		if( !@file_exists("$icons/tc1.gif") )
		{
			// use the new method (text only)
			$info = array(
				1	=>	array('Ganzt.', 'Ganzt&auml;gig'),
				2	=>	array('Vorm.',  'Vormittags'),
				3	=>	array('Nachm.', 'Nachmittags'),
				4	=>	array('Abends', 'Abends'),
				5	=>	array('WE',     'Wochenende'),
				6	=>	array('FU',     'Fernunterricht'),
				'bu'=>	array('BU',     'Bildungsurlaub'),
			);
			if( is_array($info[$tagescode]) ) {
				if( $details ) {
					$ret = '<span class="tagescode'.$tagescode.'">'.$this->shy($info[$tagescode][1]).'</span>';
					if( $addText ) $ret .= ', ' . $addText;
				}	
				else {
					$ret = '<span class="tagescode'.$tagescode.'" title="'.$info[$tagescode][1].'">'.$info[$tagescode][0].'</span>';
				}
			}
			return $ret;
		}
		else
		{
			// use the old method (icon)
			if( $tagescode )
			{
				global $codes_tagescode_array;
				if( !is_array($codes_tagescode_array) ) 
				{	
					require_once('admin/config/codes.inc.php');
					global $codes_tagescode;				
					$codes_tagescode_array = array();
					$temp = explode('###', $codes_tagescode);
					for( $i = 0; $i < sizeof($temp); $i+=2 ) {
						$codes_tagescode_array[$temp[$i]] = $temp[$i+1];
					}
					$codes_tagescode_array['bu'] = 'Bildungsurlaub';
				}
				
				$title = $codes_tagescode_array[$tagescode];
				
				if( $details ) {
					$ret = "<img src=\"{$icons}/tc{$tagescode}.gif\" width=\"15\" height=\"12\" border=\"0\" alt=\"\" title=\"\" /><small> $addText $title</small>";
				}
				else {
					$ret = "<img src=\"{$icons}/tc{$tagescode}.gif\" width=\"15\" height=\"12\" border=\"0\" alt=\"$title\" title=\"$title\" />";
				}
			}
			return $this->shy($ret);
		}
	}
	
	
	function formatDauer($dauer, $stunden, $maskdauer = '%1', $maskstunden = '(%1)') // return as HTML
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::formatDauer($dauer, $stunden, $maskdauer, $maskstunden);
		
		// Dauer formatieren
		global $codes_dauer_array;
		if( !is_array($codes_dauer_array) ) 
		{	
			require_once('admin/config/codes.inc.php');
			global $codes_dauer;
			$codes_dauer_array = array();
			$temp = explode('###', $codes_dauer);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_dauer_array[$temp[$i]] = $temp[$i+1];
			}
		}
	
		if( $dauer <= 0 ) {
			$dauer = '';
		}
		else if( $codes_dauer_array[$dauer] ) {
			$dauer = str_replace(' ', '&nbsp;', $codes_dauer_array[$dauer]);
		}
		else {
			$dauer = "$dauer&nbsp;Tage";
		}
		
		// stunden
		if( $stunden > 0 ) {
			$stunden = "$stunden&nbsp;Std.";
		}	
		else {
			$stunden = '';
		}
		
		// done
		$ret = '';
 		if( $dauer != '' ) {
			$ret .= str_replace('%1', $dauer, $maskdauer);
		}
		if( $stunden != '' ) {
			$ret .= str_replace('%1', $stunden, $maskstunden);
		}
		return $ret;
	}
}
registerWisyClass('MOBIL_DURCHF_CLASS');