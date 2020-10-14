<?php

class EMT_Lib
{
	const LAYOUT_STYLE = 1;
	const LAYOUT_CLASS = 2;
	
	const INTERNAL_BLOCK_OPEN = '%%%INTBLOCKO235978%%%';
	const INTERNAL_BLOCK_CLOSE = '%%%INTBLOCKC235978%%%';
	/**
	 * Таблица символов
	 *
	 * @var array
	 */
	public static $_charsTable = array(
		'"' 	=> array('html' => array('&laquo;', '&raquo;', '&rdquo;', '&lsquo;', '&bdquo;', '&ldquo;', '&quot;', '&#171;', '&#187;'),
					 	 'utf8' => array(0x201E, 0x201C, 0x201F, 0x201D, 0x00AB, 0x00BB)),
		' ' 	=> array('html' => array('&nbsp;', '&thinsp;', '&#160;'),
					 	 'utf8' => array(0x00A0, 0x2002, 0x2003, 0x2008, 0x2009)),
		'-' 	=> array('html' => array(/*'&mdash;',*/ '&ndash;', '&minus;', '&#151;', '&#8212;', '&#8211;'),
					 	 'utf8' => array(0x002D, /*0x2014,*/ 0x2010, 0x2012, 0x2013)),
		'—' 	=> array('html' => array('&mdash;'),
					 	 'utf8' => array(0x2014)),
		'==' 	=> array('html' => array('&equiv;'),
						 'utf8' => array(0x2261)),
		'...' 	=> array('html' => array('&hellip;', '&#0133;'),
						 'utf8' => array(0x2026)),
		'!=' 	=> array('html' => array('&ne;', '&#8800;'),
						 'utf8' => array(0x2260)),
		'<=' 	=> array('html' => array('&le;', '&#8804;'),
						 'utf8' => array(0x2264)),
		'>=' 	=> array('html' => array('&ge;', '&#8805;'),
						 'utf8' => array(0x2265)),
		'1/2' 	=> array('html' => array('&frac12;', '&#189;'),
						 'utf8' => array(0x00BD)),
		'1/4' 	=> array('html' => array('&frac14;', '&#188;'),
					     'utf8' => array(0x00BC)),
		'3/4' 	=> array('html' => array('&frac34;', '&#190;'),
						 'utf8' => array(0x00BE)),
		'+-' 	=> array('html' => array('&plusmn;', '&#177;'),
						 'utf8' => array(0x00B1)),
		'&' 	=> array('html' => array('&amp;', '&#38;')),
		'(tm)' 	=> array('html' => array('&trade;', '&#153;'),
						 'utf8' => array(0x2122)),
		//'(r)' 	=> array('html' => array('<sup>&reg;</sup>', '&reg;', '&#174;'), 
		'(r)' 	=> array('html' => array('&reg;', '&#174;'), 
						 'utf8' => array(0x00AE)),
		'(c)' 	=> array('html' => array('&copy;', '&#169;'), 
					     'utf8' => array(0x00A9)),
		'§' 	=> array('html' => array('&sect;', '&#167;'), 
					     'utf8' => array(0x00A7)),
		'`' 	=> array('html' => array('&#769;')),
		'\'' 	=> array('html' => array('&rsquo;', '’')),
		'x' 	=> array('html' => array('&times;', '&#215;'), 
					     'utf8' => array('×') /* какой же у него может быть код? */),
		
	);

