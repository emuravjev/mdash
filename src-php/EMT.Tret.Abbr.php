<?php
/**
 * @see EMT_Tret
 */
require_once('EMT.Tret.php');

class EMT_Tret_Abbr extends EMT_Tret
{
	public $title = "Сокращения";
	
	public $domain_zones = array('ru','ру','com','ком','org','орг', 'уа', 'ua');
	
	public $classes = array(
			'nowrap'           => 'word-spacing:nowrap;',
			);
	
	public $rules = array(
		'nobr_abbreviation' => array(
				'description'	=> 'Расстановка пробелов перед сокращениями dpi, lpi',
				'pattern' 		=> '/(\s+|^|\>)(\d+)(\040|\t)*(dpi|lpi)([\s\;\.\?\!\:\(]|$)/i', 
				'replacement' 	=> '\1\2&nbsp;\4\5'
			),
		'nobr_acronym' => array(
				'description'	=> 'Расстановка пробелов перед сокращениями гл., стр., рис., илл., ст., п.',
				'pattern' 		=> '/(\s|^|\>|\()(гл|стр|рис|илл?|ст|п|с)\.(\040|\t)*(\d+)(\&nbsp\;|\s|\.|\,|\?|\!|$)/iu', 
				'replacement' 	=> '\1\2.&nbsp;\4\5'
			),		
		'nobr_sm_im' => array(
				'description'	=> 'Расстановка пробелов перед сокращениями см., им.',
				'pattern' 		=> '/(\s|^|\>|\()(см|им)\.(\040|\t)*([а-яё0-9a-z]+)(\s|\.|\,|\?|\!|$)/iu', 
				'replacement' 	=> '\1\2.&nbsp;\4\5'
			),	
		'nobr_locations' => array(
				'description'	=> 'Расстановка пробелов в сокращениях г., ул., пер., д.',
				'pattern' 		=> array(
						'/(\s|^|\>)(г|ул|пер|просп|пл|бул|наб|пр|ш|туп)\.(\040|\t)*([а-яё0-9a-z]+)(\s|\.|\,|\?|\!|$)/iu', 
						'/(\s|^|\>)(б\-р|пр\-кт)(\040|\t)*([а-яё0-9a-z]+)(\s|\.|\,|\?|\!|$)/iu', 
						'/(\s|^|\>)(д|кв|эт)\.(\040|\t)*(\d+)(\s|\.|\,|\?|\!|$)/iu', 
						),
				'replacement' 	=> array(
						'\1\2.&nbsp;\4\5',
						'\1\2&nbsp;\4\5',
						'\1\2.&nbsp;\4\5',
						)
			),		
		'nbsp_before_unit' => array(
				'description'	=> 'Замена символов и привязка сокращений в размерных величинах: м, см, м2…',
				'pattern' 		=> array(
							'/(\s|^|\>|\&nbsp\;|\,)(\d+)( |\&nbsp\;)?(м|мм|см|дм|км|гм|km|dm|cm|mm)(\s|\.|\!|\?|\,|$|\&plusmn\;|\;|\<)/iu', 
							'/(\s|^|\>|\&nbsp\;|\,)(\d+)( |\&nbsp\;)?(м|мм|см|дм|км|гм|km|dm|cm|mm)([32]|&sup3;|&sup2;)(\s|\.|\!|\?|\,|$|\&plusmn\;|\;|\<)/iue'
							),
				'replacement' 	=> array(
							'\1\2&nbsp;\4\5',
							'$m[1].$m[2]."&nbsp;".$m[4].($m[5]=="3"||$m[5]=="2"? "&sup".$m[5].";" : $m[5] ).$m[6]'
							),
			),
		'nbsp_before_weight_unit' => array(
				'description'	=> 'Замена символов и привязка сокращений в весовых величинах: г, кг, мг…',
				'pattern' 		=> '/(\s|^|\>|\&nbsp\;|\,)(\d+)( |\&nbsp\;)?(г|кг|мг|т)(\s|\.|\!|\?|\,|$|\&nbsp\;|\;)/iu', 
				'replacement' 	=> '\1\2&nbsp;\4\5',
			),						
		'nobr_before_unit_volt' => array(
				'description'	=> 'Установка пробельных символов в сокращении вольт',
				'pattern' 		=> '/(\d+)([вВ]| В)(\s|\.|\!|\?|\,|$)/u', 
				'replacement' 	=> '\1&nbsp;В\3'
			),				
		'ps_pps' => array(
				'description'	=> 'Объединение сокращений P.S., P.P.S.',
				'pattern' 		=> '/(^|\040|\t|\>|\r|\n)(p\.\040?)(p\.\040?)?(s\.)([^\<])/ie',
				'replacement' 	=> '$m[1] . $this->tag(trim($m[2]) . " " . ($m[3] ? trim($m[3]) . " " : ""). $m[4], "span",  array("class" => "nowrap") ).$m[5] '
			),	
		'nobr_vtch_itd_itp'     => array(
				'description'	=> 'Объединение сокращений и т.д., и т.п., в т.ч.',			
				'cycled'		=> true,
				'pattern' 		=> array(
						'/(^|\s|\&nbsp\;)и( |\&nbsp\;)т\.?[ ]?д(\.|$|\s|\&nbsp\;)/ue',
						'/(^|\s|\&nbsp\;)и( |\&nbsp\;)т\.?[ ]?п(\.|$|\s|\&nbsp\;)/ue',
						'/(^|\s|\&nbsp\;)в( |\&nbsp\;)т\.?[ ]?ч(\.|$|\s|\&nbsp\;)/ue',						
					),
				'replacement' 	=> array(
						'$m[1].$this->tag("и т. д.", "span",  array("class" => "nowrap")).($m[3]!="."? $m[3] : "" )',
						'$m[1].$this->tag("и т. п.", "span",  array("class" => "nowrap")).($m[3]!="."? $m[3] : "" )',
						'$m[1].$this->tag("в т. ч.", "span",  array("class" => "nowrap")).($m[3]!="."? $m[3] : "" )',
					)
			),
		'nbsp_te'     => array(
				'description'	=> 'Обработка т.е.',			
				'pattern' 		=> '/(^|\s|\&nbsp\;)([тТ])\.?[ ]?е\./ue',
				'replacement' 	=> '$m[1].$this->tag($m[2].". е.", "span",  array("class" => "nowrap"))',
			),
		'nbsp_money_abbr' => array(
				'description'	=> 'Форматирование денежных сокращений (расстановка пробелов и привязка названия валюты к числу)',
				'pattern' 		=> '/(\d)((\040|\&nbsp\;)?(тыс|млн|млрд)\.?(\040|\&nbsp\;)?)?(\040|\&nbsp\;)?(руб\.|долл\.|евро|€|&euro;|\$|у[\.]? ?е[\.]?(\s|$))/ieu', 
				'replacement' 	=> '$m[1].($m[4]?"&nbsp;".$m[4].($m[4]=="тыс"?".":""):"")."&nbsp;".(!preg_match("#у[\\\\.]? ?е[\\\\.]?#iu",$m[7])?$m[7]:"у.е.")',
				'replacement_python' => 'm.group(1)+(u"&nbsp;"+m.group(4)+(u"." if m.group(4)==u"тыс" else u"") if m.group(4) else u"")+u"&nbsp;"+(m.group(7) if not re.match(u"у[\\\\.]? ?е[\\\\.]?",m.group(7),re.I | re.U) else u"у.е.")'
				//'replacement_py' => 'm.group(1)+(\"&nbsp;\"+m.group(4)+(m.group(4)==\"\u0442\u044b\u0441\"?\".\" if m.group(4) else \"\"):\"\")+\"&nbsp;\"+(m.group(7) if !preg_match(\"#\u0443[\\\\.]? ?\u0435[\\\\.]?#iu\",m.group(7)) else \"\u0443.\u0435.\")'
			),
		'nbsp_money_abbr_rev' => array(
				'description'	=> 'Привязка валюты к числу спереди',
				'pattern' 		=> '/(€|&euro;|\$)\s?(\d)/iu', 
				'replacement' 	=> '\1&nbsp;\2'
			),
		'nbsp_org_abbr' => array(
				'description'	=> 'Привязка сокращений форм собственности к названиям организаций',
				'pattern' 		=> '/([^a-zA-Zа-яёА-ЯЁ]|^)(ООО|ЗАО|ОАО|НИИ|ПБОЮЛ) ([a-zA-Zа-яёА-ЯЁ]|\"|\&laquo\;|\&bdquo\;|<)/u', 
				'replacement' 	=> '\1\2&nbsp;\3'
			),
		'nobr_gost' => array(
				'description'	=> 'Привязка сокращения ГОСТ к номеру',
				'pattern' 		=> array(
						'/(\040|\t|\&nbsp\;|^)ГОСТ( |\&nbsp\;)?(\d+)((\-|\&minus\;|\&mdash\;)(\d+))?(( |\&nbsp\;)(\-|\&mdash\;))?/ieu',
						'/(\040|\t|\&nbsp\;|^|\>)ГОСТ( |\&nbsp\;)?(\d+)(\-|\&minus\;|\&mdash\;)(\d+)/ieu',
						),
				'replacement' 	=> array(
						'$m[1].$this->tag("ГОСТ ".$m[3].(isset($m[6])?"&ndash;".$m[6]:"").(isset($m[7])?" &mdash;":""),"span", array("class"=>"nowrap"))',
						'$m[1]."ГОСТ ".$m[3]."&ndash;".$m[5]',
						),
			),
			/*
		'nobr_vtch_itd_itp'     => array(
				'description'	=> 'Привязка сокращений до н.э., н.э.',
				'pattern' 		=> array(
				
				//IV в до н.э, в V-VIвв до нэ., третий в. н.э.
				
						'/(\s|\&nbsp\;)и( |\&nbsp\;)т\.?[ ]?д\./ue',
						'/(\s|\&nbsp\;)и( |\&nbsp\;)т\.?[ ]?п\./ue',
						'/(\s|\&nbsp\;)в( |\&nbsp\;)т\.?[ ]?ч\./ue',						
					),
				'replacement' 	=> array(
						'$m[1].$this->tag("и т. д.", "span",  array("class" => "nowrap"))',
						'$m[1].$this->tag("и т. п.", "span",  array("class" => "nowrap"))',
						'$m[1].$this->tag("в т. ч.", "span",  array("class" => "nowrap"))',
					)
			),
			*/
		
		
		);
}

?>