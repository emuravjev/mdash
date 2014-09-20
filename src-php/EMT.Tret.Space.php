<?php
/**
 * @see EMT_Tret
 */
require_once('EMT.Tret.php');

class EMT_Tret_Space extends EMT_Tret
{
	public $title = "Расстановка и удаление пробелов";
	
	public $domain_zones = array('ru','ру','ком','орг', 'уа', 'ua', 'uk', 'co', 'fr', 
	                             'com', 'net', 'edu', 'gov', 'org', 'mil', 'int', 'info', 'biz', 'info', 'name', 'pro');
	
	public $classes = array(
			'nowrap'           => 'word-spacing:nowrap;',
			);
	
	public $rules = array(
		'nobr_twosym_abbr' => array(
				'description'	=> 'Неразрывный перед 2х символьной аббревиатурой',
				'pattern' 		=> '/([a-zA-Zа-яёА-ЯЁ])(\040|\t)+([A-ZА-ЯЁ]{2})([\s\;\.\?\!\:\(\"]|\&(ra|ld)quo\;|$)/u', 
				'replacement' 	=> '\1&nbsp;\3\4'
			),
		'remove_space_before_punctuationmarks' => array(
				'description'	=> 'Удаление пробела перед точкой, запятой, двоеточием, точкой с запятой',
				'pattern' 		=> '/((\040|\t|\&nbsp\;)+)([\,\:\.\;\?])(\s+|$)/', 
				'replacement' 	=> '\3\4'
			),					
		'autospace_after_comma' => array(
				'description'	=> 'Пробел после запятой',
				'pattern' 		=> array(
						'/(\040|\t|\&nbsp\;)\,([а-яёa-z0-9])/iu', 
						'/([^0-9])\,([а-яёa-z0-9])/iu', 
						),
				'replacement' 	=> array(
						', \2',
						'\1, \2'
						),
			),
		'autospace_after_pmarks' => array(
				'description'	=> 'Пробел после знаков пунктуации, кроме точки',
				'pattern' 		=> '/(\040|\t|\&nbsp\;|^|\n)([a-zа-яё0-9]+)(\040|\t|\&nbsp\;)?(\:|\)|\,|\&hellip\;|(?:\!|\?)+)([а-яёa-z])/iu', 
				'replacement' 	=> '\1\2\4 \5'
			),	
		'autospace_after_dot' => array(
				'description'	=> 'Пробел после точки',
				'pattern' 		=> array(
						'/(\040|\t|\&nbsp\;|^)([a-zа-яё0-9]+)(\040|\t|\&nbsp\;)?\.([а-яёa-z]{5,})($|[^a-zа-яё])/iue', 
						'/(\040|\t|\&nbsp\;|^)([a-zа-яё0-9]+)\.([а-яёa-z]{1,4})($|[^a-zа-яё])/iue', 
						),
				'replacement' 	=> array(
						//'\1\2. \4',
						'$m[1].$m[2]."." .( $m[5] == "." ? "" : " ").$m[4].$m[5]',
						'$m[1].$m[2]."." .(in_array(EMT_Lib::strtolower($m[3]), $this->domain_zones)? "":( $m[4] == "." ? "" : " ")). $m[3].$m[4]'
						),
			),	
		'autospace_after_hellips' => array(
				'description'	=> 'Пробел после знаков троеточий с вопросительным или восклицательными знаками',
				'pattern' 		=> '/([\?\!]\.\.)([а-яёa-z])/iu', 
				'replacement' 	=> '\1 \2'
			),	
		'many_spaces_to_one' => array(
				'description'	=> 'Удаление лишних пробельных символов и табуляций',
				'pattern' 		=> '/(\040|\t)+/', 
				'replacement' 	=> ' '
			),
		'clear_percent' => array(
				'description'	=> 'Удаление пробела перед символом процента',
				'pattern' 		=> '/(\d+)([\t\040]+)\%/', 
				'replacement' 	=> '\1%'
			),
		'nbsp_before_open_quote' => array(
				'description'	=> 'Неразрывный пробел перед открывающей скобкой',
				'pattern' 		=> '/(^|\040|\t|>)([a-zа-яё]{1,2})\040(\&laquo\;|\&bdquo\;)/u', 
				'replacement' 	=> '\1\2&nbsp;\3'
			),
		
		'nbsp_before_month'     => array(
				'description'	=> 'Неразрывный пробел в датах перед числом и месяцем',			
				'pattern' 		=> '/(\d)(\s)+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)([^\<]|$)/iu',
				'replacement' 	=> '\1&nbsp;\3\4'
			),
		'spaces_on_end'     => array(
				'description'	=> 'Удаление пробелов в конце текста',			
				'pattern' 		=> '/ +$/',
				'replacement' 	=> ''
			),
		'no_space_posle_hellip' => array(
				'description'	=> 'Отсутстввие пробела после троеточия после открывающей кавычки',
				'pattern' 		=> '/(\&laquo\;|\&bdquo\;)( |\&nbsp\;)?\&hellip\;( |\&nbsp\;)?([a-zа-яё])/ui', 
				'replacement' 	=> '\1&hellip;\4'
			),
		'space_posle_goda' => array(
				'description'	=> 'Пробел после года',
				'pattern' 		=> '/(^|\040|\&nbsp\;)([0-9]{3,4})(год([ауе]|ом)?)([^a-zа-яё]|$)/ui', 
				'replacement' 	=> '\1\2 \3\5'
			),
		);
}

?>