	/**
	 * Добавление к тегам атрибута 'id', благодаря которому
	 * при повторном типографирование текста будут удалены теги,
	 * расставленные данным типографом
	 *
	 * @var array
	 */
	protected static $_typographSpecificTagId = false;
	
	
	/**
     * Костыли для работы с символами UTF-8
     * 
     * @author	somebody?
     * @param	int $c код символа в кодировке UTF-8 (например, 0x00AB)
     * @return	bool|string
     */
    public static function _getUnicodeChar($c)
    {
    	if ($c <= 0x7F) {
        	return chr($c);
    	} else if ($c <= 0x7FF) {
        	return chr(0xC0 | $c >> 6)
        	     . chr(0x80 | $c & 0x3F);
    	} else if ($c <= 0xFFFF) {
        	return chr(0xE0 | $c >> 12)
        	     . chr(0x80 | $c >> 6 & 0x3F)
                 . chr(0x80 | $c & 0x3F);
    	} else if ($c <= 0x10FFFF) {
        	return chr(0xF0 | $c >> 18) 
        		 . chr(0x80 | $c >> 12 & 0x3F)                 	
        		 . chr(0x80 | $c >> 6 & 0x3F)
                 . chr(0x80 | $c & 0x3F);
    	} else {
        	return false;
    	}
    }
	

	/**
	 * Удаление кодов HTML из текста
	 *
	 * <code>
	 *  // Remove UTF-8 chars:
	 * 	$str = EMT_Lib::clear_special_chars('your text', 'utf8');
	 *  // ... or HTML codes only:
	 * 	$str = EMT_Lib::clear_special_chars('your text', 'html');
	 * 	// ... or combo:
	 *  $str = EMT_Lib::clear_special_chars('your text');
	 * </code>
	 *
	 * @param 	string $text
	 * @param   mixed $mode
	 * @return 	string|bool
	 */
	public static function clear_special_chars($text, $mode = null)
	{
		if(is_string($mode)) $mode = array($mode);
		if(is_null($mode)) $mode = array('utf8', 'html');
		if(!is_array($mode)) return false;
		$moder = array();
		foreach($mode as $mod) if(in_array($mod, array('utf8','html'))) $moder[] = $mod;
		if(count($moder)==0) return false;
		
		foreach (self::$_charsTable as $char => $vals) 
		{
			foreach ($mode as $type) 
			{
				if (isset($vals[$type])) 
				{
					foreach ($vals[$type] as $v) 
					{
						if ('utf8' === $type && is_int($v)) 
						{
							$v = self::_getUnicodeChar($v);
						}
						if ('html' === $type) 
						{
							if(preg_match("/<[a-z]+>/i",$v))
							{
								$v = self::safe_tag_chars($v, true);
							}
						}
						$text = str_replace($v, $char, $text);
					}
				}
			}
		}
		
		return $text;
	}
	
	/**
	 * Удаление тегов HTML из текста
	 * Тег <br /> будет преобразов в перенос строки \n, сочетание тегов </p><p> -
	 * в двойной перенос
	 *
	 * @param 	string $text
	 * @param 	array $allowableTag массив из тегов, которые будут проигнорированы
	 * @return 	string
	 */
	public static function remove_html_tags($text, $allowableTag = null)
	{
		$ignore = null;
		
		if (null !== $allowableTag) 
		{
			if (is_string($allowableTag)) 
			{
				$allowableTag = array($allowableTag);
			}
			if (is_array($allowableTag))
			{
				$tags = array();	
				foreach ($allowableTag as $tag) 
				{
					if ('<' !== substr($tag, 0, 1) || '>' !== substr($tag, -1, 1)) continue;
					if ('/' === substr($tag, 1, 1)) continue;
					$tags [] = $tag;
				}
				$ignore = implode('', $tags);
			}
		}
		$text = preg_replace(array('/\<br\s*\/?>/i', '/\<\/p\>\s*\<p\>/'), array("\n","\n\n"), $text);
		$text = strip_tags($text, $ignore);
		return $text;
	}
	
