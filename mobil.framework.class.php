<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_FRAMEWORK_CLASS');

// require("mobil.auth.class.inc.php");

// HINWEIS: Falls Sie in Ihren Klassen einen Konstruktor verwenden, vergessen
// Sie nicht, den Konstruktor der Elternklasse ueber parent::__construct() 
// aufzurufen!
class MOBIL_FRAMEWORK_CLASS extends WISY_FRAMEWORK_CLASS
{

	function __construct($baseObject, $addParam)
	{
		
		global $showMobile;
		$showMobile = false;
		
		// Zuerst �berpr�fen, ob MOBILausgabe erw�nscht ist
		if($this->iniRead('mobil.aktiv') && ($this->iniRead('mobil.immermobil') == 1 || $this->isMobile()) || (isset($_COOKIE['immermobil']) && $_COOKIE['immermobil'] == true)) {
			$showMobile = true;
		}

		// Link geklickt: Umschalten auf Mobilversion 
		if(isset($_GET['immermobil'])) {
			if(!$_COOKIE['immermobil']) {
				setcookie('immermobil', true, time()+86400);	// bleibt 24h in Mobilmodus, wenn nicht "zur�ck zu Desktopversion" geklickt
			}
			$showMobile = true;
		}

		// Link geklickt: Umschalten auf Desktopversion
		if(isset($_GET['nomobile'])) {
			setcookie("immermobil", "", time()-3600);		// Mobilversion-Cookie l�schen
			$showMobile = false;
		}

		if($showMobile) {
			// authentication required?
			if( $this->iniRead('mobil.auth.use', 0) == 1 )
			{
				if(class_exists("MOBIL_AUTH_CLASS")) {
				$auth = new MOBIL_AUTH_CLASS($this);
				$auth->check();
				} else { die("Modul mobil.auth.[...] nicht eingebunden!"); }
			}
		}

		parent::__construct($baseObject, $addParam);
	}
	
	function getTitleString($pageTitleNoHtml)
	{
		// Nicht mobil? Trotzdem Sonderbehandlung f�r Favprint-Seitenaufrufe:
		// Sonderfall Favprint: Unsch�ner Seitentitel bei Aufruf der vom Mobiltelefon verschickten Kurslisten-URL etwas sch�ner durch K�rzung
		// get the title as a no-html-string
		global $wisyPortalKurzname;
		if(strpos($pageTitleNoHtml, 'Favprint:') === false) {
			$fullTitleNoHtml  = $pageTitleNoHtml;
		} else {
			$fullTitleNoHtml = 'Favprint:';
		}
		$fullTitleNoHtml .= $fullTitleNoHtml? ' - ' : '';
		$fullTitleNoHtml .= $wisyPortalKurzname;
		return $fullTitleNoHtml;
	}

