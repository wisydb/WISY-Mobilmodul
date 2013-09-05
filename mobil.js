if (!window.console) console = {log: function() {}};
window.addEventListener('load', function() {setTimeout(scrollTo, 0, 0, 1); }, false);

var fav_resize_timer;

$(document).ready(function() {
	
	// MOBILfunktionen
	$('.nojs').removeClass('nojs');
	updateMainnav();
	updateTabs();
	prepareSearch();
	prepareSort();
	prepareSwipe();
	
	// STANDARDfunktionen
	initFeedback();
	fav_init();
	initAutocomplete();
});

function updateMainnav() {
	
	// Unterpunkte bei Klick ausklappen / einklappen wenn vorhanden
	$('#mainnav a, #metanav a').on('click', function() {
		if(!$(this).hasClass('wisy_help')) {
			$firstUl = $(this).siblings('ul').first();
			if($firstUl.length) {
				if($firstUl.hasClass('open')) {
					$firstUl.removeClass('open').stop(true,true).hide('fast');
				} else {
					$firstUl.children('li').show();
					$firstUl.addClass('open').stop(true,true).hide().show('slow');
				}
				return false;
			}
		}
	});
	
	// Gekürzte Texte erst zu- dann bei Klick aufklappen
	$('.automin_nojs').addClass('automin min').removeClass('automin_nojs');
	$('.automin.min').on('click', function(evt) {
		// Nur ausklappen, wenn Klick nicht auf einem Link im sichtbaren Inhalt war
		if(!$(evt.target).closest('a').length) $(this).removeClass('min');
	});
	// Sonderfall Kontakt / Anbieter vcard: Alle folgenden Elemente im übergeordneten Container zu- / aufklappen
	$('.automin.min.hide_after').nextAll().hide();
	$('.automin.min.hide_after').on('click', function(evt) {
		// Nur ausklappen, wenn Klick nicht auf einem Link im sichtbaren Inhalt war
		if(!$(evt.target).closest('a').length) $(this).nextAll().show();
	});
}

function updateTabs() {
	
	$('#wisy_resulttabs li a').on('click', function() {
		
		$('#wisy_resulttabs li.active').removeClass('active');
		$(this).parent().addClass('active');
		
		$('.wisy_resultsection.active').removeClass('active');
		$($(this).attr('href')).addClass('active');
		
		if($("#wisy_map2").is(':visible') && gm_map == undefined) {
			$("#wisy_map2").initWisyMap();
		}

		return false;
	});
}

function prepareSearch() {
	
	// Clearbutton
	// nur zeigen, wenn Feld nicht leer ist
	var $wisy_searchinput = $('#wisy_searchinput');
	showHideClear($wisy_searchinput);
	$wisy_searchinput.on('keyup', function() {
		showHideClear($(this));
	});
	$wisy_searchinput.siblings('.clearbtn').on('click', function() {
		$wisy_searchinput.val('');
		showHideClear($wisy_searchinput);
	});
	
	
	// Anbietersuche
	$('#wisy_anbietersuche').on('click', function() {
		var val = $('#wisy_searchinput').val().replace('Fav:,', '');
		if(val.indexOf('Zeige:Anbieter') == -1) {
			$('#wisy_searchinput').val('Zeige:Anbieter, ' + val);
		}
	});
	// Kurssuche
	$('#wisy_kurssuche').on('click', function() {
		$('#wisy_searchinput').val($('#wisy_searchinput').val().
			replace('Fav:,', '').
			replace('Zeige:Anbieter,', '').
			replace('Zeige:Anbieter', ''));
	});
	// Umkreissuche
	$('#wisy_umkreis').on('change', function() {
		// vorherigen umkreis:xxx/yyy und km:zzz löschen
		var search_val = $('#wisy_searchinput').val();
		var re_km = /\s*km:\d{1,2},{0,1}/gi;
		search_val = search_val.replace(re_km, '');
		var re_umkreis = /\s*umkreis:\d*\.\d*\/\d*\.\d*,{0,1}/gi;
		search_val = search_val.replace(re_umkreis, '');
		
		$('#wisy_searchinput').val(search_val);
		
		var km = $(this).val();
		if(km > 0) {
			// Koordinaten ermitteln
			if(typeof(navigator.geolocation) != 'undefined') {
				navigator.geolocation.getCurrentPosition(locationSuccess, locationError, { enableHighAccuracy: false, maximumAge: 60000 });
			}
			var val = $('#wisy_searchinput').val().replace('Fav:,', '');
			$('#wisy_searchinput').val(val + 'km:' + km + ', ');
			// TODO: Feld und Buttons sperren für x Sekunden und Spinner zeigen mit Text "Ortung" oder so
		} else {
			$('#wisy_searcharea form').submit();
		}
		return false;
	});
}