	/**
     * Сохраняем содержимое тегов HTML
     *
     * Тег 'a' кодируется со специальным префиксом для дальнейшей
     * возможности выносить за него кавычки.
     * 
     * @param 	string $text
     * @param 	bool $safe
     * @return  string
     */
    public static function safe_tag_chars($text, $way)
    {
    	if ($way) 
        	$text = preg_replace_callback('/(\<\/?)([^<>]+?)(\>)/s', function($m) {return (strlen($m[1])==1 && substr(trim($m[2]), 0, 1) == '-' && substr(trim($m[2]), 1, 1) != '-')? $m[0] : $m[1].( substr(trim($m[2]), 0, 1) === "a" ? "%%___"  : ""  ) . EMT_Lib::encrypt_tag(trim($m[2]))  . $m[3]; }, $text);
        else
        	$text = preg_replace_callback('/(\<\/?)([^<>]+?)(\>)/s', function($m) {return (strlen($m[1])==1 && substr(trim($m[2]), 0, 1) == '-' && substr(trim($m[2]), 1, 1) != '-')? $m[0] : $m[1].( substr(trim($m[2]), 0, 3) === "%%___" ? EMT_Lib::decrypt_tag(substr(trim($m[2]), 4)) : EMT_Lib::decrypt_tag(trim($m[2])) ) . $m[3];} , $text);	
        return $text;
    }
    
    
    /**
     * Декодриует спец блоки
     *
     * @param 	string $text
     * @return  string
     */
    public static function decode_internal_blocks($text)
    {
    	$text = preg_replace_callback('/'.EMT_Lib::INTERNAL_BLOCK_OPEN.'([a-zA-Z0-9\/=]+?)'.EMT_Lib::INTERNAL_BLOCK_CLOSE.'/s', function($m) {return EMT_Lib::decrypt_tag($m[1]);}, $text);
        return $text;
    }
    
    /**
     * Кодирует спец блок
     *
     * @param 	string $text
     * @return  string
     */
    public static function iblock($text)
    {
        return EMT_Lib::INTERNAL_BLOCK_OPEN. EMT_Lib::encrypt_tag($text).EMT_Lib::INTERNAL_BLOCK_CLOSE;
    }
    
    
    /**
     * Создание тега с защищенным содержимым 
     *
     * @param 	string $content текст, который будет обрамлен тегом
     * @param 	string $tag тэг 
     * @param 	array $attribute список атрибутов, где ключ - имя атрибута, а значение - само значение данного атрибута
     * @return 	string
     */
    public static function build_safe_tag($content, $tag = 'span', $attribute = array(), $layout = EMT_Lib::LAYOUT_STYLE )
    {
    	$htmlTag = $tag;
		
    	if (self::$_typographSpecificTagId) 
    	{
    		if(!isset($attribute['id'])) 
    		{
    			$attribute['id'] = 'emt-2' . mt_rand(1000,9999);
    		}
    	}
    	
		$classname = "";
    	if (count($attribute)) 
		{
			
			if($layout & EMT_lib::LAYOUT_STYLE)
			{
				if(isset($attribute['__style']) && $attribute['__style'])
				{
					if(isset($attribute['style']) && $attribute['style'])
					{
						$st = trim($attribute['style']);
						if(mb_substr($st, -1) != ";") $st .= ";";
						$st .= $attribute['__style'];
						$attribute['style'] = $st;
					} else {
						$attribute['style'] = $attribute['__style'];
					}
					unset($attribute['__style']);
				}
				
			}			
			foreach ($attribute as $attr => $value) 
			{
				if($attr == "__style") continue;
				if($attr == "class") {
					$classname = "$value";
					continue;
				}
				$htmlTag .= " $attr=\"$value\"";
			}
			
		}
    	
		if( ($layout & EMT_lib::LAYOUT_CLASS ) && $classname) {
    		$htmlTag .= " class=\"$classname\"";
    	}
    	
		return "<" . self::encrypt_tag($htmlTag) . ">$content</" . self::encrypt_tag($tag) . ">";
    }
    
    /**
     * Метод, осуществляющий кодирование (сохранение) информации
     * с целью невозможности типографировать ее
     *
     * @param 	string $text
     * @return 	string
     */
    public static function encrypt_tag($text)
    {
    	return base64_encode($text)."=";
    }
    