	function getPrologue($param = array()) {
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::getPrologue($param);
		
		// Einstellungen f�r Spalten anhand mobil.spalten �berschreiben, falls gesetzt
		$mobilspalten = $this->iniRead('mobil.spalten');

		if(trim($mobilspalten) != '') {
			$GLOBALS['wisyPortalSpalten'] = 0;
			$mobilspalten = str_replace(' ', '', $mobilspalten) . ',';
			if( strpos($mobilspalten, 'anbieter,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 1;
			if( strpos($mobilspalten, 'termin,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 2;
			if( strpos($mobilspalten, 'dauer,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 4;
			if( strpos($mobilspalten, 'art,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 8;
			if( strpos($mobilspalten, 'preis,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 16;
			if( strpos($mobilspalten, 'ort,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 32;
			if( strpos($mobilspalten, 'kursnummer,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 64;
			if( strpos($mobilspalten, 'bunummer,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 128;
			if( strpos($mobilspalten, 'bildungsgutschein,'	)!==false ) $GLOBALS['wisyPortalSpalten'] += 256;
			if( strpos($mobilspalten, 'foerderung,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 512;
		}
		
		// HTML-Template aus Datei einlesen
		$mobil_html_file = trim($this->iniRead('mobil.dateien.html'));
		if($mobil_html_file != '') {
			if(file_exists($mobil_html_file) ) {
				$mobil_html = file_get_contents($mobil_html_file);
			}
		}
		
		// HEAD ausgeben	
		$head  = '<meta charset="ISO-8859-1">' . "\n";
		$head .= '<meta name="language" content="de" />' . "\n";
//		$head .= '<meta name="viewport" content="width=device-width"/>' . "\n";
		$head .= '<meta name="viewport" content="initial-scale=1.0"/>' . "\n";
		$head .= $this->getTitleTags($param['title']);
		$head .= $this->getFaviconTags();
		$head .= $this->getOpensearchTags();
		$head .= $this->getRSSTags();
		$head .= $this->getCanonicalTag($param['canonical']);
		
		// Mobil CSS und JS
		if( ($mobil_css_url = trim($this->iniRead('mobil.dateien.css'))) != '') {
			$head .= '<link rel="stylesheet" href="'. $mobil_css_url .'" />' . "\n";
		}
		$head .= '<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>' . "\n";
		if( ($mobil_jslibspath = trim($this->iniRead('mobil.dateien.jslibspath'))) != '') {
			$head .= '<script src="'. $mobil_jslibspath .'jquery.autocomplete.min.js"></script>' . "\n";
		}
		if( ($mobil_js_url = trim($this->iniRead('mobil.dateien.js'))) != '') {
			$head .= '<script src="'. $mobil_js_url .'"></script>' . "\n";
		}

		// Portal-spezifisches CSS
		if( ($portal_css_url = trim($this->iniRead('mobil.dateien.portal_css'))) != '') {
			$head .= '<link rel="stylesheet" href="'. $portal_css_url .'" />' . "\n";
		}
		
		// Portal-spezifisches JS
		if( ($portal_js_url = trim($this->iniRead('mobil.dateien.portal_js'))) != '') {
			$head .= '<script src="'. $portal_js_url .'"></script>' . "\n";
		}

		// $mobil_html = "";

		// Kopf an Stelle _HEAD__ ausgeben
		$mobil_html = str_replace('__HEAD__', $head, $mobil_html);
		
		// Embed __BODYCLASSES__ -> Body Klassen
		$mobil_html = str_replace('__BODYCLASSES__', $this->getBodyClasses($param['bodyClass']), $mobil_html);
		
		// Link "Zur�ck zur Desktopversion" einblenden.
		if(isset($_GET['immermobil']) || $_COOKIE['immermobil']) {
			$mobil_html = str_replace('__HEADERCONTENT__', '<a href="/?nomobile=1" class="zurdesktopversion">'.$this->iniRead('mobil.html.desktoplink.text').'</a>__HEADERCONTENT__', $mobil_html);
		}

		// HEADERCONTENT
		$mobilheadercontent_default = '
		<p class="statistik">
			<strong>__ANZAHL_KURSE__</strong> Kurstitel<br />
			<strong>__ANZAHL_DURCHFUEHRUNGEN__</strong> Angebote<br />
			<strong>__ANZAHL_ANBIETER__</strong> Anbieter<br />
			<span class="claim"></span>
		</p>';
		$mobilheadercontent_custom = trim($this->iniRead('mobil.header.content'));
		$mobilheadercontent = ($mobilheadercontent_custom=="" ? $mobilheadercontent_default : $mobilheadercontent_custom);
		$mobil_html = str_replace('__HEADERCONTENT__', $mobilheadercontent, $mobil_html);

		// Kasten/K�sten unterm Kopfbereich
		$mobil_html = str_replace('__SUBHEADER__', $this->getsubheadercontent("presearch"), $mobil_html);

		// Fu�bereich Platzhalter ersetzen (__FOOTERLOGO__ und __COPYRIGHT__)
		$mobil_html = str_replace('__FOOTERLOGO__', $this->iniRead('mobil.footer.logo'), $mobil_html);
		
		// Anhand der Anfrageart entscheiden, welcher Copyright-Hinweis ausgegeben werden soll
		$db = new DB_Admin();
		$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
		$copyrightString = '';
		
		global $wisyRequestedFile;
		$firstLetter = substr($wisyRequestedFile, 0, 1);
		$requestId = intval(substr($wisyRequestedFile, 1));
	
		if( $firstLetter=='k' && $requestId > 0 )
		{
			$copyrightString = $copyrightClass->renderCopyright($db, 'kurse', $requestId);
		}
		else if( $firstLetter=='a' && $requestId > 0 )
		{
			$copyrightString = $copyrightClass->renderCopyright($db, 'anbieter', $requestId);
		}
		else if( $firstLetter=='g' && $requestId > 0 )
		{
			$copyrightString = $copyrightClass->renderCopyright($db, 'glossar', $requestId);
		} else {
			$copyrightString = $this->iniRead('mobil.footer.copyright');	
		}		
		$mobil_html = str_replace('__COPYRIGHT__', $copyrightString, $mobil_html);

		// Replace __TAGS__ -> Der eigentliche Inhalt
		$mobil_html = $this->replacePlaceholders($mobil_html);
		
		// ... handle __CONTENT__: save stuff behind this placeholder to $this->bodyEnd
		$this->bodyEnd = '';
		if( ($p=strpos($mobil_html, '__CONTENT__')) !== false ) {
			$this->bodyEnd = substr($mobil_html, $p+11);
			$mobil_html = substr($mobil_html, 0, $p);
		}


		return $mobil_html;
	}

	function getsubheadercontent($required_position) {

		// SUBHEADER
		$subheader_html = "";
		$i = 1;

		while(strlen(trim($this->iniRead('mobil.html.subheadercontent.'.$i))) > 0) {
			$subheader_template = '<div class="subheader '.$required_position.' nr_'.$i.'">__SUBHEADERCONTENT__</strong></div>';
			$mobilsubheadercontent = trim($this->iniRead('mobil.html.subheadercontent.'.$i));	// es gibt kein default-content
			$doadd = false;

			if(strpos($mobilsubheadercontent, "|") === FALSE) { 
				$doadd = true;
			} 
			else { 
				$params = explode("|", $mobilsubheadercontent); 
				$position = strtolower(trim($params[1])); 
				if($position == $required_position) { $doadd = true; $mobilsubheadercontent = trim($params[0]); } 
			}

			if($doadd) { $subheader_html .= str_replace('__SUBHEADERCONTENT__', $mobilsubheadercontent, $subheader_template); }

			$i++;
		}
	
		return $subheader_html;
	}
	
	function getSearchField()
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::getSearchField();
		
		$q = $this->getParam('q', '');

		// if the query is not empty, add a comma and a space		
		$q = trim($q);
		if( $q != '' )
		{
			if( substr($q, -1) != ',' )
				$q .= ',';
			$q .= ' ';
		}
		
		// Favoriten
		if($this->iniRead('fav.use', 0) != 0) {
			echo '<div id="wisy_favs" class="clearfix" style="display: none;">';
			echo '<a id="wisy_favs_anzeigen" href="search?q=Fav%3A" title="Zeige Favoriten"><span class="fav_star">&#9733;</span> Zeige <span class="fav_count">1 Favorit</span></a>';
			echo '<a id="wisy_fav_mailprint" href="mailto:?subject=';
			echo $this->iniRead('mobil.favprint.mail.subject', '');
			echo '&body=';
			echo $this->iniRead('mobil.favprint.mail.body', '');
			echo 'http://' . $_SERVER['HTTP_HOST'];
			echo '/search?q=Favprint:"';
			echo ' title="Favoritenliste per Mail senden">Favoriten per Mail senden</a>';
			echo '<a id="fav_delete_all" href="javascript:fav_delete_all()" title="Alle Favoriten l�schen">&times;</a>';
			echo '</div>';
		}

		// Suchfeld
		echo "\n";
		echo '<div id="wisy_searcharea">' . "\n";
		echo '<form action="search" method="get">' . "\n";
		echo '<input type="text" id="wisy_searchinput" class="ac_keyword" name="q" value="' .$q. '" />' . "\n";
		echo '<div class="clearbtn" style="display: none;">x</div>';
		echo '<input type="submit" class="wisy_searchbtn" id="wisy_kurssuche" value="Zeige Kurse" />';
		echo '<input type="submit" class="wisy_searchbtn" id="wisy_anbietersuche" value="Anbieter" />';

		// Umkreisoptionen
		$umkreis_options = array(
			'' => 'Umkreis:',
			'2' => '2 km',
			'5' => '5 km',
			'10' => '10 km',
			'20' => '20 km'
		);
		$current_umkreis = '';
		if(strpos($q, 'km:2') !== false) {
			$current_umkreis = 2;
		} else if(strpos($q, 'km:5') !== false) {
			$current_umkreis = 5;
		} else if(strpos($q, 'km:10') !== false) {
			$current_umkreis = 10;
		} else if(strpos($q, 'km:20') !== false) {
			$current_umkreis = 20;
		}

		echo '<select name="wisy_umkreis" id="wisy_umkreis" class="wisy_searchbtn">';
		foreach($umkreis_options as $key => $value) {
			$selected = '';
			if($key == $current_umkreis) $selected = ' selected';
			echo '<option value="'. $key .'"'. $selected .'>'. $value .'</option>';
		}
		echo '</select>';
		echo '</form>' . "\n";
		echo '</div>' . "\n\n";
		
		echo $this->replacePlaceholders( $this->iniRead('searcharea.below', '') );

		echo $this->getsubheadercontent("postsearch");
	}


	
	function writeStichwoerter($db, $table, $stichwoerter, $richtext = false)
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::writeStichwoerter($db, $table, $stichwoerter);
		
		// Stichwoerter ausgeben
		// load codes
		$ret = '';
		global $codes_stichwort_eigenschaften;
		global $hidden_stichwort_eigenschaften;
		require_once("admin/config/codes.inc.php");
		$codes_array = explode('###', $codes_stichwort_eigenschaften);
		
		// go through codes and stichwoerter
		for( $c = 0; $c < sizeof((array) $codes_array); $c += 2 ) 
		{
			if( $codes_array[$c] == 0 )
				continue; // sachstichwoerter nicht darstellen - aenderung vom 30.03.2010 (bp)
			
			if( $codes_array[$c] & $hidden_stichwort_eigenschaften )
				continue; // explizit verborgene Stichworttypen nicht darstellen
				
			$anythingOfThisCode = 0;
			
			for( $s = 0; $s < sizeof((array) $stichwoerter); $s++ )
			{
				$glossarLink = '';
				$glossarId = $this->glossarDb($db, 'stichwoerter', $stichwoerter[$s]['id']);
				if( $glossarId ) {
					$glossarLink = ' <a href="' . $this->getHelpUrl($glossarId) . '" class="wisy_help" title="Hilfe">i</a>';
				}
				
				if( ($stichwoerter[$s]['eigenschaften']==0 && intval($codes_array[$c])==0 && $glossarLink)
				 || ($stichwoerter[$s]['eigenschaften'] & intval($codes_array[$c])) )
				{
					if( !$anythingOfThisCode ) {
						//$ret .= '<li>' . $codes_array[$c+1] . ':&nbsp;';
					}
					
					$writeAend = false;
					/* 
					// lt. Liste "WISY-Baustellen" vom 5.9.2007, Punkt 8. in "Kursdetails", sollen hier kein Link angezeigt werden.
					// Zitat: "Anzeige der Stichworte ohne Link einblenden" (bp)
					$ret .= '<a title="alle Kurse mit diesem Stichwort anzeigen" href="' .wisy_param('index.php', array('sst'=>"\"{$stichwoerter[$s]['stichwort']}\"", 'skipdefaults'=>1, 'snew'=>2)). '">';
					$writeAend = true;
					*/
					
					$ret .= '<li>'. $stichwoerter[$s]['stichwort'];
					
					if( $writeAend ) {
						$ret .= '</a>';
					}
					
					$ret .= $glossarLink;
					
					$anythingOfThisCode	= 1;
				}
			}
			
			if( $anythingOfThisCode ) {
				$ret .= '</li>'."\n";
			}
		}
		
		return $ret;
	}
	
	function iniRead($key, $default='')
	{
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::iniRead($key, $default);
		
		global $wisyPortalEinstellungen;
		$value = $default;
		
		// Erst �berpr�fen, ob Wert im mobil-Namespace gesetzt ist, 
		//	ansonsten regul�re Ausgabe des Wertes
		
		$mobilkey = 'mobil.' . $key;
		
		if( isset( $wisyPortalEinstellungen[ $mobilkey ] ) ) {
			
			$value = $wisyPortalEinstellungen[ $mobilkey ];

		} else if( isset( $wisyPortalEinstellungen[ $key ] ) )
		{
			$value = $wisyPortalEinstellungen[ $key ];
		}
		return $value;
	}

	function cacheRead($key, $default='')
	{
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::cacheRead($key, $default);
		
		
		global $wisyPortalEinstcache;
		$value = $default;
		
		// Erst �berpr�fen, ob Wert im mobil-Namespace gesetzt ist, 
		//	ansonsten regul�re Ausgabe des Wertes
		
		$mobilkey = 'mobil.' . $key;
		
		if( isset( $wisyPortalEinstcache[ $mobilkey ] ) ) {
			
			$value = $wisyPortalEinstcache[ $mobilkey ];

		} else if( isset( $wisyPortalEinstcache[ $key ] ) )
		{
			$value = $wisyPortalEinstcache[ $key ];
		}
		return $value;
	}
	
	// --------- Mobil-spezifische Funktionen: ---------------------------------------------------
	
	function isMobile() {
		$mobil_string = $this->iniRead('mobil.string');
		return preg_match("/". $mobil_string ."/", strtolower($_SERVER['HTTP_USER_AGENT']));
	}
	
	function unsetArrayKeysByPrefix($inputArray, $prefixes) {
		$outputArray = array();
		foreach ($inputArray as $k => $v) {
			foreach ($prefixes as $p) {
				$add = true;
				if (strpos($k, $p) === 0) {
					$add = false;
					break;
				}
			}
			if ($add) {
				$outputArray[$k] = $v;
			}
		}
		return $outputArray;
	}
};

registerWisyClass('MOBIL_FRAMEWORK_CLASS');