function showHideClear($searchfield) {
	if($searchfield.val() != '') {
		$searchfield.siblings('.clearbtn').show();
	} else {
		$searchfield.siblings('.clearbtn').hide();
	}
}

function locationSuccess(position) {
	var val = $('#wisy_searchinput').val();
	$('#wisy_searchinput').val(val + 'umkreis:' + position.coords.longitude + '/' + position.coords.latitude + ', ');
	$('#wisy_searcharea form').submit();
}

function locationError(error) {
	alert('Es konnte keine Ortung durchgeführt werden. Fehlercode: ' + error.code);
}



// Nach Sortierfolgenauswahl Seite neu laden, Parameter anpassen
function prepareSort() {
	$('.wisy_select_order select').on('change', function() {
		$this = $(this);
		var selectedOrder = $this.val();
		$this.parent('form').submit();
	});
	
	// Auf- oder Absteigend
	$('.wisy_select_order input').on('click', function() {
		$this = $(this);
		var name = $this.attr('name');
		if(name == 'order_dn') {
			// Selectfeldwert auslesen und Name lÃ¶schen, damit es nicht submitted wird
			var $select = $this.siblings('select').first();
			var selectval = $select.val();
			$select.attr('name', '');
			
			// Neues verstecktes Inputfeld mit gleichem Namen und neuem Wert erstellen
			$this.parent('form').append('<input type="hidden" name="order" value="' + selectval + 'd" />');
		}
		
		// Form per Javascript abschicken, damit name des Submitbuttons nicht in GET auftaucht
		$this.parent('form').submit();
		return false;
	});
}

function prepareSwipe() {
	$('body').touchwipe({
     	wipeLeft: function() { swipe('left'); },
     	wipeRight: function() { swipe('right'); },
     	min_move_x: 120,
     	preventDefaultEvents: false
	});
}
function swipe(direction) {
	var $body = $('body');
	
	if($body.hasClass('wisyp_homepage')) {
		// Startseite -> Nichts tun
		
	} else if($body.hasClass('wisyp_search')) {
		// Suchergebnisliste -> Paging vor zurück oder back wenn right auf Seite 1
		if(direction == 'right') {
			if($('.wisy_paginate a.prev').attr('href') != undefined) {
				window.location = $('.wisy_paginate a.prev').attr('href');
			} else {
				window.location = '/';
			}
		} else if($('.wisy_paginate a.next').attr('href') != undefined) {
			window.location = $('.wisy_paginate a.next').attr('href');
		}
		
	} else {
		// Sonstige Seiten (auch Kursseiten) -> history back bei swipe right
		if(direction == 'right') history.back();
	}
	return true;
}

/* Aus jquery.wisy.js */