    /**
     * Метод, осуществляющий декодирование информации
     *
     * @param 	string $text
     * @return 	string
     */
    public static function decrypt_tag($text)
    {
    	return base64_decode(substr($text,0,-1));
    }
    
    
    
    public static function strpos_ex(&$haystack, $needle, $offset = null)
    {
    	if(is_array($needle))
    	{
    		$m = false;
    		$w = false;
    		foreach($needle as $n)
    		{
    			$p = strpos($haystack, $n , $offset);
    			if($p===false) continue;
    			if($m === false)
    			{
    				$m = $p;
    				$w = $n;
    				continue;
    			}
    			if($p < $m)
    			{
    				$m = $p;
    				$w = $n;
    			}
    		}
    		if($m === false) return false;
    		return array('pos' => $m, 'str' => $w);
    	}
    	return strpos($haystack, $needle, $offset);    	
    }
    
    public static function _process_selector_pattern(&$pattern)
	{
		if($pattern===false) return;
		$pattern = preg_quote($pattern , '/');
		$pattern = str_replace("\\*", "[a-z0-9_\-]*", $pattern);
		$pattern = "/".$pattern."/i";
	}
	public static function _test_pattern($pattern, $text)
	{
		if($pattern === false) return true;
		return preg_match($pattern, $text);
	}
	
    public static function strtolower($string)
    { 
		$convert_to = array( 
			"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", 
			"v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", 
			"ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "а", "б", "в", "г", "д", "е", "ё", "ж", 
			"з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы", 
			"ь", "э", "ю", "я" 
		); 
		$convert_from = array( 
			"A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", 
			"V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", 
			"Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж", 
			"З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ъ", 
			"Ь", "Э", "Ю", "Я" 
		); 
		
		return str_replace($convert_from, $convert_to, $string); 
	} 
	
