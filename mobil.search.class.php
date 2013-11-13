<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_SEARCH_CLASS');

class MOBIL_SEARCH_CLASS extends WISY_SEARCH_CLASS
{

	function prepare($queryString)
	{
		
		// Nicht mobil? Trotzdem um die FAVPRINT-Funktion erweitern, die wir brauchen 
		// 		um eine Liste von Kurs-IDs (per Mail vom Mobilgerät gesendet) aufzulisten analog der FAV-Liste aus dem Cookie
		
		// first, apply the stdkursfilter
		global $wisyPortalFilter;
		global $wisyPortalId;
		if( $wisyPortalFilter['stdkursfilter'] != '' )
		{
			$queryString .= ", .portal$wisyPortalId";
		}

		$this->error 		= false;
		$this->queryString	= $queryString; // needed for the cache
		
		$this->tokens		= $this->tokenize($queryString);
		$this->rawJoinKurse = '';
		$this->rawJoin 		= '';
		$this->rawWhere		= '';
		$this->rawCanCache	= true;
		
		// pass 1: collect some values
		$this->last_lat = 0;
		$this->last_lng = 0;
		$has_bei = false;
		$max_km = 500;
		$default_km = $this->framework->iniRead('radiussearch.defaultkm', 2);
		$km = floatval($default_km);
		for( $i = 0; $i < sizeof($this->tokens['cond']); $i++ )
		{
			$value = $this->tokens['cond'][$i]['value'];
			switch( $this->tokens['cond'][$i]['field'] )
			{
				case 'bei':
					$has_bei = true;
					break;
					
				case 'umkreis':
					$has_bei = true;
					break;
					
				case 'km':
					$km = floatval(str_replace(',', '.', $value));
					if( $km <= 0.0 || $km > $max_km )
						$km = 0.0; // error
					break;
			}
		}
				
		// pass 2: create SQL
		$abgelaufeneKurseAnzeigen = 'no';
		for( $i = 0; $i < sizeof($this->tokens['cond']); $i++ )
		{
			// build SQL statements for this part
			$value = $this->tokens['cond'][$i]['value'];
			switch( $this->tokens['cond'][$i]['field'] )
			{
				case 'tag':
					$tagNotFound = false;
					if( strpos($value, ' ODER ') !== false )
					{
						// ODER-Suche
						$subval = explode(' ODER ', $value);
						$rawOr = '';
						for( $s = 0; $s < sizeof($subval); $s++ )
						{	
							$tag_id = $this->lookupTag(trim($subval[$s]));
							if( $tag_id == 0 )
								{ $tagNotFound = true; break; }							
							$rawOr .= $rawOr==''? '' : ' OR ';
							$rawOr .= "j$i.tag_id=$tag_id";
						}
						if( !$tagNotFound )
						{
							$this->rawJoin  .= " LEFT JOIN x_kurse_tags j$i ON x_kurse.kurs_id=j$i.kurs_id";
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "($rawOr)";
						}
					}
					else
					{
						// einfache UND- oder NICHT-Suche
						$op = '';
						if( $value{0} == '-' )
						{
							$value = substr($value, 1);
							$op = 'not';
						}
						
						$tag_id = $this->lookupTag($value);
						if( $tag_id == 0 )
						{
							$tagNotFound = true;
						}
						else
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							if( $op == 'not' )
							{
								$this->rawWhere .= "x_kurse.kurs_id NOT IN(SELECT kurs_id FROM x_kurse_tags WHERE tag_id=$tag_id)";
							}
							else
							{
								$this->rawJoin  .= " LEFT JOIN x_kurse_tags j$i ON x_kurse.kurs_id=j$i.kurs_id";
								$this->rawWhere .= "j$i.tag_id=$tag_id";
							}
						}
					}

					if( $tagNotFound )
					{
						$this->error = array('id'=>'tag_not_found', 'tag'=>$value, 'first_bad_tag'=>$i);
						break;
					}
					break;

				case 'schaufenster':
					$portalId = intval($value);
					$this->rawJoin  .= " LEFT JOIN anbieter_promote j$i ON x_kurse.kurs_id=j$i.kurs_id";
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					$this->rawWhere .= "(j$i.portal_id=$portalId AND j$i.promote_active=1)";
					break;
				
				case 'preis':
					if( preg_match('/^([0-9]{1,9})$/', $value, $matches) )
					{	
						$preis = intval($matches[1]);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						if( $preis == 0 )
							$this->rawWhere .= "x_kurse.preis=0";
						else
							$this->rawWhere .= "(x_kurse.preis!=-1 AND x_kurse.preis<=$preis)";
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $value, $matches) )
					{	
						$preis1 = intval($matches[1]);
						$preis2 = intval($matches[2]);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						$this->rawWhere .= "(x_kurse.preis>=$preis1 AND x_kurse.preis<=$preis2)";
					}
					else
					{
						$this->error = array('id'=>'invalid_preis', 'field'=>$value) ;
					}
					break;
				
				case 'plz':
					$this->rawJoin  .= " LEFT JOIN x_kurse_plz j$i ON x_kurse.kurs_id=j$i.kurs_id";
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( strlen($value) < 5 )
						$this->rawWhere .= "(j$i.plz LIKE '".addslashes($value)."%')";
					else
						$this->rawWhere .= "(j$i.plz='".addslashes($value)."')";
					break;
				
				case 'fav':
					// safely get the IDs - do not use the Cookie-String directly!
					$fav_ids = array();
					$temp = explode(',',$_COOKIE['fav']);
					for( $j = 0; $j < sizeof($temp); $j++ ) 
						$fav_ids[] = intval($temp[$j]);
					
					$this->rawCanCache = false;
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( sizeof($fav_ids) >= 1 ) {
						$this->rawWhere .= "(x_kurse.kurs_id IN (".implode(',', $fav_ids)."))";
						$abgelaufeneKurseAnzeigen = 'void';
					}
					else {
						$this->rawWhere .= '(0)';
					}
					break;
					
				// NEU zur Unterstützung der MOBILversion: Anzeigen einer per Mail vom Mobilgerät gesendeten Liste von 
				//		Kursen (anhand kommaseparierter ID-Liste) zwecks einfachem Ausdrucken etc.	
				case 'favprint':
					// safely get the IDs - do not use the URL-String directly!
					$favprint_ids = array();
					$temp = explode('/',$value); // Auch hier wie in Adressen muss der Schraegstrich anstelle des Kommas verwendet werden (das Komma trennt ja schon die verschiedenen Suchkritieren)
					for( $j = 0; $j < sizeof($temp); $j++ ) 
						$favprint_ids[] = intval($temp[$j]);
					
					$this->rawCanCache = false;
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( sizeof($favprint_ids) >= 1 ) {
						$this->rawWhere .= "(x_kurse.kurs_id IN (".implode(',', $favprint_ids)."))";
						$abgelaufeneKurseAnzeigen = 'void';
					}
					else {
						$this->rawWhere .= '(0)';
					}
					break;
				
				case 'bei':
					$value = str_replace('/', ',', $value); // in Adressen muss der Schraegstrich anstelle des Kommas verwendet werden (das Komma trennt ja schon die verschiedenenn Suchkriterien)
					$soll_accuracy = 4; // stadt/ortschaft, s. http://code.google.com/intl/de/apis/maps/documentation/reference.html#GGeoAddressAccuracy
					{
						if( !is_object($this->g_map) )
							$this->g_map =& createWisyObject('WISY_GOOGLEMAPS_CLASS', $this->framework);
							
						$geocodeRet	= $this->g_map->geocodeCached($value);
						if( $geocodeRet === false || $geocodeRet['status'] != 200 )
						{
							$this->error = array('id'=>'bad_location', 'field'=>$value, 'status'=>$geocodeRet['status']);
						}
						else if( $geocodeRet['accuracy'] < $soll_accuracy )
						{
							$this->error = array('id'=>'inaccurate_location', 'field'=>$value, 'ist_accuracy'=>$geocodeRet['accuracy'], 'soll_accuracy'=>$soll_accuracy) ;
						}
						else
						{
							$radius_meters = $km * 1000.0;
							
							$radius_lat = $radius_meters / 111320.0; // Abstand zwischen zwei Breitengraden: 111,32 km  (weltweit)
							$radius_lng = $radius_meters /  71460.0; // Abstand zwischen zwei Längengraden :  71,46 km  (im mittel in Deutschland)
							
							list($lat, $lng) = explode(',', $geocodeRet['latlng']);
							
							$min_lat = intval( ($lat - $radius_lat)*1000000 );
							$max_lat = intval( ($lat + $radius_lat)*1000000 );
							$min_lng = intval( ($lng - $radius_lng)*1000000 );
							$max_lng = intval( ($lng + $radius_lng)*1000000 );

							$this->rawJoin  .= " LEFT JOIN x_kurse_latlng j$i ON x_kurse.kurs_id=j$i.kurs_id";
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "((j$i.lat BETWEEN $min_lat AND $max_lat) AND (j$i.lng BETWEEN $min_lng AND $max_lng))"; // lt. http://dev.mysql.com/doc/refman/4.1/en/mysql-indexes.html wird der B-Tree auch fuer groesser/kleiner oder BETWEEN abfragen verwendet.
							
							if( isset($_COOKIE['debug']) )
							{
								echo '<p style="background-color: orange;">'; print_r($geocodeRet); echo '</p>';
							}

							// remember some stuff for the getInfo() function (needed eg. for the "distance"-column)
							$this->last_lat = $lat;
							$this->last_lng = $lng;
						}
					}
					break;
					
				// NEU für MOBILversion: Umkreissuche direkt nach Koordinaten (aus der Mobilgerät-Ortung)
				case 'umkreis':
				
					$radius_meters = $km * 1000.0;
							
					$radius_lat = $radius_meters / 111320.0; // Abstand zwischen zwei Breitengraden: 111,32 km  (weltweit)
					$radius_lng = $radius_meters /  71460.0; // Abstand zwischen zwei Längengraden :  71,46 km  (im mittel in Deutschland)
							
					list($lat, $lng) = explode('/', $value);
							
					$min_lat = intval( ($lat - $radius_lat)*1000000 );
					$max_lat = intval( ($lat + $radius_lat)*1000000 );
					$min_lng = intval( ($lng - $radius_lng)*1000000 );
					$max_lng = intval( ($lng + $radius_lng)*1000000 );

					$this->rawJoin  .= " LEFT JOIN x_kurse_latlng j$i ON x_kurse.kurs_id=j$i.kurs_id";
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					$this->rawWhere .= "((j$i.lat BETWEEN $min_lat AND $max_lat) AND (j$i.lng BETWEEN $min_lng AND $max_lng))"; // lt. http://dev.mysql.com/doc/refman/4.1/en/mysql-indexes.html wird der B-Tree auch fuer groesser/kleiner oder BETWEEN abfragen verwendet.
							
					// remember some stuff for the getInfo() function (needed eg. for the "distance"-column)
					$this->last_lat = $lat;
					$this->last_lng = $lng;

					break;
				
				case 'km':
					if( !$has_bei )
					{
						$this->error = array('id'=>'km_without_bei');
					}
					else if( $km == 0.0 )
					{
						$this->error = array('id'=>'bad_km', 'max_km'=>$max_km, 'default_km'=>$default_km);
					}
					break;
				
				case 'datum':
					if( strtolower($value) == 'alles' )
					{
						$abgelaufeneKurseAnzeigen = 'yes';
					}
					else if( preg_match('/^heute([+-][0-9]{1,5})?$/i', $value, $matches) )
					{
						$offset = intval($matches[1]);
						$abgelaufeneKurseAnzeigen = 'void';
						$todayMidnight = strtotime(strftime("%Y-%m-%d"));
						$wantedday = strftime("%Y-%m-%d", $todayMidnight + $offset*24*60*60);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						$this->rawWhere .= "(x_kurse.beginn>='$wantedday')"; // 13:58 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
					}
					else if( preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})$/', $value, $matches) )
					{
						$day = intval($matches[1]);
						$month = intval($matches[2]);
						$year = intval($matches[3]); if( $year <= 99 ) $year += 2000;
						$timestamp = mktime(0, 0, 0, $month, $day, $year);
						if( $timestamp <= 0 )
						{
							$this->error = array('id'=>'invalid_date', 'date'=>$value) ;
						}
						else
						{
							$abgelaufeneKurseAnzeigen = 'void';
							$wantedday = strftime("%Y-%m-%d", $timestamp);
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.beginn>='$wantedday')"; // 13:59 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
						}
					}
					else
					{
						$this->error = array('id'=>'invalid_date', 'field'=>$value) ;
					}
					break;
				
				case 'dauer':
					$dauer_error = true;
					if( preg_match('/^([0-9]{1,9})$/', $value, $matches) )
					{	
						$dauer = intval($matches[1]);
						if( $dauer > 0 )
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.dauer!=0 AND x_kurse.dauer<=$dauer)";
							$dauer_error = false;
						}
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $value, $matches) )
					{	
						$dauer1 = intval($matches[1]);
						$dauer2 = intval($matches[2]);
						if( $dauer1 > 0 && $dauer2 > 0 && $dauer1 <= $dauer2 )
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.dauer>=$dauer1 AND x_kurse.dauer<=$dauer2)";
							$dauer_error = false;
						}
					}
					
					if( $dauer_error )
					{
						$this->error = array('id'=>'invalid_dauer', 'field'=>$value) ;
					}
					break;
				
				case 'volltext':
					// volltextsuche, aktuell gibt es ein Volltextindex über kurse.titel und kurse.beschreibung; dieser
					// wird vom core10 *nicht* verwendet und vom redaktionssystem wohl eher selten.
					// aktuell nehmen wird diesen Index einfach, sollten wir hier aber etwas anderes benötigen, 
					// kann der alte Volltextindex verworfen werden. ALSO:
					if( $value != '' )
					{
						$this->rawJoinKurse = " LEFT JOIN kurse ON x_kurse.kurs_id=kurse.id";	 // this join is needed only to query COUNT(*)
						
						$this->rawWhere    .= $this->rawWhere? ' AND ' : ' WHERE ';				
						$this->rawWhere    .= "MATCH(kurse.titel, kurse.beschreibung) AGAINST('".addslashes($value)."' IN BOOLEAN MODE)";
					}
					else
					{
						$this->error = array('id'=>'missing_fulltext') ;
					}
					break;
				
				default:
					$this->error = array('id'=>'field_not_found', 'field'=>$this->tokens['cond'][$i]['field']) ;
					break;
			}			
		}
		
		/* -- leere Anfragen sind für "diese kurse beginnen morgen" notwendig, leere Anfragen sind _kein_ Fehler!
		if( !is_array($this->error) && $this->rawWhere=='' )
		{
			$this->error = array('id'=>'empty_query');
		}
		*/

		// finalize SQL
		if( !is_array($this->error) )
		{
			if( $abgelaufeneKurseAnzeigen == 'no' )
			{
				$today = strftime("%Y-%m-%d");
				
				$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
				$this->rawWhere .= "(x_kurse.beginn>='$today')"; // 13:59 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
			}
		}
	}
};

registerWisyClass('MOBIL_SEARCH_CLASS');