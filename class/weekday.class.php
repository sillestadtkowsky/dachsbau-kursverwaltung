<?php
class TT_Weekday extends TT_Post
{
	public static function GetDefaultFetchArgs()
	{
		$defaults = array(
			'post_type' => 'timetable_weekdays',
		);
		$parent_defaults = parent::GetDefaultFetchArgs();
		$defaults += $parent_defaults;		
		return $defaults;
	}
	
	protected static function GetDefaultCreateArgs()
	{
		$defaults = array(
			'post_type' => 'timetable_weekdays',
		);
		$parent_defaults = parent::GetDefaultCreateArgs();
		$defaults += $parent_defaults;
		return $defaults;
	}

	/*
	*
	* @autor Silvio Osowsky
	* Die Funktion so_getDayForWeek erhält als Parameter einen Wochentag in der Form eines zweistelligen Strings, z.B. "Mo" für Montag oder "Di" für Dienstag. 
	* Die Funktion berechnet dann das Datum des nächsten Auftretens dieses Wochentags ausgehend vom aktuellen Datum und gibt dieses im Format "TT.MM.JJJJ" zurück.
	* Zunächst wird die Zeitzone auf "Europe/Berlin" gesetzt und dann ein DateTime-Objekt für das aktuelle Datum und eins für das Ziel-Datum erstellt. 
	* Das Ziel-Datum wird mithilfe der setISODate()-Methode gesetzt, die Jahr, Woche und Wochentag annimmt und das Datum des entsprechenden Tages zurückgibt.
	* Anschließend wird geprüft, ob das Ziel-Datum heute ist und falls ja, wird das aktuelle Datum im Format "TT.MM.JJJJ" zurückgegeben. 
	* Falls das Ziel-Datum noch in dieser Woche liegt, wird das Datum des Ziel-Tages im Format "TT.MM.JJJJ" zurückgegeben.
	* Wenn der Ziel-Tag bereits in dieser Woche vorbei ist, wird das Datum des Ziel-Tages in der nächsten Woche zurückgegeben
	*
	*/

	public static function so_getDayForWeek($weekday) {  
		$weekDayLower = strtolower($weekday);
		date_default_timezone_set('Europe/Berlin');
		$today = so_getDayToday();
		$target_day = so_getDayToday();
		$target_day->setISODate($today->format('Y'), $today->format('W'), array_search(substr($weekDayLower,0,2), ['mo', 'di', 'mi', 'do', 'fr', 'sa', 'so']) + 1);
		
		if ($today->format('N') == $target_day->format('N')) {
			// Target day is today
			$next_date = $today->format('d.m.Y');
		} elseif ($today->format('N') < $target_day->format('N')) {
			// Target day is still in the current week
			$next_date = $target_day->format('d.m.Y');
		} else {
			// Target day has already passed in the current week, get the date for next week
			$target_day->modify('+1 week');
			$next_date = $target_day->format('d.m.Y');
		}
		
		return $next_date;
	}

}