// cookie - http://archive.plugins.jquery.com/project/Cookie , http://www.electrictoolbox.com/jquery-cookies/
(function(e,t,n){function i(e){return e}function s(e){return decodeURIComponent(e.replace(r," "))}var r=/\+/g;var o=e.cookie=function(r,u,a){if(u!==n){a=e.extend({},o.defaults,a);if(u===null){a.expires=-1}
if(typeof a.expires==="number"){var f=a.expires,l=a.expires=new Date;l.setDate(l.getDate()+f)}u=o.json?JSON.stringify(u):String(u);return t.cookie=[encodeURIComponent(r),"=",o.raw?u:encodeURIComponent(u),
a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":""].join("")}var c=o.raw?i:s;var h=t.cookie.split("; ");for(var p=0,d=h.length;p<d;p++)
{var v=h[p].split("=");if(c(v.shift())===r){var m=c(v.join("="));return o.json?JSON.parse(m):m}}return null};o.defaults={};e.removeCookie=function(t,n){if(e.cookie(t)!==null){e.cookie(t,null,n);return true}return false}})(jQuery,document)


/*****************************************************************************
 * autocomplete stuff
 *****************************************************************************/

function clickAutocompleteHelp(tag_help, tag_name_encoded)
{
	location.href = 'g' + tag_help + '?ie=UTF-8&q=' + tag_name_encoded;
	return false;
}

function clickAutocompleteMore(tag_name_encoded)
{
	location.href = 'search?ie=UTF-8&show=tags&q=' + tag_name_encoded;
}

function formatItem(row)
{
	var tag_name  = row[0];
	var tag_descr = row[1];
	var tag_type  = row[2];
	var tag_help  = row[3];
	var tag_freq  = row[4];
	
	/* see also (***) in the PHP part */
	var row_class   = 'ac_normal';
	var row_prefix  = '';
	var row_postfix = '';

	if( tag_help == 1 )
	{
		/* add the "more" link */
		row_class = 'ac_more';
		tag_name = '<a href="" onclick="return clickAutocompleteMore(&#39;' + encodeURIComponent(tag_name) + '&#39;)">' + tag_descr + '</a>';
	}
	else
	{
		/* base type */
		     if( tag_type &   1 ) { row_class = "ac_abschluss";            row_postfix = 'Abschluss'; }
		else if( tag_type &   2 ) { row_class = "ac_foerderung";           row_postfix = 'F&ouml;rderung'; }
		else if( tag_type &   4 ) { row_class = "ac_qualitaetszertifikat"; row_postfix = 'Qualit&auml;tszertifikat'; }
		else if( tag_type &   8 ) { row_class = "ac_zielgruppe";           row_postfix = 'Zielgruppe'; }
		else if( tag_type &  16 ) { row_class = "ac_abschlussart";         row_postfix = 'Abschlussart'; }
		else if( tag_type & 128 ) { row_class = "ac_thema";                row_postfix = 'Thema'; }
		else if( tag_type & 256 ) { row_class = "ac_anbieter";
									     if( tag_type &  0x10000 )	{ row_postfix = 'Trainer'; }
									else if( tag_type &  0x20000 )	{ row_postfix = 'Beratungsstelle'; }
									else if( tag_type & 0x400000 )	{ row_postfix = 'Anbieterverweis'; }
									else							{ row_postfix = 'Anbieter'; }
								  }
		else if( tag_type & 512 ) { row_class = "ac_ort";                  row_postfix = 'Ort'; }
	
		/* frequency, end base type */
		if( tag_descr != '' )
			row_postfix = tag_descr;
			
		if( tag_freq > 0 )
		{
			row_postfix += row_postfix==''? '' : ', ';
			row_postfix += tag_freq==1? '1 Kurs' : ('' + tag_freq + ' Kurse');
		}

		if( row_postfix != '' )
			row_postfix = ' <span class="ac_tag_type">(' + row_postfix + ')</span> ';

		
		/* additional flags */
		if( tag_type & 0x10000000 )
		{
			row_prefix = '&nbsp; &nbsp; &nbsp; &nbsp; &#8594; ';
			row_class += " ac_indent";
		}	
		else if( tag_type & 0x20000000 )
		{
			row_prefix = 'Meinten Sie: ';
		}
		
		/* help link */
		if( tag_help != 0 )
		{
			/* note: a single semicolon disturbs the highlighter as well as a single quote! */
			row_postfix +=
			 ' <a class="wisy_help" href="" onclick="return clickAutocompleteHelp(' + tag_help + ', &#39;' + encodeURIComponent(tag_name) + '&#39;)">&nbsp;i&nbsp;</a>';
		}
	}
	
	return '<span class="'+row_class+'">' + row_prefix + tag_name + row_postfix + '</span>';
}

function formatResult(row) {
	return row[0].replace(/(<.+?>)/gi, '');
}

function initAutocomplete()
{
	$(".ac_keyword").autocomplete('autosuggest',
	{
		//width: '100%',
		multiple: true,
		matchContains: true,
		matchSubset: false, /* andernfalls wird aus den bisherigen Anfragen versucht eine Liste herzustellen; dies schlaegt dann aber bei unseren Verweisen fehl */
		formatItem: formatItem,
		formatResult: formatResult,
		max: 512,
		scrollHeight: 250,
		selectFirst: false,
		minChars: 3,
		delay: 300
	});
}


/*****************************************************************************
 * maps stuff
 *****************************************************************************/

// initialization state
var gm_initDone = 0; // 1=success, 2=error

// objects used
var gm_map;
var gm_allMarkers= new Array;
var gm_markerInView = 0;

function gm_panToNext()
{	
	// function pans the map to the next marker in gm_allMarkers, loops on end
	// this function may only be called if gm_initPan() has succeeded
	
	gm_markerInView = gm_markerInView+1;
	if( gm_markerInView >= gm_allAdr.length ) gm_markerInView = 0;
	
	if( gm_allMarkers[gm_markerInView] )
	{
		gm_map.panTo(gm_allMarkers[gm_markerInView].getPoint())
		gm_allMarkers[gm_markerInView].openInfoWindowHtml(gm_allDescr[gm_markerInView]);
	}
	else
	{
		var geocoder = new GClientGeocoder();
		geocoder.getLatLng
		(
			gm_allAdr[gm_markerInView],
			function(point)
			{
				if( !point )
				{
					alert(gm_allAdr[gm_markerInView] + ' nicht gefunden.');
				}
				else
				{
					gm_map.panTo(point);
					gm_allMarkers[gm_markerInView] = new GMarker(point);
					gm_allMarkers[gm_markerInView].myDescr = gm_allDescr[gm_markerInView];
					GEvent.addListener(gm_allMarkers[gm_markerInView], 'click', function() {
						this.openInfoWindowHtml(this.myDescr);
					});
					gm_map.addOverlay(gm_allMarkers[gm_markerInView]);
					gm_allMarkers[gm_markerInView].openInfoWindowHtml(gm_allDescr[gm_markerInView]);
				}
			}
		);						
	}
}



function gm_initPan(quality)
{
	// init the pan to one of the three resolution qualities in gm_initAdr
	
	// create a async. geocode
	var geocoder = new GClientGeocoder();
	geocoder.getLatLng
	(
		gm_initAdr[quality],
		function(point)
		{
			// async geocoder event:
			if( !point )
			{
				// geocoding failed ...
				if( quality >= 2 || gm_initAdr[quality+1]=='' )
				{
					// ... nothing found at all
					$('#wisy_map2Anchor').hide();
					$('#wisy_map2').hide();
					gm_initDone = 2; // error
					return;
				}
				else if( quality == 0 )
				{
					// ... try again with fallback #1
					window.setTimeout(function() {gm_initPan(1);}, 500);
					return;
				}
				else if( quality == 1 )
				{
					// ... try again with fallback #2
					window.setTimeout(function() {gm_initPan(2);}, 500);
					return;
				}
			}
			else
			{
				// geocoding succeeded!

				// center the map:
				// we move the center a little bit down to avoid a scrolling when the info window opens;
				// the offset is fine for "street view" (zoom 15)
				var center = new GLatLng(point.lat()+0.0016, point.lng()); 
				gm_map.setCenter(center, gm_initZoom[quality]);
				
				// add a marker at the calculated point
				gm_allMarkers[0] = new GMarker(point);
				gm_allMarkers[0].myDescr = gm_allDescr[0];
				GEvent.addListener(gm_allMarkers[0], 'click', function() {
					this.openInfoWindowHtml(this.myDescr);
				});
				gm_map.addOverlay(gm_allMarkers[0]);
			}

			// done - success
			gm_initDone = 1;
		}
	);
}


// maps inititalization

$.fn.initWisyMap = function()
{
	// init the gm_map and all needed objects, this function is called after the
	// page has loaded completely

	if( typeof(GBrowserIsCompatible) == 'undefined' )
	{
		return;
	}
		
	if( !GBrowserIsCompatible() )
	{
		return;
	}
	
	$(window).unload( function () { GUnload(); } );

	gm_map = new GMap2(document.getElementById("wisy_map2"));
	gm_map.addControl(new GSmallZoomControl());
	gm_initPan(0);	
	
	return;
}

function gm_mapHere()
{
	document.write('<div class="wisy_vcard" id="wisy_map2Anchor"><div class="wisy_vcardtitle">Angebotsort und Umgebung</div><div id="wisy_map2"></div></div>');
}

/*****************************************************************************
 * feedback stuff
 *****************************************************************************/

function ajaxFeedback(rating, descr)
{
	var url = 'feedback?url=' + encodeURIComponent(window.location) + '&rating=' + rating + '&descr=' + encodeURIComponent(descr);
	$.get(url);
}

function describeFeedback()
{
	var descr = $('#wisy_feedback_descr').val();
	descr = $.trim(descr);
	if( descr == '' )
	{
		alert('Bitte geben Sie zuerst Ihren Kommentar ein.');
	}
	else
	{
		$('#wisy_feedback_line2').html('<strong style="color: green;">Vielen Dank f&uuml;r Ihren Kommentar!</strong>');
		ajaxFeedback(0, descr); // Kommentar zur Bewertung hinzufügen; die Bewertung selbst (erster Parameter) wird an dieser Stelle ignoriert!
	}
}

function sendFeedback(rating)
{
	$('#wisy_feedback_yesno').html('<strong style="color: green;">Vielen Dank f&uuml;r Ihr Feedback!</strong>');
	
	if( rating == 0 )
	{
		$('#wisy_feedback_line1').after(
				'<p id="wisy_feedback_line2">'
			+		'Bitte schildern Sie uns noch kurz, warum diese Information nicht hilfreich war und was wir besser machen k&ouml;nnen:<br />'
				+	'<textarea id="wisy_feedback_descr" name="wisy_feedback_descr" rows="4" cols="20" style="width: 240px;"></textarea><br />'
				+	'<input type="submit" onclick="describeFeedback(); return false;" value="Kommentar senden" />'
			+	'</p>'
		);
		$('#wisy_feedback_descr').focus();
	}
	else 
	{
		$('#wisy_feedback_line1').after(
				'<p id="wisy_feedback_line2">'
			+		'Bitte schildern Sie uns kurz, was hilfreich war, damit wir Bew&auml;hrtes bewahren und ausbauen:<br />'
				+	'<textarea id="wisy_feedback_descr" name="wisy_feedback_descr" rows="4" cols="20" style="width: 240px;"></textarea><br />'
				+	'<input type="submit" onclick="describeFeedback(); return false;" value="Kommentar senden" />'
			+	'</p>'
		);
		$('#wisy_feedback_descr').focus();
	}
	
	ajaxFeedback(rating, '');
}

function initFeedback()
{
	if($('body.wisyp_homepage').length == 0)
	{
		$('.wisy_allow_feedback').after(
			'<div class="wisy_feedback"><p id="wisy_feedback_line1" class="noprint"><strong>War diese Information hilfreich?</strong> '
		+		'<span id="wisy_feedback_yesno" class="clearfix"><a href="javascript:sendFeedback(1)">Ja</a><a href="javascript:sendFeedback(0)">Nein</a></span>'
		+	'</p></div>'
		);
	}
}

/*****************************************************************************
 * fav stuff
 *****************************************************************************/

 
 
var g_all_fav = {};
function fav_count()
{	
	var cnt = 0;
	for( var key in g_all_fav ) {
		if( g_all_fav[ key ] )
			cnt ++;
	}
	return cnt;
}
function fav_is_favourite(id)
{
	return g_all_fav[ id ]? true : false;
}
function fav_set_favourite(id, state)
{
	g_all_fav[ id ] = state;
	fav_save_cookie();
}
function fav_save_cookie()
{
	var str = '';
	for( var key in g_all_fav ) {
		if( g_all_fav[ key ] ) {
			str += str==''? '' : ',';
			str += key;
		}
	}
	$.cookie('fav', str, { expires: 30 }); // expires in 30 days
}


function fav_update_bar()
{	
	var cnt = fav_count();
	if( cnt > 0 )
	{	
		$('#wisy_favs .fav_count').html(cnt + (cnt==1? ' Favorit' : ' Favoriten'));
		$('#wisy_favs').show();
		
		// Update favprint mailto Link with list of all favorites
		var url = $('#wisy_fav_mailprint').attr('href');
		for( var key in g_all_fav ) {
			if( g_all_fav[ key ] ) {
				url += key + '/';
			}
		}
		$('#wisy_fav_mailprint').attr('href', url);
		
		fav_resize();
	}
	else
	{
		$('#wisy_favs').hide();
	}
}
function fav_resize() {
	var $favs_anzeigen = $('#wisy_favs_anzeigen');
	var rest_width =  $('#wisy_favs').outerWidth(true) - $('#wisy_fav_mailprint').outerWidth(true) - $('#wisy_fav_mailprint').outerWidth(true);
	var el_width = rest_width - parseInt($favs_anzeigen.css('padding-left').replace('px', '')) - parseInt($favs_anzeigen.css('padding-right').replace('px', ''));
	$favs_anzeigen.css('width', el_width + 'px');
}



function fav_click(jsObj, id)
{
	jqObj = $(jsObj);
	if( jqObj.hasClass('fav_selected') ) {
		jqObj.removeClass('fav_selected');
		fav_set_favourite(id, false);
		fav_update_bar();
	}
	else {
		jqObj.addClass('fav_selected');
		fav_set_favourite(id, true);
		fav_update_bar();
		
		if( $.cookie('fav_init_hint') != 1 ) {
			alert('Ihr Favorit wurde auf diesem Computer gespeichert. Um alle Favoriten anzuzeigen, klicken Sie auf das entsprechende Symbol beim Suchfeld.');
			$.cookie('fav_init_hint', 1, { expires: 30 }); 
		}
	}
}
function fav_delete_all()
{
	if( !confirm('Alle gespeicherten Favoriten löschen?') )
		return false;
	
	g_all_fav = {};
	fav_save_cookie();
	fav_update_bar();
	$('.fav_selected').removeClass('fav_selected');
}

function fav_init()
{
	// read favs from cookie (exp. '3501,3554')
	var temp = $.cookie('fav');
	if( typeof temp == 'string' ) {
		temp = temp.split(',');
		for( var i = 0; i < temp.length; i++ ) {
			var id = parseInt(temp[i], 10);
			if( !isNaN(id) && id > 0 ) {
				g_all_fav[ id ] = true;
			}
		}
	}
	
	// prepare the page
	$('.fav_add').each(function() {
		var id = $(this).attr('data-favid');
		var cls = fav_is_favourite(id)? 'fav_item fav_selected' : 'fav_item';
		$(this).parent().append(' <span class="'+cls+'" onclick="fav_click(this, '+id+');" title="Angebot merken">&#9733;</span>');
	});
	
	if( fav_count() ) {
		fav_update_bar();
	}
	
	// Resize Fav Bar
	$(window).on('resize', function() {
		clearTimeout(fav_resize_timer);
		fav_resize_timer = setTimeout(fav_resize, 50);
	});
}

/*****************************************************************************
 * LIBs
 *****************************************************************************/

/**
 * jQuery Plugin to obtain touch gestures from iPhone, iPod Touch and iPad, should also work with Android mobile phones (not tested yet!)
 * Common usage: wipe images (left and right to show the previous or next image)
 * 
 * @author Andreas Waltl, netCU Internetagentur (http://www.netcu.de)
 * @version 1.1.1 (9th December 2010) - fix bug (older IE's had problems)
 * @version 1.1 (1st September 2010) - support wipe up and wipe down
 * @version 1.0 (15th July 2010)
 */
(function($){$.fn.touchwipe=function(settings){var config={min_move_x:20,min_move_y:20,wipeLeft:function(){},wipeRight:function(){},wipeUp:function(){},wipeDown:function(){},preventDefaultEvents:true};if(settings)$.extend(config,settings);this.each(function(){var startX;var startY;var isMoving=false;function cancelTouch(){this.removeEventListener('touchmove',onTouchMove);startX=null;isMoving=false}function onTouchMove(e){if(config.preventDefaultEvents){e.preventDefault()}if(isMoving){var x=e.touches[0].pageX;var y=e.touches[0].pageY;var dx=startX-x;var dy=startY-y;if(Math.abs(dx)>=config.min_move_x){cancelTouch();if(dx>0){config.wipeLeft()}else{config.wipeRight()}}else if(Math.abs(dy)>=config.min_move_y){cancelTouch();if(dy>0){config.wipeDown()}else{config.wipeUp()}}}}function onTouchStart(e){if(e.touches.length==1){startX=e.touches[0].pageX;startY=e.touches[0].pageY;isMoving=true;this.addEventListener('touchmove',onTouchMove,false)}}if('ontouchstart'in document.documentElement){this.addEventListener('touchstart',onTouchStart,false)}});return this}})(jQuery);