	// взято с http://www.w3.org/TR/html4/sgml/entities.html
	protected static $html4_char_ents = array(
		'nbsp' => 160,
		'iexcl' => 161,
		'cent' => 162,
		'pound' => 163,
		'curren' => 164,
		'yen' => 165,
		'brvbar' => 166,
		'sect' => 167,
		'uml' => 168,
		'copy' => 169,
		'ordf' => 170,
		'laquo' => 171,
		'not' => 172,
		'shy' => 173,
		'reg' => 174,
		'macr' => 175,
		'deg' => 176,
		'plusmn' => 177,
		'sup2' => 178,
		'sup3' => 179,
		'acute' => 180,
		'micro' => 181,
		'para' => 182,
		'middot' => 183,
		'cedil' => 184,
		'sup1' => 185,
		'ordm' => 186,
		'raquo' => 187,
		'frac14' => 188,
		'frac12' => 189,
		'frac34' => 190,
		'iquest' => 191,
		'Agrave' => 192,
		'Aacute' => 193,
		'Acirc' => 194,
		'Atilde' => 195,
		'Auml' => 196,
		'Aring' => 197,
		'AElig' => 198,
		'Ccedil' => 199,
		'Egrave' => 200,
		'Eacute' => 201,
		'Ecirc' => 202,
		'Euml' => 203,
		'Igrave' => 204,
		'Iacute' => 205,
		'Icirc' => 206,
		'Iuml' => 207,
		'ETH' => 208,
		'Ntilde' => 209,
		'Ograve' => 210,
		'Oacute' => 211,
		'Ocirc' => 212,
		'Otilde' => 213,
		'Ouml' => 214,
		'times' => 215,
		'Oslash' => 216,
		'Ugrave' => 217,
		'Uacute' => 218,
		'Ucirc' => 219,
		'Uuml' => 220,
		'Yacute' => 221,
		'THORN' => 222,
		'szlig' => 223,
		'agrave' => 224,
		'aacute' => 225,
		'acirc' => 226,
		'atilde' => 227,
		'auml' => 228,
		'aring' => 229,
		'aelig' => 230,
		'ccedil' => 231,
		'egrave' => 232,
		'eacute' => 233,
		'ecirc' => 234,
		'euml' => 235,
		'igrave' => 236,
		'iacute' => 237,
		'icirc' => 238,
		'iuml' => 239,
		'eth' => 240,
		'ntilde' => 241,
		'ograve' => 242,
		'oacute' => 243,
		'ocirc' => 244,
		'otilde' => 245,
		'ouml' => 246,
		'divide' => 247,
		'oslash' => 248,
		'ugrave' => 249,
		'uacute' => 250,
		'ucirc' => 251,
		'uuml' => 252,
		'yacute' => 253,
		'thorn' => 254,
		'yuml' => 255,
		'fnof' => 402,
		'Alpha' => 913,
		'Beta' => 914,
		'Gamma' => 915,
		'Delta' => 916,
		'Epsilon' => 917,
		'Zeta' => 918,
		'Eta' => 919,
		'Theta' => 920,
		'Iota' => 921,
		'Kappa' => 922,
		'Lambda' => 923,
		'Mu' => 924,
		'Nu' => 925,
		'Xi' => 926,
		'Omicron' => 927,
		'Pi' => 928,
		'Rho' => 929,
		'Sigma' => 931,
		'Tau' => 932,
		'Upsilon' => 933,
		'Phi' => 934,
		'Chi' => 935,
		'Psi' => 936,
		'Omega' => 937,
		'alpha' => 945,
		'beta' => 946,
		'gamma' => 947,
		'delta' => 948,
		'epsilon' => 949,
		'zeta' => 950,
		'eta' => 951,
		'theta' => 952,
		'iota' => 953,
		'kappa' => 954,
		'lambda' => 955,
		'mu' => 956,
		'nu' => 957,
		'xi' => 958,
		'omicron' => 959,
		'pi' => 960,
		'rho' => 961,
		'sigmaf' => 962,
		'sigma' => 963,
		'tau' => 964,
		'upsilon' => 965,
		'phi' => 966,
		'chi' => 967,
		'psi' => 968,
		'omega' => 969,
		'thetasym' => 977,
		'upsih' => 978,
		'piv' => 982,
		'bull' => 8226,
		'hellip' => 8230,
		'prime' => 8242,
		'Prime' => 8243,
		'oline' => 8254,
		'frasl' => 8260,
		'weierp' => 8472,
		'image' => 8465,
		'real' => 8476,
		'trade' => 8482,
		'alefsym' => 8501,
		'larr' => 8592,
		'uarr' => 8593,
		'rarr' => 8594,
		'darr' => 8595,
		'harr' => 8596,
		'crarr' => 8629,
		'lArr' => 8656,
		'uArr' => 8657,
		'rArr' => 8658,
		'dArr' => 8659,
		'hArr' => 8660,
		'forall' => 8704,
		'part' => 8706,
		'exist' => 8707,
		'empty' => 8709,
		'nabla' => 8711,
		'isin' => 8712,
		'notin' => 8713,
		'ni' => 8715,
		'prod' => 8719,
		'sum' => 8721,
		'minus' => 8722,
		'lowast' => 8727,
		'radic' => 8730,
		'prop' => 8733,
		'infin' => 8734,
		'ang' => 8736,
		'and' => 8743,
		'or' => 8744,
		'cap' => 8745,
		'cup' => 8746,
		'int' => 8747,
		'there4' => 8756,
		'sim' => 8764,
		'cong' => 8773,
		'asymp' => 8776,
		'ne' => 8800,
		'equiv' => 8801,
		'le' => 8804,
		'ge' => 8805,
		'sub' => 8834,
		'sup' => 8835,
		'nsub' => 8836,
		'sube' => 8838,
		'supe' => 8839,
		'oplus' => 8853,
		'otimes' => 8855,
		'perp' => 8869,
		'sdot' => 8901,
		'lceil' => 8968,
		'rceil' => 8969,
		'lfloor' => 8970,
		'rfloor' => 8971,
		'lang' => 9001,
		'rang' => 9002,
		'loz' => 9674,
		'spades' => 9824,
		'clubs' => 9827,
		'hearts' => 9829,
		'diams' => 9830,
		'quot' => 34,
		'amp' => 38,
		'lt' => 60,
		'gt' => 62,
		'OElig' => 338,
		'oelig' => 339,
		'Scaron' => 352,
		'scaron' => 353,
		'Yuml' => 376,
		'circ' => 710,
		'tilde' => 732,
		'ensp' => 8194,
		'emsp' => 8195,
		'thinsp' => 8201,
		'zwnj' => 8204,
		'zwj' => 8205,
		'lrm' => 8206,
		'rlm' => 8207,
		'ndash' => 8211,
		'mdash' => 8212,
		'lsquo' => 8216,
		'rsquo' => 8217,
		'sbquo' => 8218,
		'ldquo' => 8220,
		'rdquo' => 8221,
		'bdquo' => 8222,
		'dagger' => 8224,
		'Dagger' => 8225,
		'permil' => 8240,
		'lsaquo' => 8249,
		'rsaquo' => 8250,
		'euro' => 8364,
	);
	/**
	 * Вернуть уникод символ по html entinty
	 *
	 * @param string $entity
	 * @return string
	 */
	public static function html_char_entity_to_unicode($entity)
	{
		if(isset(self::$html4_char_ents[$entity])) return self::_getUnicodeChar(self::$html4_char_ents[$entity]);
		return false;
	}
	
