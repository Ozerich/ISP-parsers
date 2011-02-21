<?php

/*********************************************************************/

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';
require_once PARSERS_BASE_DIR . '/parsers/addons/simple_html_dom.php';

/*********************************************************************/

/* В этот класс записываются все свои функции, которые в будуещем понадобятся для 
 * скачивания других сайтов 
 * */
abstract class ItemsSiteParser_Lyxsus extends ItemsSiteParser_Drakon
{ 
	// get html dom form file
	static function file_get_html() {
	    $dom = new simple_html_dom;
	    $args = func_get_args();
	    $dom->load(call_user_func_array('file_get_contents', $args), true);
	    return $dom;
	}

	// get html dom form string
	static function str_get_html($str, $lowercase=true) {
	    $dom = new simple_html_dom;
	    $dom->load($str, $lowercase);
	    return $dom;
	}

	// dump html dom tree


	// get dom form file (deprecated)
	static function file_get_dom() {
	    $dom = new simple_html_dom;
	    $args = func_get_args();
	    $dom->load(call_user_func_array('file_get_contents', $args), true);
	    return $dom;
	}

	// get dom form string (deprecated)
	static function str_get_dom($str, $lowercase=true) {
	    $dom = new simple_html_dom;
	    $dom->load($str, $lowercase);
	    return $dom;
	}

	public function txt($text)
	{
		$text = str_replace(array("<BR>", '&nbsp;', "&laquo;", "&raquo", "&quot;", "&ndash;","&mdash;"), array("\n",' ','"','"','"','-',"-"), $text);
		$text = strip_tags($text);
		$text = trim($text);
		return $text;
	}
	
	public function getText ($str)
	{
		return trim(html_entity_decode(strip_tags($str), ENT_COMPAT, "utf-8" )) ;
	}
}

/*********************************************************************/
