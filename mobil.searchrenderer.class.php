<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_SEARCH_RENDERER_CLASS');

// HINWEIS: Falls Sie in Ihren Klassen einen Konstruktor verwenden, vergessen
// Sie nicht, den Konstruktor der Elternklasse ueber parent::__construct() 
// aufzurufen!
class MOBIL_SEARCH_RENDERER_CLASS extends WISY_SEARCH_RENDERER_CLASS
{

	function renderTagliste($queryString)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderTagliste($queryString);
		
		$tagsuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);
		$suggestions = $tagsuggestor->suggestTags($queryString);

		if( sizeof($suggestions) ) 
		{
			echo '<div class="wisy_suggestions">';
			echo '<h3>Gefundene Rechercheziele - verfeinern Sie Ihren Suchauftrag:</h3>';
			echo '<ul>';
				for( $i = 0; $i < sizeof($suggestions); $i++ )
				{
					echo '<li>' . $this->formatItem($suggestions[$i]['tag'], $suggestions[$i]['tag_descr'], $suggestions[$i]['tag_type'], intval($suggestions[$i]['tag_help']), intval($suggestions[$i]['tag_freq'])) . '</li>';
				}
			echo '</ul>';
			echo '</div>';
		}
		else
		{
			echo 'Keine Treffer.';
		}
	}

	function renderKursliste(&$searcher, $queryString, $offset)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderKursliste($searcher, $queryString, $offset);
		
		global $wisyPortalSpalten;

	
		$validOrders = array('a', 'ad', 't', 'td', 'b', 'bd', 'd', 'dd', 'p', 'pd', 'o', 'od', 'creat', 'creatd', 'rand');
		$orderBy = stripslashes($_GET['order']);
		if(strlen($orderBy) == 1 && stripslashes($_GET['order_dn'])) $orderBy .= 'd'; // Fallback falls Javascript nicht aktiv ist
		if( !in_array($orderBy, $validOrders) ) $orderBy = 'b';
		
		$info = $searcher->getInfo();
		if( $info['changed_query'] || sizeof($info['suggestions']) )
		{
			echo '<div class="wisy_suggestions">';
				if( $info['changed_query'] )
				{
					echo '<b>Hinweis:</b> Der Suchauftrag wurde abgeändert in <i><a href="'.$this->framework->getUrl('search', array('q'=>$info['changed_query'])).'">'.htmlspecialchars($info['changed_query']).'</a></i>';
					if( sizeof($info['suggestions']) ) 
						echo ' &ndash; ';
				}
				
				if( sizeof($info['suggestions']) ) 
				{
					echo '<h3>Gefundene Rechercheziele - verfeinern Sie Ihren Suchauftrag:</h3>';
					echo '<ul>';
						for( $i = 0; $i < sizeof($info['suggestions']); $i++ )
						{
							echo '<li>' . $this->formatItem($info['suggestions'][$i]['tag'], $info['suggestions'][$i]['tag_descr'], $info['suggestions'][$i]['tag_type'], intval($info['suggestions'][$i]['tag_help']), intval($suggestions[$i]['tag_freq'])) . '</li>';
						}
					echo '</ul>';
				}
			echo '</div>';
		}

		$sqlCount = $searcher->getKurseCount();
		if( $sqlCount )
		{
			$db = new DB_Admin();
			
			// create get prev / next URLs
			$prevurl = $offset==0? '' : $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset-$this->rows));
			$nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset+$this->rows)) : '';
			if( $prevurl || $nexturl )
			{	
				$param = array('q'=>$queryString);
				if( $orderBy != 'b' ) $param['order'] = $orderBy;
				$param['offset'] = '';
				$pagesel = $this->pageSel($this->framework->getUrl('search', $param), $this->rows, $offset, $sqlCount);
			}
			else
			{
				$pagesel = '';
			}

			// render head
			echo '<div class="wisy_suchergebnisse">';
				if( $queryString == '' )
					echo '<b>Aktuelle Angebote:</b>';
				else
					echo $sqlCount==1? "<b>1 Angebot</b> zum Suchauftrag" : "<b>$sqlCount Angebote</b> zum Suchauftrag";
					if( $queryString ) echo ' <span class="wisy_suchbegriff">' . htmlspecialchars(trim($queryString, ', ')) . '</span>';
				
			if( $info['lat'] && $info['lng'] ) {
				$this->hasDistanceColumn = true;
				$this->baseLat = $info['lat'];
				$this->baseLng = $info['lng'];
			}
				
			// Sortieroptionen
			$order_options = array(
				'b' => 'Termin',
				't' => 'Angebot',
				'a' => 'Anbieter',
				'd' => 'Dauer',
				'p' => 'Preis',
				'o' => 'Ort'
			);
			// TODO: if($this->hasDistanceColumn) $order_options['e'] = 'Entfernung';
			
			$current_order = $this->framework->getParam('order', '');

			$current_orderby = 'b';
			if(strlen($current_order) > 0)  $current_orderby = substr($current_order, 0, 1);

			$current_orderdir = '';
			if(strlen($current_order) > 1) $current_orderdir = substr($current_order, 1, 1);
						
			echo '<div class="wisy_select_order"><form action="">';
			echo '<label for="order">Sortieren nach</label> ';
			echo '<input type="hidden" name="q" value="'. $this->framework->getParam('q', '') .'" />';
			echo '<select name="order">';
				foreach($order_options as $key => $value) {
					$selected = '';
					if($key == $current_orderby) $selected = ' selected';
					echo '<option value="'. $key .'"'. $selected .'>'. $value .'</option>';
				}
			echo '</select>';
			$order_up_active = $order_dn_active = '';
			if($current_orderdir == '') {
				$order_up_active = ' active';
			} else {
				$order_dn_active = ' active';
			}

			echo '<input type="submit" class="order_up'. $order_up_active .'" name="order_up" value="Aufsteigend" />';
			echo '<input type="submit" class="order_dn'. $order_dn_active .'" name="order_dn" value="Absteigend" />';
			echo '</form></div>';
				
			echo '</div>' . "\n";
						
			flush();

			echo "\n".'<ul id="kursliste" class="wisy_list">' . "\n";
			
			// render other records
			$records = $searcher->getKurseRecords($offset, $this->rows, $orderBy);
			$this->renderKursRecords($db, $records, $records2 /*recordsToSkip*/, array('q'=>$queryString));
		
			echo '</ul>';
			flush();
				
			if( $pagesel )
			{
				$this->renderPagination($prevurl, $nexturl, $pagesel);
			}
		}
		else 
		{
			if( sizeof($info['suggestions']) == 0 )
			{
				$temp = trim($queryString, ', ');
				echo '<p class="wisy_topnote">';
					echo 'Keine aktuellen Datensätze für <em>&quot;'  . htmlspecialchars($temp) . '&quot;</em> gefunden.<br /><br />';
					echo '<a href="' . $this->framework->getUrl('search', array('q'=>"$temp, Datum:Alles")) . '">Suche wiederholen und dabei <b>auch abgelaufene Kurse berücksichtigen</b> ...</a>';
				echo "</p>\n";
			}
		}
	}
	
	function pageSel($baseUrl, $currRowsPerPage, $currOffset, $totalRows)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::pageSel($baseUrl, $currRowsPerPage, $currOffset, $totalRows);
		
		// find out the current page number (the current page number is zero-based)
		$currPageNumber = intval($currOffset / $currRowsPerPage);
	
		// find out the max. page page number (also zero-based)
		$maxPageNumber = intval($totalRows / $currRowsPerPage);
		if( intval($totalRows / $currRowsPerPage) == $totalRows / $currRowsPerPage ) {
			$maxPageNumber--;
		}
		
		return 'Seite ' . ++$currPageNumber . ' von ' . ++$maxPageNumber;
	}
	
	function renderPagination($prevurl, $nexturl, $pagesel)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderPagination($prevurl, $nexturl, $pagesel);
		
		echo ' <div class="wisy_paginate">';
		
			echo '<a class="prev"';
			if( $prevurl ) echo ' href="/' . $prevurl . '"';
			echo '>&laquo;</a>';
			
			echo '<div class="status">';
			echo $pagesel;
			echo '</div>';
			
			echo '<a class="next"';
			if( $nexturl ) echo ' href="/' . $nexturl . '"';
			echo '>&raquo;</a>';
	
		echo '</div>' . "\n";
	}
	
	function renderKursRecords(&$db, &$records, &$recordsToSkip, $param)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderKursRecords($db, $records, $recordsToSkip, $param);
		
		global $wisyPortalSpalten;

		$loggedInAnbieterId = $this->framework->getEditAnbieterId();

		// build skip hash
		$recordsToSkipHash = array();
		if( is_array($recordsToSkip['records']) )
		{
			reset($recordsToSkip['records']);
			while( list($i, $record) = each($recordsToSkip['records']) )
			{
				$recordsToSkipHash[ $record['id'] ] = true;
			}
		}

		// load all latlng values
		$distances = array();
		if( $this->hasDistanceColumn )
		{
			$ids = '';
			reset($records['records']);
			while( list($i, $record) = each($records['records']) )
			{
				$ids .= ($ids==''? '' : ', ') . $record['id'];
			}
			
			if ($ids != '' )
			{
				$x1 = $this->baseLng *  71460.0;
				$y1 = $this->baseLat * 111320.0;
				$sql = "SELECT kurs_id, lat, lng FROM x_kurse_latlng WHERE kurs_id IN ($ids)";
				$db->query($sql);
				while( $db->next_record() )
				{
					$kurs_id = intval($db->f('kurs_id'));
					$x2 = (floatval($db->f('lng')) / 1000000) *  71460.0;
					$y2 = (floatval($db->f('lat')) / 1000000) * 111320.0;

					// calculate the distance between the points ($x1/$y1) and ($x2/$y2)
					// d = sqrt( (x1-x2)^2 + (y1-y2)^2 )
					$dx = $x1 - $x2; if( $dx < 0 ) $dx *= -1;
					$dy = $y1 - $y2; if( $dy < 0 ) $dy *= -1;
					$d = sqrt( $dx*$dx + $dy*dy ); // $d ist nun die Entfernung in Metern ;-)
					
					// remember the smallest distance
					if( !isset($distances[ $kurs_id ]) || $distances[ $kurs_id ] > $d )
					{
						$distances[ $kurs_id ] = $d;
					}
				}
			}
		}

		// go through result
		$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
		
		$fav_use = $this->framework->iniRead('fav.use', 0);
		
		$rows = 0;
		reset($records['records']);
		while( list($i, $record) = each($records['records']) )
		{	
			// get kurs basics
			$currKursId = $record['id'];
			$currAnbieterId = $record['anbieter'];
			$currKursFreigeschaltet = $record['freigeschaltet'];
			$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $currKursId);

			// record already promoted? if so, skip the normal row
			if( $recordsToSkipHash[ $currKursId ] )
				continue;

			// dump kurs
			$rows ++;
			
			if( $param['promoted'] )
				$class = ' class="wisy_promoted"';
			else
				$class = ($rows%2)==0? ' class="wisy_even"' : '';
			
			echo "  <li$class>\n";
			
			$aclass = '';
			if( $fav_use ) {
				$aclass = ' class="fav_add" data-favid="'.$currKursId.'"';
			}
			
			$aparam = array('id'=>$currKursId, 'q'=>$param['q']);
			echo '<a href="' .$this->framework->getUrl('k', $aparam). "\"{$aclass}>";

			// KURSTITEL
			echo '    <span class="wisy_kurstitel">';
								
			if( $currKursFreigeschaltet == 0 ) { echo '<em>Kurs in Vorbereitung:</em><br />'; }
			if( $currKursFreigeschaltet == 2 ) { echo '<em>Gesperrt:</em><br />'; }
			if( $currKursFreigeschaltet == 3 ) { echo '<em>Abgelaufen:</em><br />'; }
							
			echo htmlspecialchars(stripslashes($record['titel']));
						
			echo '</span>' . "\n";

			// ANBIETER
			if (($wisyPortalSpalten & 1) > 0)
			{
				$this->renderAnbieterCell($db, $currAnbieterId, array('q'=>$param['q'], 'addPhone'=>true, 'promoted'=>$param['promoted'], 'kurs_id'=>$currKursId));
			}
				
			// DURCHFUEHRUNG
			echo '<span class="wisy_durchfuehrung">';
			$durchfClass->formatDurchfuehrung($db, $currKursId, intval($durchfuehrungenIds[0]), 0, 0, 1, '');
			echo '</span>';			
					
			// ENTFERNUNG
			if( $this->hasDistanceColumn )
			{
				if( isset($distances[$currKursId]) )
				{
					$cell = ' <span class="wisy_entfernung">';
					$meters = $distances[$currKursId];
					if( $meters > 1500 )
					{
						// 1 km, 2 km etc.
						$km = intval(($meters+500)/1000); if( $km < 1 ) $km = 1;
						$cell .= '~' . $km . ' km';
					}
					else if( $meters > 550 )
					{
						// 100 m, 200 m etc.
						$hundreds = intval(($meters+50)/100); if( $hundreds < 1 ) $hundreds = 1;
						$cell .= '~' . $hundreds . '00 m';
					}
					else
					{
						$cell .= '&lt;500 m';
					}
					$cell .= '</span>';
					echo $cell;
				}
			}	
			echo '  </a></li>' . "\n";
		}
	}
	
	function renderAnbieterliste(&$searcher, $queryString, $offset)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderAnbieterliste($searcher, $queryString, $offset);
		
		$anbieterRenderer =& createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this->framework);

		$validOrders = array('a', 'ad', 's', 'sd', 'p', 'pd', 'o', 'od', 'h', 'hd', 'e', 'ed', 't', 'td', 'creat', 'creatd');
		$orderBy = stripslashes($_GET['order']); if( !in_array($orderBy, $validOrders) ) $orderBy = 'a';

		$db2 = new DB_Admin();

		$sqlCount = $searcher->getAnbieterCount();
		if( $sqlCount )
		{
			// create get prev / next URLs
			$prevurl = $offset==0? '' : $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset-$this->rows));
			$nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset+$this->rows)) : '';
			if( $prevurl || $nexturl )
			{	
				$param = array('q'=>$queryString);
				if( $orderBy != 'b' ) $param['order'] = $orderBy;
				$param['offset'] = '';
				$pagesel = $this->pageSel($this->framework->getUrl('search', $param), $this->rows, $offset, $sqlCount);
			}
			else
			{
				$pagesel = '';
			}

			// render head
			echo '<p class="wisy_suchergebnisse">';
				echo "<b>$sqlCount Anbieter</b> zum Suchauftrag";
				if( $queryString ) echo ' <span class="wisy_suchbegriff">' . htmlspecialchars(trim($queryString, ', ')) . '</span>';		
			echo '</p>' . "\n";
			flush();

			// render records
			$records = $searcher->getAnbieterRecords($offset, $this->rows, $orderBy);
			$rows = 0;
			
			echo '<ul id="anbieterliste" class="wisy_list">';
			
			while( list($i, $record) = each($records['records']) )
			{
				$class = ($rows%2)==0? ' class="wisy_even"' : '';
				echo "<li$class>\n";
					echo '<a href="'.$this->framework->getUrl('a', array('id'=>$record['id'], 'q'=>$queryString)).'">';
					echo '<span class="wisy_anbietertitel">';
					echo $record['suchname'];
					echo '</span>';
					echo '<span class="wisy_anschrift">';
					echo htmlspecialchars(stripslashes($record['strasse'])) .', ';
					echo htmlspecialchars(stripslashes($record['plz'])) .' ';
					echo htmlspecialchars(stripslashes($record['ort']));
					echo '</span>';
					echo '</a>';
					if($record['anspr_tel'] != '') {
						$fon = str_replace(array('/', '(', ')', '#'), '', $record['anspr_tel']); // Klammern und Gatter usw. entfernen
						$fon = str_replace(' ', '-', $fon); // Leerzeichen in - umwandeln
						$count = 1;
						while($count) $fon = str_replace('--', '-', $fon, $count); // Doppelte -- entfernen
						echo '<a class="wisy_call" title="Anbieter anrufen: '. $fon . '" href="tel:'. htmlentities($fon) .'">'. $fon .'</a>';
					}
				echo '</li>' . "\n";
			}

			// main list end
			echo '</ul><!-- /#anbieterliste -->' . "\n\n";
			flush();

				
			if( $pagesel )
			{
				$this->renderPagination($prevurl, $nexturl, $pagesel);
			}

		}
		else /* if( sqlCount ) */
		{
			echo '<p class="wisy_topnote">Keine Datensätze für <em>&quot;'.htmlspecialchars(trim($queryString, ', ')).'&quot;</em> gefunden.</p>' . "\n";
		}
	}
	
	function renderAnbieterCell(&$db2, $currAnbieterId, $param)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::renderAnbieterCell($db2, $currAnbieterId, $param);
		
		$db2->query("SELECT suchname, pruefsiegel_seit, anspr_tel FROM anbieter WHERE id=$currAnbieterId");
		$db2->next_record();
		$anbieterName = stripslashes($db2->f('suchname'));
		$pruefsiegel_seit = $db2->f('pruefsiegel_seit');
		$anspr_tel = stripslashes($db2->f('anspr_tel'));

		echo '    <span class="wisy_anbieter">';
			
		if( $anbieterName )
		{
			$aparam = array('id'=>$currAnbieterId, 'q'=>$param['q']);			
			echo htmlspecialchars($anbieterName);
					
		}
		
		echo '</span>' . "\n";
	}
}
registerWisyClass('MOBIL_SEARCH_RENDERER_CLASS');