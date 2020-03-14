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
		
		global $wisyCore;
		$head .= '<link rel="stylesheet" href="'.$wisyCore.'/lib/cookieconsent/cookieconsent.min.css" />' . "\n";
		
		$protocol = $this->iniRead('portal.https', '') ? "https" : "http";
		$head .= '<script src="/files/mobil/jslibs/jquery-1.9.1.min.js"></script>' . "\n";
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
		
		if($this->iniRead('cookiebanner', '') == 1) {
		    /* ! $head .= '<script type="text/javascript">window.cookiebanner_html = \''.$this->iniRead('cookiebanner.html', '').'\'; </script>' . "\n";
		     $head .= '<script src="/core20/cookiebanner.js"></script>' . "\n"; */
		    
		    global $wisyCore;
		    $head .= '<script src="'.$wisyCore.'/lib/cookieconsent/cookieconsent.min.js'.'"></script>' . "\n";
		}
		
		// Cookie Banner settings
		if($this->iniRead('cookiebanner', '') == 1) {
		    
		    $head .= "<script>\n";
		    $head .= "window.cookiebanner = {};\n";
		    $head .= "window.cookiebanner.optoutCookies = \"{$this->iniRead('cookiebanner.cookies.optout', '')},fav,fav_init_hint\";\n";
		    $head .= "window.cookiebanner.optedOut = false;\n";
		    $head .= "window.cookiebanner.favOptoutMessage = \"{$this->iniRead('cookiebanner.fav.optouthinweis', 'Ihr Favorit konnte auf diesem Computer nicht gespeichert gewerden da Sie die Speicherung von Cookies abgelehnt haben. Sie k&ouml;nnen Ihre Cookie-Einstellungen in den Datenschutzhinweisen anpassen.')}\";\n";
		    $head .= "window.cookiebanner.piwik = \"{$this->iniRead('analytics.piwik', '')}\";\n";
		    $head .= "window.cookiebanner.uacct = \"{$this->iniRead('analytics.uacct', '')}\";\n";
		    
		    $head .= 'window.addEventListener("load",function(){window.cookieconsent.initialise({';
		    
		    $cookieOptions = array();
		    $cookieOptions['type'] = 'opt-out';
		    $cookieOptions['revokeBtn'] = '<div style="display:none;"></div>'; // Workaround for cookieconsent bug. Revoke cannot be disabled correctly at the moment
		    $cookieOptions['position'] = $this->iniRead('cookiebanner.position', 'top-left');
		    
		    $cookieOptions['law'] = array();
		    $cookieOptions['law']['countryCode'] = 'DE';
		    
		    $cookieOptions['cookie'] = array();
		    $cookieOptions['cookie']['expiryDays'] = intval($this->iniRead('cookiebanner.cookiegueltigkeit', 7));
		    
		    $cookieOptions['content'] = array();
		    $cookieOptions['content']['message'] = $this->iniRead('cookiebanner.hinweis.text', 'Wir verwenden Cookies, um Ihnen eine Merkliste sowie eine Seiten&uuml;bersetzung anzubieten und um Kursanbietern die Pflege ihrer Kurse zu erm&ouml;glichen. Indem Sie unsere Webseite nutzen, erkl&auml;ren Sie sich mit der Verwendung der Cookies einverstanden. Weitere Details finden Sie in unserer Datenschutzerkl&auml;rung.');
		    
		    $this->detailed_cookie_settings_merkliste = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.merkliste', ''))) > 3); // legacy compatibility
		    $cookieOptions['content']['zustimmung_merkliste'] = $this->iniRead('cookiebanner.zustimmung.merkliste', false);
		    
		    $this->detailed_cookie_settings_onlinepflege = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.onlinepflege', ''))) > 3); // legacy compatibility
		    $cookieOptions['content']['zustimmung_onlinepflege'] = $this->iniRead('cookiebanner.zustimmung.onlinepflege', false);
		    
		    $this->detailed_cookie_settings_translate = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.translate', ''))) > 3); // legacy compatibility
		    $cookieOptions['content']['zustimmung_translate'] = $this->iniRead('cookiebanner.zustimmung.translate', false);
		    
		    $this->detailed_cookie_settings_analytics = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.analytics', ''))) > 3); // legacy compatibility
		    $cookieOptions['content']['zustimmung_analytics'] = $this->iniRead('cookiebanner.zustimmung.analytics', false);
		    
		    $cookieOptions['content']['message'] = str_ireplace('__ZUSTIMMUNGEN__',
		        '<ul class="cc-consent-details">'
		        .($cookieOptions['content']['zustimmung_merkliste'] ? $this->addCConsentOption("merkliste", $cookieOptions) : '')
		        .($cookieOptions['content']['zustimmung_onlinepflege'] ? $this->addCConsentOption("onlinepflege", $cookieOptions) : '')
		        .($cookieOptions['content']['zustimmung_translate'] ? $this->addCConsentOption("translate", $cookieOptions) : '')
		        .($cookieOptions['content']['zustimmung_analytics'] ? $this->addCConsentOption("analytics", $cookieOptions) : '')
		        .'__ZUSTIMMUNGEN_SONST__'
		        .'</ul>',
		        $cookieOptions['content']['message']
		        );
		    
		    global $wisyPortalEinstellungen;
		    reset($wisyPortalEinstellungen);
		    $allPrefix = 'cookiebanner.zustimmung.sonst';
		    $allPrefixLen = strlen($allPrefix);
		    foreach($wisyPortalEinstellungen as $key => $value)
		    {
		        if( substr($key, 0, $allPrefixLen)==$allPrefix )
		        {
		            $cookieOptions['content']['message'] = str_replace('__ZUSTIMMUNGEN_SONST__',
		                $this->addCConsentOption("analytics", $key).'__ZUSTIMMUNGEN_SONST__',
		                $cookieOptions['content']['message']);
		        }
		    }
		    $cookieOptions['content']['message'] = str_replace('__ZUSTIMMUNGEN_SONST__', '', $cookieOptions['content']['message']);
		    
		    
		    $cookieOptions['content']['message'] = str_ireplace('__HINWEIS_ABWAHL__',
		        '<span class="hinweis_abwahl">'
		        .$this->iniRead('cookiebanner.hinweis.abwahl', '(Option abw&auml;hlen, wenn nicht einverstanden)')
		        .'</span>',
		        $cookieOptions['content']['message']);
		    
		    $cookieOptions['content']['allow'] = $this->iniRead('cookiebanner.erlauben.text', 'OK', 1);
		    $cookieOptions['content']['deny'] = $this->iniRead('cookiebanner.ablehnen.text', 'Ablehnen', 1);
		    $cookieOptions['content']['link'] = $this->iniRead('cookiebanner.datenschutz.text', 'Mehr erfahren', 1);
		    $cookieOptions['content']['href'] = $this->iniRead('cookiebanner.datenschutz.link', '');
		    
		    $cookieOptions['palette'] = array();
		    $cookieOptions['palette']['popup'] = array();
		    $cookieOptions['palette']['popup']['background'] = $this->iniRead('cookiebanner.hinweis.hintergrundfarbe', '#EEE');
		    $cookieOptions['palette']['popup']['text'] = $this->iniRead('cookiebanner.hinweis.textfarbe', '#000');
		    $cookieOptions['palette']['popup']['link'] = $this->iniRead('cookiebanner.hinweis.linkfarbe', '#3E7AB8');
		    
		    $cookieOptions['palette']['button']['background'] = $this->iniRead('cookiebanner.erlauben.buttonfarbe', '#3E7AB8');
		    $cookieOptions['palette']['button']['text'] = $this->iniRead('cookiebanner.erlauben.buttontextfarbe', '#FFF');
		    
		    $cookieOptions['palette']['highlight']['background'] = $this->iniRead('cookiebanner.ablehnen.buttonfarbe', '#FFF');
		    $cookieOptions['palette']['highlight']['text'] = $this->iniRead('cookiebanner.ablehnen.buttontextfarbe', '#000');
		    
		    $head .= trim(json_encode($cookieOptions, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), '{}') . ',';
		    
		    // Callbacks for enabling / disabling Cookies
		    $head .= 'onInitialise: function(status) {
						var didConsent = this.hasConsented();
						if(!didConsent) {
							window.cookiebanner.optedOut = true;
							updateCookieSettings();
						}
						callCookieDependantFunctions();
					},
					onStatusChange: function(status) {
						var didConsent = this.hasConsented();
						if(!didConsent) {
							window.cookiebanner.optedOut = true;
							updateCookieSettings();
						}
						callCookieDependantFunctions();
					}';
		    
		    // Hide Revoke Button and enable custom revoke function in e.g. "Datenschutzhinweise"
		    // Add an <a> tag with ID #wisy_cookieconsent_settings anywhere on your site. It will re-open the cookieconsent popup when clicked
		    $head .= '},
					function(popup){
						popup.toggleRevokeButton(false);
						window.cookieconsent.popup = popup;
						jQuery("#wisy_cookieconsent_settings").on("click", function() {
							window.cookieconsent.popup.open();
							window.cookiebanner.optedOut = false;
							updateCookieSettings();
							return false;
						});
					}';
		    
		    $head .= ');
		        
			/* save detailed cookie consent status */
				jQuery(".cc-btn.cc-allow").click(function(){
					jQuery(".cc-consent-details input[type=checkbox]").each(function(){
						var cname = jQuery(this).attr("name");
						$.removeCookie(cname, { path: "/" });
						if(jQuery(this).is(":checked")) {
							setCookieSafely(cname, "allow", { expires:7});
		        
							if(cname == "cconsent_analytics") {
								$.ajax(window.location.href); // call same page with analytics allowed to count this page view
							}
						}
					});
				});
		        
			});
		        
			'.($this->detailed_cookie_settings_merkliste ? "" : "window.cookiebanner_zustimmung_merkliste_legacy = 1;").'
			'.($this->detailed_cookie_settings_onlinepflege ? "" : "window.cookiebanner_zustimmung_onlinepflege_legacy = 1;").'
			'.($this->detailed_cookie_settings_translate ? "" : "window.cookiebanner_zustimmung_translate_legacy = 1;").'
			    
			</script>'."\n"; // end initialization of cookie consent window
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