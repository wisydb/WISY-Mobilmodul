<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_KURS_RENDERER_CLASS');

// HINWEIS: Falls Sie in Ihren Klassen einen Konstruktor verwenden, vergessen
// Sie nicht, den Konstruktor der Elternklasse ueber parent::__construct() 
// aufzurufen!
class MOBIL_KURS_RENDERER_CLASS extends WISY_KURS_RENDERER_CLASS
{

	function render()
	{
		
		// Nicht mobil? Dann Funktion der Elternklasse aufrufen:
		global $showMobile;
		if(!$showMobile) return parent::render();
		
		global $wisyPortalSpalten;

		$kursId = intval($_GET['id']);

		// query DB
		$db = new DB_Admin();
		$db->query("SELECT	k.freigeschaltet, k.titel, k.org_titel, k.beschreibung, k.anbieter, k.date_modified, k.bu_nummer, a.pflege_pweinst
						FROM kurse k
						LEFT JOIN anbieter a ON a.id=k.anbieter
						WHERE k.id=$kursId"); // "a.suchname" etc. kann mit "LEFT JOIN anbieter a ON a.id=k.anbieter" zus. abgefragt werden
		if( !$db->next_record() )
			$this->framework->error404();
		$title 				= stripslashes($db->f('titel'));
		$originaltitel		= stripslashes($db->f('org_titel'));
		$freigeschaltet 	= intval($db->f('freigeschaltet'));
		$beschreibung		= stripslashes($db->f('beschreibung'));
		$anbieterId			= intval($db->f('anbieter'));
		$date_modified		= $db->f('date_modified');
		$bu_nummer 			= $db->f('bu_nummer');
		$pflege_pweinst		= intval($db->f('pflege_pweinst'));
		
		// promoted?
		if( intval($_GET['promoted']) == $kursId )
		{
			$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
			$promoter->logPromotedRecordClick($kursId, $anbieterId);
		}

		// page start
		headerDoCache();
		echo $this->framework->getPrologue(array('title'=>$title, 'canonical' => $this->framework->getUrl('k', array('id'=>$kursId)), 'bodyClass'=>'wisyp_kurs'));
		echo $this->framework->getSearchField();
		
		// start the result area
		// --------------------------------------------------------------------
		
		echo '<div id="wisy_resultarea" class="single nojs">';
		
		$fav_use = $this->framework->iniRead('fav.use', 0);
		$favclass = '';
		if( $fav_use ) {
			$favclass = ' class="fav_add" data-favid="'.$kursId.'"';
		}
			
		
		echo '<div id="wisy_resultsummary"'. $favclass .'>';
		echo '<h1>' . htmlentities($title) . '</h1>';
		
		// load anbieter
		$db->query("SELECT * FROM anbieter WHERE id=$anbieterId");
		if( $db->next_record() && $db->f('freigeschaltet') ==1 ) {
			$postname = $db->fs('postname');
			$suchname = $db->fs('suchname');
			echo '<h2>' . htmlentities($postname? $postname : $suchname) . '</h2>';
		}
		echo '</div>';
		
		$vollst = $this->framework->getVollstaendigkeitMsg($db, $kursId, 'quality.portal');			
		if( $vollst['banner'] != '' )
		{
			echo '<div class="wisy_badqualitybanner">'.$vollst['banner'].'</div>';
		}
		
		// TABS
		echo '<nav id="wisy_resulttabs" class="clearfix">'
		.		'<ul>'
		.			'<li id="tabs_info" class="active"><a href="#sections_info">Was</a></li>'
		.			'<li id="tabs_termine"><a href="#sections_termine">Wann Wo</a></li>'
		.			'<li id="tabs_karte"><a href="#sections_karte">Karte</a></li>'
		.			'<li id="tabs_kontakt"><a href="#sections_kontakt">Kontakt</a></li>'
		.		'</ul>'
		.	'</nav>';
		
		// SECTION: Info
		echo '<div id="sections_info" class="wisy_resultsection active mh200 automin_nojs">';
			
		// Beschreibung ausgeben
		if ($freigeschaltet==0) { echo '<p class="wisy_statustext">Dieses Angebot ist in Vorbereitung.</p>'; }
		else if ($freigeschaltet==3) { echo '<p class="wisy_statustext">Dieses Angebot ist abgelaufen.</p>'; }
		else if ($freigeschaltet==2) { echo '<p class="wisy_statustext">Dieses Angebot ist gesperrt.</p>'; }

		if( $freigeschaltet!=2 || $_REQUEST['showinactive']==1 )
		{
			
			if( $beschreibung != '' ) {
				$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
				echo $wiki2html->run($beschreibung);
			}
			
			// Tabellarische Infos ... jetzt als Aufzählung
			$list = '';
			
			// ... Stichwoerter
			$stichwoerter = $this->framework->loadStichwoerter($db, 'kurse', $kursId);
			if( sizeof($stichwoerter) )
			{
				$list .= $this->framework->writeStichwoerter($db, 'kurse', $stichwoerter);
			}

			// ... Bildungsurlaubsnummer 
			if (($wisyPortalSpalten & 128) > 0 && $bu_nummer)
			{
				$list .= '<li>Bildungsurlaubsnummer</li>';
			}

			if( $list != '' ) 
			{
				echo '<div class="wisy_kursinfo">';
				echo '<strong>Suchmerkmale:</strong>';
				echo '<ul>' . $list . '</ul>';
				echo '</div>';
			}
			
			// ENDE SECTION: Info
			echo '</div><!-- /#sections_info -->';
			
			// flush, Rest kann dann "im Hintergrund" laden
			flush();
		
			// SECTION: Termine
			echo '<div id="sections_termine" class="wisy_resultsection">';
			

			// Durchfuehrungen vorbereiten
			$showAllDurchf = 1; // Immer alle Durchführungen zeigen
			
			$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
			$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $kursId, $showAllDurchf);
			echo '<p class="wisy_anzahltermine">';
				if( sizeof($durchfuehrungenIds)==0 ) {
					echo $this->framework->iniRead('durchf.msg.keinedf', 'Keine Durchf&uuml;hrungen bekannt.');
				}
				else if( sizeof($durchfuehrungenIds) == 1 ) {
					echo '1 Durchf&uuml;hrung:';
				}
				else {
					echo  sizeof($durchfuehrungenIds). ' Durchf&uuml;hrungen:';
				}
			echo '</p>';
		
			// Durchfuehrungen: init map (global $this->framework->map is used in formatDurchfuehrung())
			$this->framework->map =& createWisyObject('WISY_GOOGLEMAPS_CLASS', $this->framework);
		
			// Durchfuehrungen ausgeben
			if( sizeof($durchfuehrungenIds) )
			{
										
					$maxDurchf = intval($this->framework->iniRead('details.durchf.max'));
					if( $maxDurchf <= 0 || $showAllDurchf )
						$maxDurchf = 1000;
				
					$anzDurchf = 0;
					$moreLink = '';
					for( $d = 0; $d < sizeof($durchfuehrungenIds); $d++ )
					{
						$class = ($d%2)==1? ' wisy_even' : '';
						echo '<div class="wisy_durchfuehrung'. $class .'">';
						
							$durchfClass->formatDurchfuehrung($db, $kursId, $durchfuehrungenIds[$d],  
													1,  /*1=add details*/
													$anbieterId,
													$showAllDurchf);
							$anzDurchf++;
							if( $anzDurchf >= $maxDurchf )
							{
								$moreLink = 'Zeige weitere Durchführungen...';
								break;
							}
						echo '</div>';
					}
				if( $moreLink != '' )
				{
					echo "<p><a href=\"".$this->framework->getUrl('k', array('id'=>$kursId, 'showalldurchf'=>1))."\">$moreLink</a></p>";
				}
			}
		
			// ENDE SECTION: Termine
			echo '</div><!-- /#sections_termine -->';
		
			// SECTION: Karte
			echo '<div id="sections_karte" class="wisy_resultsection">';
		
			// map
			if( $this->framework->map->hasPoints() && $_SERVER['HTTPS']!='on' )
			{
				echo $this->framework->map->render();
			}
		
			// ENDE SECTION: Karte
			echo '</div><!-- /#sections_karte -->';
		
			// SECTION: Kontakt
			echo '<div id="sections_kontakt" class="wisy_resultsection">';
		
			// visitenkarte des anbieters
			$anbieterRenderer =& createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this->framework);
			echo $anbieterRenderer->renderDetailsMobile($db, $anbieterId, $kursId);
			
			// ENDE SECTION: Kontakt
			echo '</div><!-- /#sections_kontakt -->';
			
			// Vollständigkeit, Feedback
			if( $vollst['msg'] != '' )
			{
				echo '<div id="wisy_metainfo" class="'.$this->framework->getAllowFeedbackClass().'">';
				echo $vollst['msg'];
				echo '</div><!-- /#wisy_metainfo -->';
			}
	
		} // freigeschaltet
	
		echo '</div><!-- /#wisy_resultarea -->';
	
		// Wird jetzt in mobil.framework.class ausgegeben
		//$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
		//$copyrightClass->renderCopyright($db, 'kurse', $kursId);
		
		echo $this->framework->getEpilogue();
	}
};
registerWisyClass('MOBIL_KURS_RENDERER_CLASS');