	/**
	 * Сконвериторвать все html entity в соответсвующие юникод символы
	 *
	 * @param string $text
	 */
	public static function convert_html_entities_to_unicode(&$text)
	{
		$text = preg_replace_callback("/\&#([0-9]+)\;/", 
				function($m) {return EMT_Lib::_getUnicodeChar(intval($m[1])); }
				, $text);
		$text = preg_replace_callback("/\&#x([0-9A-F]+)\;/", 
				function($m) {return EMT_Lib::_getUnicodeChar(hexdec($m[1])); }
				, $text);
		$text = preg_replace_callback("/\&([a-zA-Z0-9]+)\;/", 
				function($m) { $r = EMT_Lib::html_char_entity_to_unicode($m[1]); return $r ? $r : $m[0]; }
				, $text);
	}
	
	public static function rstrpos ($haystack, $needle, $offset = 0){
	    
	    if(trim($haystack) != "" && trim($needle) != "" && $offset <= mb_strlen($haystack))
	    {
	        $last_pos = $offset;
	        $found = false;
	        while(($curr_pos = mb_strpos($haystack, $needle, $last_pos)) !== false)
	        {
	            $found = true;
	            $last_pos = $curr_pos + 1;
	        }
	        if($found)
	        {
	            return $last_pos - 1;
	        }
	        else
	        {
	            return false;
	        }
	    }
	    else
	    {
	        return false;
	    } 
	}
	
	public static function ifop($cond, $true, $false) {
		return $cond ? $true : $false;
	}
	
	public static function split_number($num) {
		return number_format($num, 0, '', ' ');
	}

	// https://mathiasbynens.be/demo/url-regex
	// @gruber v2 (218 chars)
	public static function url_regex() {
		/*return <<<URLREGEX
_(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?_iuS
URLREGEX;
		*/
		return <<<URLREGEX
#(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'".,<>?«»“”‘’]))#iS
URLREGEX;

		/*
		return <<<URLREGEX
/([a-z][a-z0-9\*\-\.]*):\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:(?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?](?:[\w#!:\.\?\+=&@!$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?/xiS
URLREGEX;
*/
	}
	
	// https://emailregex.com/
	public static function email_regex() {
		$z = <<<EMAILREGEX
(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])
EMAILREGEX;
		$z = '~'. str_replace('~', '\\'.'~', $z) . '~imS';
		return $z;
	}
}

?>