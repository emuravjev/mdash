<?php
/**
 * @see EMT_Tret
 */
require_once('EMT.Tret.php');

class EMT_Tret_Punctmark extends EMT_Tret
{
	public $title = "Пунктуация и знаки препинания";
	
	public $rules = array( 
	 	'auto_comma' => array(
	 			'description'	=> 'Расстановка запятых перед а, но',
		 		'pattern' 		=> '/([a-zа-яё])(\s|&nbsp;)(но|а)(\s|&nbsp;)/iu',
		 		'replacement' 	=> '\1,\2\3\4'
	 		), 
		'punctuation_marks_limit' => array(
				'description'	=> 'Лишние восклицательные, вопросительные знаки и точки',
				'pattern' 		=> '/([\!\.\?]){4,}/', 
				'replacement' 	=> '\1\1\1'
			), 	
		'punctuation_marks_base_limit' => array(
				'description'	=> 'Лишние запятые, двоеточия, точки с запятой',
				'pattern' 		=> '/([\,]|[\:]|[\;]]){2,}/',
				'replacement' 	=> '\1'
			),
		'hellip' => array(
				'description'	=> 'Замена трех точек на знак многоточия',
				'simple_replace'=> true,
				'pattern' 		=> '...',
				'replacement'   => '&hellip;'
			),		
		'fix_excl_quest_marks' => array(
				'description'	=> 'Замена восклицательного и вопросительного знаков местами',
				'pattern' 		=> '/([a-zа-яё0-9])\!\?(\s|$|\<)/ui',
				'replacement' 	=> '\1?!\2'
			),
		'fix_pmarks' => array(
				'description'	=> 'Замена сдвоенных знаков препинания на одинарные',
				'pattern' 		=> array(
							'/([^\!\?])\.\./',
							'/([a-zа-яё0-9])(\!|\.)(\!|\.|\?)(\s|$|\<)/ui', 
							'/([a-zа-яё0-9])(\?)(\?)(\s|$|\<)/ui',
							),
				'replacement' 	=> array(
							'\1.',
							'\1\2\4',
							'\1\2\4'
							),
			),
		'fix_brackets' => array(
				'description'	=> 'Лишние пробелы после открывающей скобочки и перед закрывающей',
				'pattern' 		=> array('/(\()(\040|\t)+/', '/(\040|\t)+(\))/'),
				'replacement' 	=> array('\1', '\2')
			),
		'fix_brackets_space' => array(
				'description'	=> 'Пробел перед открывающей скобочкой',
				'pattern' 		=> '/([a-zа-яё])(\()/iu',
				'replacement' 	=> '\1 \2'
			),			
		'dot_on_end' => array(
				'description'	=> 'Точка в конце текста, если её там нет',
				'disabled'      => true,				
				'pattern' 		=> '/([a-zа-яё0-9])(\040|\t|\&nbsp\;)*$/ui',
				//'pattern' 		=> '/(([^\.\!\?])|(&(ra|ld)quo;))$/',
				'replacement' 	=> '\1.'
			),
			
		);
}

?>