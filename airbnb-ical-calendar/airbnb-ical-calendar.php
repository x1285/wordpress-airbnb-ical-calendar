<?php
/**
 * @package Airbnb-ical-calendar
 * @version 0.0.1
 * @license https://opensource.org/licenses/mit-license.php MIT License
 */
/*
Plugin Name: Airbnb ICal Calendar
Plugin URI: https://wosnitzka.info/projects/wordpress-plugin-airbnb-ical-calendar.php
Description: This plugin adds a simple calendar for your airbnb to your page.
Author: Maik Wosnitzka
Version: 0.0.1
Author URI: https://wosnitzka.info
*/

require_once('cacheable-ical/CacheableIcal.class.php');

function airbnb_ical_calender_setUpScripts() {
	wp_enqueue_style( 'airbnb_ical_calender_style', plugins_url('/vanilla-calendar/vanilla-calendar.min.css', __FILE__) );
	wp_enqueue_style( 'airbnb_ical_calender_style_light', plugins_url('/vanilla-calendar/light.min.css', __FILE__) );
	wp_enqueue_style( 'airbnb_ical_calender_style_extra', plugins_url('/airbnb_ical_calender.css', __FILE__) );
	wp_enqueue_script('airbnb_ical_calender_script', plugins_url('/vanilla-calendar/vanilla-calendar.min.js', __FILE__) );
}
add_action('wp_enqueue_scripts', 'airbnb_ical_calender_setUpScripts');

function airbnb_ical_calender_createHtml($icalUrl, $months, $allowSelection) {
	$cachableIcal = new CacheableIcal($icalUrl);
	$ical = $cachableIcal->getIcal();
	 $events = $ical->sortEventsWithOrder($ical->events());

	$halfDatesStart = [];
	$halfDatesEnd = [];
	$jsDisabledDates = "";
	$eventsCount = count($events);
    for ($i=0; $i < $eventsCount; $i++) {
    	$event = $events[$i];
        $start = $ical->iCalDateToDateTime($event->dtstart_array[3]);
        $end = $ical->iCalDateToDateTime($event->dtend_array[3]);

        $prevEventEnd = $i-1 >= 0 
        		? $ical->iCalDateToDateTime($events[$i-1]->dtend_array[3])
        		: null;

    	if ($prevEventEnd != $start) {
    		$halfDatesStart[] = "'".$start->format('Y-m-d')."'";
    	}
    	$firstBlockedStartDate = $prevEventEnd == $start ? $start : $start->modify("+1 day");
        
        $nextEventStart = $i+1 >= $eventsCount 
        		? null 
        		: $ical->iCalDateToDateTime($events[$i+1]->dtstart_array[3]);

    	if ($nextEventStart != $end) { 
    		$halfDatesEnd[] = "'".$end->format('Y-m-d')."'";
    	}
		$lastBlockedEndDate = $nextEventStart == $end ? $end : $end->modify("-1 day");

		$startString = $firstBlockedStartDate->format('Y-m-d');
		$endString = $lastBlockedEndDate->format('Y-m-d');
		$jsDisabledDates .= "'".$startString.":".$endString."'";
		if ($i < ($eventsCount-1)) {
			$jsDisabledDates .= ", ";
		}
    }
	$jsHalfDatesStart = "";
    foreach ($halfDatesStart as $date) {
		$jsHalfDatesStart .= $date.": { modifier: 'half-start', },";
	}
	$jsHalfDatesEnd = "";
    foreach ($halfDatesEnd as $date) {
		$jsHalfDatesEnd .= $date.": { modifier: 'half-end', },";
	}
    return "<div id=\"calendar\"></div>
    		<script>
			document.addEventListener('DOMContentLoaded', () => {
				const today = new Date();
				const options = {
					type: 'multiple',
					months: ".$months.",
					jumpMonths: 1,
					settings: {
						range: {
							disablePast: true,
							disabled: [".$jsDisabledDates."],
							disableGaps: true,
						},
						visibility: {
							theme: 'light',
						},
						lang: 'DE',
						selection: {
							day: ".($allowSelection == true ? "'multiple-ranged'" : "false").",
						},
					},
					popups: { ".$jsHalfDatesStart.$jsHalfDatesEnd." }
				};

				const calendar = new VanillaCalendar('#calendar', options);
				calendar.init();
			});
		</script>";
}
function airbnb_ical_calender_shortcode( $atts = [], $content = null, $tag = '' ) {
	$icalUrl = $atts["ical"];
	$atts = shortcode_atts(
		array(
			'months' => '3',
			'allowSelection' => 'true',
		), $atts, $tag
	);
	$months = $atts["months"];
	$allowSelection = $atts["allowSelection"] === "true" ? true : false;
	return airbnb_ical_calender_createHtml($icalUrl, $months, $allowSelection);
}
add_shortcode( 'airbnb_ical_calender', 'airbnb_ical_calender_shortcode' );