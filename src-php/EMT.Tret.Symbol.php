<?php
/**
 * @see EMT_Tret
 */
require_once('EMT.Tret.php');

class EMT_Tret_Symbol extends EMT_Tret
{
	/**
	 * Базовые параметры тофа
	 *
	 * @var array
	 */
	public $classes = array(
			'nowrap'           => 'word-spacing:nowrap;',
		);
	
	
	public $title = "Специальные символы";
	public $rules = array(	
		'tm_replace' => array(
				'description'	=> 'Замена (tm) на символ торговой марки',
				'pattern' 		=> '/([\040\t])?\(tm\)/i', 
				'replacement' 	=> '&trade;'
			),
		'r_sign_replace' => array(
				'description'	=> 'Замена (R) на символ зарегистрированной торговой марки',
				'pattern' 		=> array(
					'/(.|^)\(r\)(.|$)/ie', 
					//'/([^\>]|^)\(r\)([^\<]|$)/ie', 
					//'/\>\(r\)\</i', 
					),
				'replacement' 	=> array(
					//'$m[1].$this->tag("&reg;", "sup").$m[2]',
					'$m[1]."&reg;".$m[2]',
					//'>&reg;<'
					),
			),
		'copy_replace' => array(
				'description'	=> 'Замена (c) на символ копирайт',
				'pattern' 		=> array(
							'/\((c|с)\)\s+/iu', 
							'/\((c|с)\)($|\.|,|!|\?)/iu', 
							),
				'replacement' 	=> array(
							'&copy;&nbsp;',
							'&copy;\2',
							),
			),		
		'apostrophe' => array(
				'description'	=> 'Расстановка правильного апострофа в текстах',
				'pattern' 		=> '/(\s|^|\>|\&rsquo\;)([a-zа-яё]{1,})\'([a-zа-яё]+)/ui',
				'replacement' 	=> '\1\2&rsquo;\3',
				'cycled'		=> true
			),
			/*
		'ru_apostrophe' => array(
				'description'	=> 'Расстановка правильного апострофа в русских текстах',
				'pattern' 		=> '/(\s|^|\>)([а-яё]+)\'([а-яё]+)/iu',
				'replacement' 	=> '\1\2&rsquo;\3'
			),
			*/
		'degree_f' => array(
				'description'	=> 'Градусы по Фаренгейту',
				'pattern' 		=> '/([0-9]+)F($|\s|\.|\,|\;|\:|\&nbsp\;|\?|\!)/eu',
				'replacement' 	=> '"".$this->tag($m[1]." &deg;F","span", array("class"=>"nowrap")) .$m[2]'
			),
		'euro_symbol' => array(
				'description'	=> 'Символ евро',
				'simple_replace' => true,
				'pattern' 		=> '€',
				'replacement' 	=> '&euro;'
			),
		'arrows_symbols' => array(
				'description'	=> 'Замена стрелок вправо-влево на html коды',
				'pattern' 		=> array('/(\s|\>|\&nbsp\;|^)\-\>($|\s|\&nbsp\;|\<)/', '/(\s|\>|\&nbsp\;|^|;)\<\-(\s|\&nbsp\;|$)/', '/→/u', '/←/u'),
				'replacement' 	=> array('\1&rarr;\2', '\1&larr;\2', '&rarr;', '&larr;' )
			),			
		);
}

?>