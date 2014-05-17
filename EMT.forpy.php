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
		'"' 	=> array('html' => array('&laquo;', '&raquo;', '&ldquo;', '&lsquo;', '&bdquo;', '&ldquo;', '&quot;', '&#171;', '&#187;'),
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
        	$text = preg_replace_callback('/(\<\/?)(.+?)(\>)/s', create_function('$m','return $m[1].( substr(trim($m[2]), 0, 1) === "a" ? "%%___"  : ""  ) . EMT_Lib::encrypt_tag(trim($m[2]))  . $m[3];'), $text);
        else
        	$text = preg_replace_callback('/(\<\/?)(.+?)(\>)/s', create_function('$m','return $m[1].( substr(trim($m[2]), 0, 3) === "%%___" ? EMT_Lib::decrypt_tag(substr(trim($m[2]), 4)) : EMT_Lib::decrypt_tag(trim($m[2])) ) . $m[3];'), $text);	
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
    	$text = preg_replace_callback('/'.EMT_Lib::INTERNAL_BLOCK_OPEN.'([a-zA-Z0-9\/=]+?)'.EMT_Lib::INTERNAL_BLOCK_CLOSE.'/s', create_function('$m','return EMT_Lib::decrypt_tag($m[1]);'), $text);	
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
    	return base64_encode($text);
    }
    
    /**
     * Метод, осуществляющий декодирование информации
     *
     * @param 	string $text
     * @return 	string
     */
    public static function decrypt_tag($text)
    {
    	return base64_decode($text);
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
				create_function('$m', 'return EMT_Lib::_getUnicodeChar(intval($m[1]));')
				, $text);
		$text = preg_replace_callback("/\&#x([0-9A-F]+)\;/", 
				create_function('$m', 'return EMT_Lib::_getUnicodeChar(hexdec($m[1]));')
				, $text);
		$text = preg_replace_callback("/\&([a-zA-Z0-9]+)\;/", 
				create_function('$m', '$r = EMT_Lib::html_char_entity_to_unicode($m[1]); return $r ? $r : $m[0];')
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

}



/**
 * Базовый класс для группы правил обработки текста
 * Класс группы должен наследовать, данный класс и задавать
 * в нём EMT_Tret::rules и EMT_Tret::$name
 * 
 */
class EMT_Tret {
	
	/**
	 * Набор правил в данной группе, который задан изначально
	 * Его можно менять динамически добавляя туда правила с помощью put_rule
	 *
	 * @var unknown_type
	 */
	public    $rules;
	public    $title;
	
	
	private   $disabled = array();
	private   $enabled  = array();
	protected $_text= '';
	public $logging = false;
	public $logs    = false;
	public $errors  = false;	
	public $debug_enabled  = false;
	public $debug_info     = array();
	
	
	private $use_layout = false;
	private $use_layout_set = false;
	private $class_layout_prefix = false;
	
	public $class_names     = array();
	public $classes         = array();
	public $settings        = array();
	
	/**
	 * Защищенные теги
	 * 
	 * @todo привязать к методам из Jare_Typograph_Tool
	 */
	const BASE64_PARAGRAPH_TAG = 'cA=='; // p
	const BASE64_BREAKLINE_TAG = 'YnIgLw=='; // br / (с пробелом и слэшем)
	const BASE64_NOBR_OTAG = 'bm9icg=='; // nobr
	const BASE64_NOBR_CTAG = 'L25vYnI='; // /nobr
	
	/**
	 * Типы кавычек
	 */
	const QUOTE_FIRS_OPEN = '&laquo;';
    const QUOTE_FIRS_CLOSE = '&raquo;';
    const QUOTE_CRAWSE_OPEN = '&bdquo;';
    const QUOTE_CRAWSE_CLOSE = '&ldquo;';
	
	
	private function log($str, $data = null)
	{
		if(!$this->logging) return;
		$this->logs[] = array('info' => $str, 'data' => $data);
	}

	private function error($info, $data = null)
	{
		$this->errors[] = array('info' => $info, 'data' => $data);
		$this->log('ERROR: '. $info , $data);
	}
	
	public function debug($place, &$after_text)
	{
		if(!$this->debug_enabled) return;
		$this->debug_info[] = array(
				'place' => $place,
				'text'  => $after_text,
			);
	}
	
	
	/**
	 * Установить режим разметки для данного Трэта если не было раньше установлено,
	 *   EMT_Lib::LAYOUT_STYLE - с помощью стилей
	 *   EMT_Lib::LAYOUT_CLASS - с помощью классов
	 *
	 * @param int $kind
	 */
	public function set_tag_layout_ifnotset($layout)
	{
		if($this->use_layout_set) return;
		$this->use_layout = $layout;
	}
	
	/**
	 * Установить режим разметки для данного Трэта,
	 *   EMT_Lib::LAYOUT_STYLE - с помощью стилей
	 *   EMT_Lib::LAYOUT_CLASS - с помощью классов
	 *   EMT_Lib::LAYOUT_STYLE|EMT_Lib::LAYOUT_CLASS - оба метода
	 *
	 * @param int $kind
	 */
	public function set_tag_layout($layout = EMT_Lib::LAYOUT_STYLE)
	{
		$this->use_layout = $layout;
		$this->use_layout_set = true;
	}
	
	public function set_class_layout_prefix($prefix)
	{
		$this->class_layout_prefix = $prefix;
	}
	
	
	public function debug_on()
	{
		$this->debug_enabled = true;
	}
	
	public function log_on()
	{
		$this->debug_enabled = true;
	}
	
	
	private function getmethod($name)
	{
		if(!$name) return false;
		if(!method_exists($this, $name)) return false;
		return array($this, $name);
	}
	
	private function _pre_parse()
	{
		$this->pre_parse();
		foreach($this->rules as $rule)
		{
			if(!isset($rule['init'])) continue;
			$m = $this->getmethod($rule['init']);
			if(!$m) continue;
			call_user_func($m);
		}
	}
	private function _post_parse()
	{
		foreach($this->rules as $rule)
		{
			if(!isset($rule['deinit'])) continue;
			$m = $this->getmethod($rule['deinit']);
			if(!$m) continue;
			call_user_func($m);
		}
		$this->post_parse();
	}
	
	private function rule_order_sort($a, $b)
	{
		if($a['order'] == $b['order']) return 0;
		if($a['order'] < $b['order']) return -1;
		return 1;
	}
	
	private function apply_rule($rule)
	{
		$name = $rule['id'];
		//$this->log("Правило $name", "Применяем правило");
		$disabled = (isset($this->disabled[$rule['id']]) && $this->disabled[$rule['id']]) || ((isset($rule['disabled']) && $rule['disabled']) && !(isset($this->enabled[$rule['id']]) && $this->enabled[$rule['id']]));
		if($disabled)
		{
			$this->log("Правило $name", "Правило отключено" . ((isset($rule['disabled']) && $rule['disabled'])? " (по умолчанию)" : ""));
			return;
		}
		if(isset($rule['function']) && $rule['function'])
		{
			if(!(isset($rule['pattern']) &&  $rule['pattern']))
			{
				if(method_exists($this, $rule['function']))
				{
					$this->log("Правило $name", "Используется метод ".$rule['function']." в правиле");
					
					call_user_func(array($this, $rule['function']));
					return;
				} 
				if(function_exists($rule['function']))
				{
					$this->log("Правило $name", "Используется функция ".$rule['function']." в правиле");
					
					call_user_func($rule['function']);
					return;
				}
				
				$this->error('Функция '.$rule['function'].' из правила '.$rule['id']. " не найдена");
				return ;
			} else {
				if(preg_match("/^[a-z_0-9]+$/i", $rule['function']))
				{
					if(method_exists($this, $rule['function']))
					{
						$this->log("Правило $name", "Замена с использованием preg_replace_callback с методом ".$rule['function']."");
						
						$this->_text = preg_replace_callback($rule['pattern'], array($this, $rule['function']), $this->_text);
						return;
					} 
					if(function_exists($rule['function']))
					{
						$this->log("Правило $name", "Замена с использованием preg_replace_callback с функцией ".$rule['function']."");
						
						$this->_text = preg_replace_callback($rule['pattern'], $rule['function'], $this->_text);
						return;
					}
					$this->error('Функция '.$rule['function'].' из правила '.$rule['id']. " не найдена");
				} else {
					$this->_text = preg_replace_callback($rule['pattern'],  create_function('$m', $rule['function']), $this->_text);
					$this->log('Замена с использованием preg_replace_callback с инлайн функцией из правила '.$rule['id']);
					return;
				}
				return ;
			}
		}
		
		if(isset($rule['simple_replace']) && $rule['simple_replace'])
		{
			if(isset($rule['case_sensitive']) && $rule['case_sensitive'])
			{
				$this->log("Правило $name", "Простая замена с использованием str_replace");
				$this->_text = str_replace($rule['pattern'], $rule['replacement'], $this->_text);
				return;
			}
			$this->log("Правило $name", "Простая замена с использованием str_ireplace");		
			$this->_text = str_ireplace($rule['pattern'], $rule['replacement'], $this->_text);
			return;
		}
		
		$pattern = $rule['pattern'];
		if(is_string($pattern)) $pattern = array($pattern);
		$eval = false;
		foreach($pattern as $patt)
		{
			$chr = substr($patt,0,1);
			$preg_arr = explode($chr, $patt);		
			if(strpos($preg_arr[count($preg_arr)-1], "e")!==false)
			{
				$eval = true;
				break;
			}
		}
		if(!$eval)
		{
			$this->log("Правило $name", "Замена с использованием preg_replace");		
			
			do {
				$this->_text = preg_replace($rule['pattern'], $rule['replacement'], $this->_text);
				if(!(isset($rule['cycled']) && $rule['cycled'])) break;
			} while(preg_match($rule['pattern'], $this->_text));
			
			return;
		}
		
		$this->log("Правило $name", "Замена с использованием preg_replace_callback вместо eval");		
		$k = 0;
		foreach($pattern as $patt)
		{
			$repl = is_string($rule['replacement']) ? $rule['replacement'] : $rule['replacement'][$k];
			
			$chr = substr($patt,0,1);
			$preg_arr = explode($chr, $patt);		
			if(strpos($preg_arr[count($preg_arr)-1], "e")!==false) // eval система
			{
				$preg_arr[count($preg_arr)-1] = str_replace("e","",$preg_arr[count($preg_arr)-1]);
				$patt = implode($chr, $preg_arr);
				$this->thereplacement = $repl;
				do {
					$this->_text = preg_replace_callback($patt, array($this, "thereplcallback"), $this->_text);
					if(!(isset($rule['cycled']) && $rule['cycled'])) break;
				} while(preg_match($patt, $this->_text));
				
			} else {
				do {
					$this->_text = preg_replace($patt, $repl, $this->_text);
					if(!(isset($rule['cycled']) && $rule['cycled'])) break;
				} while(preg_match($patt, $this->_text));
			}
			$k++;
		}
	}
	
	
	protected function preg_replace_e($pattern, $replacement, $text)
	{
		$chr = substr($pattern,0,1);
		$preg_arr = explode($chr, $pattern);				
		if(strpos($preg_arr[count($preg_arr)-1], "e")===false) return preg_replace($pattern, $replacement, $text);
		$preg_arr[count($preg_arr)-1] = str_replace("e","",$preg_arr[count($preg_arr)-1]);
		$patt = implode($chr, $preg_arr);
		$this->thereplacement = $replacement;
		return preg_replace_callback($patt, array($this, "thereplcallback"), $text);
	}
	
	private $thereplacement = "";
	private function thereplcallback($m)
	{
		$x = "";
		eval('$x = '.($this->thereplacement?$this->thereplacement:'""').';');
		return $x;
	}
	
	private function _apply($list)
	{
		$this->errors = array();
		$this->_pre_parse();
		
		$this->log("Применяется набор правил", implode(",",$list));
		
		$rulelist = array();
		foreach($list as $k)
		{			
			$rule = $this->rules[$k];
			$rule['id']    = $k;
			$rule['order'] = isset($rule['order'])? $rule['order'] : 5 ;
			$rulelist[] = $rule;
		}
		//usort($rulelist, array($this, "rule_order_sort"));
		
		foreach($rulelist as $rule)
		{
			$this->apply_rule($rule);			
			$this->debug($rule['id'], $this->_text);
		}
		
		$this->_post_parse();
	}
	
	
	/**
	 * Создание защищенного тега с содержимым
	 *
	 * @see 	EMT_lib::build_safe_tag
	 * @param 	string $content
	 * @param 	string $tag
	 * @param 	array $attribute
	 * @return 	string
	 */
	protected function tag($content, $tag = 'span', $attribute = array())
	{
		if(isset($attribute['class']))
		{
			$classname = $attribute['class'];
			if($classname == "nowrap")
			{
				if(!$this->is_on('nowrap'))
				{
					$tag = "nobr";
					$attribute = array();
					$classname = "";
				}
			}
			if(isset($this->classes[$classname]))
			{
				$style_inline = $this->classes[$classname];
				if($style_inline) $attribute['__style'] = $style_inline;
			}
			$classname = (isset($this->class_names[$classname]) ? $this->class_names[$classname] :$classname);
			$classname = ($this->class_layout_prefix ? $this->class_layout_prefix : "" ).$classname;
			$attribute['class'] = $classname;
		}
		
		return EMT_Lib::build_safe_tag($content, $tag, $attribute, 
				$this->use_layout === false? EMT_Lib::LAYOUT_STYLE  : $this->use_layout );
	}
	
	
	/**
	 * Добавить правило в группу
	 *
	 * @param string $name
	 * @param array $params
	 */
	public function put_rule($name, $params)
	{
		$this->rules[$name] = $params; 
		return $this;
	}

	/**
	 * Отключить правило, в обработке
	 *
	 * @param string $name
	 */
	public function disable_rule($name)
	{
		$this->disabled[$name] = true;
		unset($this->enabled[$name]);
	}
	
	/**
	 * Включить правило
	 *
	 * @param string $name
	 */
	public function enable_rule($name)
	{
		$this->enabled[$name] = true;
		unset($this->disabled[$name]);
	}
	
	/**
	 * Добавить настройку в трет
	 *
	 * @param string $key ключ
	 * @param mixed $value значение
	 */
	public function set($key, $value)
	{
		$this->settings[$key] = $value;
	}
	
	/**
	 * Установлена ли настройка
	 *
	 * @param string $key
	 */
	public function is_on($key)
	{
		if(!isset($this->settings[$key])) return false;
		$kk = $this->settings[$key];
		return ((strtolower($kk)=="on") || ($kk === "1") || ($kk === true) || ($kk === 1));
	}
	
	/**
	 * Получить строковое значение настройки
	 *
	 * @param unknown_type $key
	 * @return unknown
	 */
	public function ss($key)
	{
		if(!isset($this->settings[$key])) return "";
		return strval($this->settings[$key]);
	}
	
	/**
	 * Добавить настройку в правило
	 *
	 * @param string $rulename идентификатор правила 
	 * @param string $key ключ
	 * @param mixed $value значение
	 */
	public function set_rule($rulename, $key, $value)
	{
		$this->rules[$rulename][$key] = $value;
	}
	
	/**
	 * Включить правила, согласно списку
	 *
	 * @param array $list список правил
	 * @param boolean $disable выкллючить их или включить
	 * @param boolean $strict строго, т.е. те которые не в списку будут тоже обработаны
	 */
	public function activate($list,$disable =false, $strict = true)
	{
		if(!is_array($list)) return ;
		
		foreach($list as $rulename)
		{
			if($disable) $this->disable_rule($rulename); else $this->enable_rule($rulename);
		}
		
		if($strict)
		{
			foreach($this->rules as $rulename => $v)
			{
				if(in_array($rulename, $list)) continue;
				if(!$disable) $this->disable_rule($rulename); else $this->enable_rule($rulename);
			}
		}
	}
	
	public function set_text(&$text)
	{
		$this->_text = &$text;
		$this->debug_info = array();
		$this->logs = array();
	}
	

	/**
	 * Применить к тексту
	 *
	 * @param string $text - текст к которому применить
	 * @param mixed $list - список правил, null - все правила
	 * @return string
	 */
	public function apply($list = null)
	{
		if(is_string($list)) $rlist = array($list);
		elseif(is_array($list)) $rlist = $list;
		else $rlist = array_keys($this->rules);
		$this->_apply($rlist);
		return $this->_text;
	}
	
	
	
	
	/**
	 * Код, выполняем до того, как применить правила
	 *
	 */
	public function pre_parse()
	{
	}
	
	/**
	 * После выполнения всех правил, выполняется этот метод
	 *
	 */
	public function post_parse()
	{
	}
	
	
}




/**
 * @see EMT_Tret
 */

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
							'/(\s|^|\>|\&nbsp\;|\,)(\d+)( |\&nbsp\;)?(м|мм|см|дм|км|гм|km|dm|cm|mm)(\s|\.|\!|\?|\,|$|\&plusmn\;|\;)/iu', 
							'/(\s|^|\>|\&nbsp\;|\,)(\d+)( |\&nbsp\;)?(м|мм|см|дм|км|гм|km|dm|cm|mm)([32]|&sup3;|&sup2;)(\s|\.|\!|\?|\,|$|\&plusmn\;|\;)/iue'
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
				'pattern' 		=> array(
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
		'nbsp_te'     => array(
				'description'	=> 'Обработка т.е.',			
				'pattern' 		=> '/(^|\s|\&nbsp\;)([тТ])\.?[ ]?е\./ue',
				'replacement' 	=> '$m[1].$this->tag($m[2].". е.", "span",  array("class" => "nowrap"))',
			),
		'nbsp_money_abbr' => array(
				'description'	=> 'Форматирование денежных сокращений (расстановка пробелов и привязка названия валюты к числу)',
				'pattern' 		=> '/(\d)((\040|\&nbsp\;)?(тыс|млн|млрд)\.?(\040|\&nbsp\;)?)?(\040|\&nbsp\;)?(руб\.|долл\.|евро|€|&euro;|\$|у[\.]? ?е[\.]?)/ieu', 
				'replacement' 	=> '$m[1].($m[4]?"&nbsp;".$m[4].($m[4]=="тыс"?".":""):"")."&nbsp;".(!preg_match("#у[\\\\.]? ?е[\\\\.]?#iu",$m[7])?$m[7]:"у.е.")',
				'replacement_python' => 'm.group(1)+(u"&nbsp;"+m.group(4)+(u"." if m.group(4)==u"тыс" else u"") if m.group(4) else u"")+u"&nbsp;"+(m.group(7) if not re.match(u"у[\\\\.]? ?е[\\\\.]?",m.group(7),re.I | re.U) else u"у.е.")'
				//'replacement_py' => 'm.group(1)+(\"&nbsp;\"+m.group(4)+(m.group(4)==\"\u0442\u044b\u0441\"?\".\" if m.group(4) else \"\"):\"\")+\"&nbsp;\"+(m.group(7) if !preg_match(\"#\u0443[\\\\.]? ?\u0435[\\\\.]?#iu\",m.group(7)) else \"\u0443.\u0435.\")'
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


/**
 * @see EMT_Tret
 */

class EMT_Tret_Dash extends EMT_Tret
{
	public $title = "Дефисы и тире";
	public $rules = array(
		'mdash_symbol_to_html_mdash' => array(
				'description'	=> 'Замена символа тире на html конструкцию',
				'pattern' 		=> '/—/iu',
				'replacement' 	=> '&mdash;'
			),
		'mdash' => array(
				'description'	=> 'Тире после кавычек, скобочек, пунктуации',
				'pattern' 		=> array(
						'/([a-zа-яё0-9]+|\,|\:|\)|\&(ra|ld)quo\;|\|\"|\>)(\040|\t)(—|\-|\&mdash\;)(\s|$|\<)/ui', 
						'/(\,|\:|\)|\")(—|\-|\&mdash\;)(\s|$|\<)/ui', 
						),
				'replacement' 	=> array(
						'\1&nbsp;&mdash;\5',
						'\1&nbsp;&mdash;\3',
						),
			),
		'mdash_2' => array(
				'description'	=> 'Тире после переноса строки',
				'pattern' 		=> '/(\n|\r|^|\>)(\-|\&mdash\;)(\t|\040)/',
				'replacement' 	=> '\1&mdash;&nbsp;'
			),
		'mdash_3' => array(
				'description'	=> 'Тире после знаков восклицания, троеточия и прочее',
				'pattern' 		=> '/(\.|\!|\?|\&hellip\;)(\040|\t|\&nbsp\;)(\-|\&mdash\;)(\040|\t|\&nbsp\;)/',
				'replacement' 	=> '\1 &mdash;&nbsp;'
			),						
		'iz_za_pod' => array(
				'description'	=> 'Расстановка дефисов между из-за, из-под',
				'pattern' 		=> '/(\s|\&nbsp\;|\>|^)(из)(\040|\t|\&nbsp\;)\-?(за|под)([\.\,\!\?\:\;]|\040|\&nbsp\;)/uie',
				'replacement' 	=> '($m[1] == "&nbsp;" ? " " : $m[1]) . $m[2]."-".$m[4] . ($m[5] == "&nbsp;"? " " : $m[5])'
			),
		'to_libo_nibud' => array(
				'description'	=> 'Автоматическая простановка дефисов в обезличенных местоимениях и междометиях',
				'cycled'		=> true,
				'pattern' 		=> '/(\s|^|\&nbsp\;|\>)(кто|кем|когда|зачем|почему|как|что|чем|где|чего|кого)\-?(\040|\t|\&nbsp\;)\-?(то|либо|нибудь)([\.\,\!\?\;]|\040|\&nbsp\;|$)/uie',
				'replacement' 	=> '($m[1] == "&nbsp;" ? " " : $m[1]) . $m[2]."-".$m[4] . ($m[5] == "&nbsp;"? " " : $m[5])'
			),
		'koe_kak' => array(
				'description'	=> 'Кое-как, кой-кого, все-таки',
				'cycled'		=> true,
				'pattern' 		=> array(
						'/(\s|^|\&nbsp\;|\>)(кое)\-?(\040|\t|\&nbsp\;)\-?(как)([\.\,\!\?\;]|\040|\&nbsp\;|$)/uie',
						'/(\s|^|\&nbsp\;|\>)(кой)\-?(\040|\t|\&nbsp\;)\-?(кого)([\.\,\!\?\;]|\040|\&nbsp\;|$)/uie',
						'/(\s|^|\&nbsp\;|\>)(вс[её])\-?(\040|\t|\&nbsp\;)\-?(таки)([\.\,\!\?\;]|\040|\&nbsp\;|$)/uie',
						),
				'replacement' 	=> '($m[1] == "&nbsp;" ? " " : $m[1]) . $m[2]."-".$m[4] . ($m[5] == "&nbsp;"? " " : $m[5])'
			),
		'ka_de_kas' => array(
				'description'	=> 'Расстановка дефисов с частицами ка, де, кась',
				'disabled'		=> true,
				'pattern' 		=> array(
						'/(\s|^|\&nbsp\;|\>)([а-яё]+)(\040|\t|\&nbsp\;)(ка)([\.\,\!\?\;]|\040|\&nbsp\;|$)/uie',
						'/(\s|^|\&nbsp\;|\>)([а-яё]+)(\040|\t|\&nbsp\;)(де)([\.\,\!\?\;]|\040|\&nbsp\;|$)/uie',
						'/(\s|^|\&nbsp\;|\>)([а-яё]+)(\040|\t|\&nbsp\;)(кась)([\.\,\!\?\;]|\040|\&nbsp\;|$)/uie',
						),
				'replacement' 	=> '($m[1] == "&nbsp;" ? " " : $m[1]) . $m[2]."-".$m[4] . ($m[5] == "&nbsp;"? " " : $m[5])'
			),
		
			
					
		);
	
}


/**
 * @see EMT_Tret
 */

class EMT_Tret_Date extends EMT_Tret
{
	public $title = "Даты и дни";
	
	public $classes = array(
			'nowrap'           => 'word-spacing:nowrap;',
			);
	
	public $rules = array(
		'years' => array(
				'description'	=> 'Установка тире и пробельных символов в периодах дат',
				'pattern' 		=> '/(с|по|период|середины|начала|начало|конца|конец|половины|в|между|\([cс]\)|\&copy\;)(\s+|\&nbsp\;)([\d]{4})(-|\&mdash\;|\&minus\;)([\d]{4})(( |\&nbsp\;)?(г\.г\.|гг\.|гг|г\.|г)([^а-яёa-z]))?/eui',
				'replacement' 	=> '$m[1].$m[2].  (intval($m[3])>=intval($m[5])? $m[3].$m[4].$m[5] : $m[3]."&mdash;".$m[5]) . (isset($m[6])? "&nbsp;гг.":"").(isset($m[9])?$m[9]:"")'
			),
		'mdash_month_interval' => array(
				'description'	=> 'Расстановка тире и объединение в неразрывные периоды месяцев',
				'disabled'		=> true,
				'pattern' 		=> '/((январ|феврал|сентябр|октябр|ноябр|декабр)([ьяюе]|[её]м)|(апрел|июн|июл)([ьяюе]|ем)|(март|август)([ауе]|ом)?|ма[йяюе]|маем)\-((январ|феврал|сентябр|октябр|ноябр|декабр)([ьяюе]|[её]м)|(апрел|июн|июл)([ьяюе]|ем)|(март|август)([ауе]|ом)?|ма[йяюе]|маем)/iu',
				'replacement' 	=> '\1&mdash;\8'
			),		
		'nbsp_and_dash_month_interval' => array(
				'description'	=> 'Расстановка тире и объединение в неразрывные периоды дней',			
				'disabled'      => true,
				'pattern' 		=> '/([^\>]|^)(\d+)(\-|\&minus\;|\&mdash\;)(\d+)( |\&nbsp\;)(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)([^\<]|$)/ieu',
				'replacement' 	=> '$m[1].$this->tag($m[2]."&mdash;".$m[4]." ".$m[6],"span", array("class"=>"nowrap")).$m[7]'
			),
		'nobr_year_in_date' => array(
				'description'	=> 'Привязка года к дате',
				'pattern' 		=> array(
					'/(\s|\&nbsp\;)([0-9]{2}\.[0-9]{2}\.([0-9]{2})?[0-9]{2})(\s|\&nbsp\;)?г(\.|\s|\&nbsp\;)/eiu',
					'/(\s|\&nbsp\;)([0-9]{2}\.[0-9]{2}\.([0-9]{2})?[0-9]{2})(\s|\&nbsp\;|\.(\s|\&nbsp\;|$)|$)/eiu',
					),
				'replacement' 	=> array(
					'$m[1].$this->tag($m[2]." г.","span", array("class"=>"nowrap")).($m[5]==="."?"":" ")',
					'$m[1].$this->tag($m[2],"span", array("class"=>"nowrap")).$m[4]',
					),
			),
		'space_posle_goda' => array(
				'description'	=> 'Пробел после года',
				'pattern' 		=> '/(^|\040|\&nbsp\;)([0-9]{3,4})(год([ауе]|ом)?)([^a-zа-яё]|$)/ui', 
				'replacement' 	=> '\1\2 \3\5'
			),
		'nbsp_posle_goda_abbr' => array(
				'description'	=> 'Пробел после года',
				'pattern' 		=> '/(^|\040|\&nbsp\;|\"|\&laquo\;)([0-9]{3,4})[ ]?(г\.)([^a-zа-яё]|$)/ui', 
				'replacement' 	=> '\1\2&nbsp;\3\4'
			),
		
		);
}


/**
 * @see EMT_Tret
 */

class EMT_Tret_Etc extends EMT_Tret
{
	
	
	public $classes = array(
			'nowrap'           => 'word-spacing:nowrap;',
		);
	
	
	/**
	 * Базовые параметры тофа
	 *
	 * @var array
	 */
	public $title = "Прочее";
	public $rules = array(	
		'acute_accent' => array(
				'description'	=> 'Акцент',
				'pattern' 		=> '/(у|е|ы|а|о|э|я|и|ю|ё)\`(\w)/i', 
				'replacement' 	=> '\1&#769;\2'
			),
		
		
				
		'word_sup' => array(
				'description'	=> 'Надстрочный текст после символа ^',
				'pattern' 		=> '/((\s|\&nbsp\;|^)+)\^([a-zа-яё0-9\.\:\,\-]+)(\s|\&nbsp\;|$|\.$)/ieu',
				'replacement' 	=> '"" . $this->tag($this->tag($m[3],"small"),"sup") . $m[4]'
			),					
		'century_period' => array(
				'description'	=> 'Тире между диапозоном веков',
				'pattern' 		=> '/(\040|\t|\&nbsp\;|^)([XIV]{1,5})(-|\&mdash\;)([XIV]{1,5})(( |\&nbsp\;)?(в\.в\.|вв\.|вв|в\.|в))/eu',
				'replacement' 	=> '$m[1] .$this->tag($m[2]."&mdash;".$m[4]." вв.","span", array("class"=>"nowrap"))'
			),
		'time_interval' => array(
				'description'	=> 'Тире и отмена переноса между диапозоном времени',
				'pattern' 		=> '/([^\d\>]|^)([\d]{1,2}\:[\d]{2})(-|\&mdash\;|\&minus\;)([\d]{1,2}\:[\d]{2})([^\d\<]|$)/eui',
				'replacement' 	=> '$m[1] . $this->tag($m[2]."&mdash;".$m[4],"span", array("class"=>"nowrap")).$m[5]'
			),
		'expand_no_nbsp_in_nobr' => array(
				'description'	=> 'Удаление nbsp в nobr/nowrap тэгах',
				'function'	=> 'remove_nbsp'
			),
		);

	
		
	protected function remove_nbsp()
	{
		$thetag = $this->tag("###", 'span', array('class' => "nowrap"));
		$arr = explode("###", $thetag);
		$b = preg_quote($arr[0], '/');
		$e = preg_quote($arr[1], '/');
		
		$match = '/(^|[^a-zа-яё])([a-zа-яё]+)\&nbsp\;('.$b.')/iu';
		do {
			$this->_text = preg_replace($match, '\1\3\2 ', $this->_text);
		} while(preg_match($match, $this->_text));

		$match = '/('.$e.')\&nbsp\;([a-zа-яё]+)($|[^a-zа-яё])/iu';
		do {
			$this->_text = preg_replace($match, ' \2\1\3', $this->_text);
		} while(preg_match($match, $this->_text));
		
		$this->_text = $this->preg_replace_e('/'.$b.'.*?'.$e.'/iue', 'str_replace("&nbsp;"," ",$m[0]);' , $this->_text );
	}
	
}

/**PYTHON
    def remove_nbsp(self):
        thetag = self.tag(u"###", u'span', {u'class': u"nowrap"})
        arr = thetag.split(u"###")
        b = re.escape(arr[0])
        e = re.escape(arr[1])
        
        match = u'/(^|[^a-zа-яё])([a-zа-яё]+)\&nbsp\;(' + b + u')/iu'
        p = EMT_Lib.parse_preg_pattern(match)
        while (True):
            self._text = EMT_Lib.preg_replace(match, u"\\1\\3\\2 ", self._text)
            if not (re.match(p['pattern'], self._text, p['flags'])):
                break

        match = u'/(' + e + u')\&nbsp\;([a-zа-яё]+)($|[^a-zа-яё])/iu'
        p = EMT_Lib.parse_preg_pattern(match)
        while (True):
            self._text = EMT_Lib.preg_replace(match, u" \\2\\1\\3", self._text)
            if not (re.match(p['pattern'], self._text, p['flags'])):
                break
        
        self._text = EMT_Lib.preg_replace(u'/' + b + u'.*?' + e + u'/iue', u'EMT_Lib.str_replace("&nbsp;"," ",m.group(0))' , self._text )

PYTHON**/


/**
 * @see EMT_Tret
 */

class EMT_Tret_Nobr extends EMT_Tret
{
	public $title = "Неразрывные конструкции";
	
	public $classes = array(
			'nowrap'           => 'word-spacing:nowrap;',
			);
	
	public $rules = array(
		
		'super_nbsp' => array(
				'description'	=> 'Привязка союзов и предлогов к написанным после словам',
				'pattern' 		=> '/(\s|^|\&(la|bd)quo\;|\>|\(|\&mdash\;\&nbsp\;)([a-zа-яё]{1,2}\s+)([a-zа-яё]{1,2}\s+)?([a-zа-яё0-9\-]{2,}|[0-9])/ieu', 
				'replacement' 	=> '$m[1] . trim($m[3]) . "&nbsp;" . ($m[4] ? trim($m[4]) . "&nbsp;" : "") . $m[5]'
			),
		'nbsp_in_the_end' => array(
				'description'	=> 'Привязка союзов и предлогов к предыдущим словам в случае конца предложения',
				'pattern' 		=> '/([a-zа-яё0-9\-]{3,}) ([a-zа-яё]{1,2})\.( [A-ZА-ЯЁ]|$)/u', 
				'replacement' 	=> '\1&nbsp;\2.\3'
			),
		'phone_builder' => array(
				'description'	=> 'Объединение в неразрывные конструкции номеров телефонов',
				'pattern' 		=> 
					array(
						'/([^\d\+]|^)([\+]?[0-9]{1,3})( |\&nbsp\;|\&thinsp\;)([0-9]{3,4}|\([0-9]{3,4}\))( |\&nbsp\;|\&thinsp\;)([0-9]{2,3})(-|\&minus\;)([0-9]{2})(-|\&minus\;)([0-9]{2})([^\d]|$)/e',
						'/([^\d\+]|^)([\+]?[0-9]{1,3})( |\&nbsp\;|\&thinsp\;)([0-9]{3,4}|[0-9]{3,4})( |\&nbsp\;|\&thinsp\;)([0-9]{2,3})(-|\&minus\;)([0-9]{2})(-|\&minus\;)([0-9]{2})([^\d]|$)/e',
					),
				'replacement'   => 
					array(
						'$m[1]  .(($m[1] == ">" || $m[11] == "<") ? $m[2]." ".$m[4]." ".$m[6]."-".$m[8]."-".$m[10] :$this->tag($m[2]." ".$m[4]." ".$m[6]."-".$m[8]."-".$m[10], "span", array("class"=>"nowrap"))  ).$m[11]',
						'$m[1]  .(($m[1] == ">" || $m[11] == "<") ? $m[2]." ".$m[4]." ".$m[6]."-".$m[8]."-".$m[10] :$this->tag($m[2]." ".$m[4]." ".$m[6]."-".$m[8]."-".$m[10], "span", array("class"=>"nowrap"))  ).$m[11]',
					),
			),
		'ip_address' => array(
				'description'	=> 'Объединение IP-адресов',
				'pattern' 		=> '/(\s|\&nbsp\;|^)(\d{0,3}\.\d{0,3}\.\d{0,3}\.\d{0,3})/ie', 
				'replacement' 	=> '$m[1] . $this->nowrap_ip_address($m[2])'	
			),	
		'spaces_nobr_in_surname_abbr' => array(
				'description'	=> 'Привязка инициалов к фамилиям',			
				'pattern' 		=> 
					array(
						'/(\s|^|\.|\,|\;|\:|\?|\!|\&nbsp\;)([A-ZА-ЯЁ])\.?(\s|\&nbsp\;)?([A-ZА-ЯЁ])(\.(\s|\&nbsp\;)?|(\s|\&nbsp\;))([A-ZА-ЯЁ][a-zа-яё]+)(\s|$|\.|\,|\;|\:|\?|\!|\&nbsp\;)/ue',
						'/(\s|^|\.|\,|\;|\:|\?|\!|\&nbsp\;)([A-ZА-ЯЁ][a-zа-яё]+)(\s|\&nbsp\;)([A-ZА-ЯЁ])\.?(\s|\&nbsp\;)?([A-ZА-ЯЁ])\.?(\s|$|\.|\,|\;|\:|\?|\!|\&nbsp\;)/ue',						
					),						
				'replacement' 	=> 
					array(
						'$m[1].$this->tag($m[2].". ".$m[4].". ".$m[8], "span",  array("class" => "nowrap")).$m[9]',
						'$m[1].$this->tag($m[2]." ".$m[4].". ".$m[6].".", "span",  array("class" => "nowrap")).$m[7]',						
					),
			),
		'nbsp_before_particle' => array(
				'description'	=> 'Неразрывный пробел перед частицей',
				'pattern' 		=> '/(\040|\t)+(ли|бы|б|же|ж)(\&nbsp\;|\.|\,|\:|\;|\&hellip\;|\?|\s)/iue', 
				'replacement' 	=> '"&nbsp;".$m[2] . ($m[3] == "&nbsp;" ? " " : $m[3])'
			),	
		'nbsp_v_kak_to' => array(
				'description'	=> 'Неразрывный пробел в как то',
				'pattern' 		=> '/как то\:/ui', 
				'replacement' 	=> 'как&nbsp;то:'
			),
		'nbsp_celcius' => array(
				'description'	=> 'Привязка градусов к числу',
				'pattern' 		=> '/(\s|^|\>|\&nbsp\;)(\d+)( |\&nbsp\;)?(°|\&deg\;)(C|С)(\s|\.|\!|\?|\,|$|\&nbsp\;|\;)/iu', 
				'replacement' 	=> '\1\2&nbsp;\4C\6'
			),
		'hyphen_nowrap_in_small_words' => array(
				'description'	=> 'Обрамление пятисимвольных слов разделенных дефисом в неразрывные блоки',
				'disabled'      => true,
				'cycled'		=> true,
				'pattern' 		=> '/(\&nbsp\;|\s|\>|^)([a-zа-яё]{1}\-[a-zа-яё]{4}|[a-zа-яё]{2}\-[a-zа-яё]{3}|[a-zа-яё]{3}\-[a-zа-яё]{2}|[a-zа-яё]{4}\-[a-zа-яё]{1}|когда\-то|кое\-как|кой\-кого|вс[её]\-таки|[а-яё]+\-(кась|ка|де))(\s|\.|\,|\!|\?|\&nbsp\;|\&hellip\;|$)/uie',
				'replacement' 	=> '$m[1] . $this->tag($m[2], "span", array("class"=>"nowrap")) . $m[4]',
			),
		'hyphen_nowrap' => array(
				'description'	=> 'Отмена переноса слова с дефисом',
				'disabled'      => true,
				'cycled'		=> true,
				'pattern' 		=> '/(\&nbsp\;|\s|\>|^)([a-zа-яё]+)((\-([a-zа-яё]+)){1,2})(\s|\.|\,|\!|\?|\&nbsp\;|\&hellip\;|$)/uie',
				'replacement' 	=> '$m[1] . $this->tag($m[2].$m[3], "span", array("class"=>"nowrap")) . $m[6]'
			),
		);
		
	/**
	 * Объединение IP-адрессов в неразрывные конструкции (IPv4 only)
	 *
	 * @param unknown_type $triads
	 * @return unknown
	 */
	protected function nowrap_ip_address($triads)
	{
		$triad = explode('.', $triads);
		$addTag = true;
		
		foreach ($triad as $value) {
			$value = (int) $value;
			if ($value > 255) {
				$addTag = false;
				break;
			}
		}
		
		if (true === $addTag) {
			$triads = $this->tag($triads, 'span', array('class' => "nowrap"));
		}
		
		return $triads;
	}
}
/**PYTHON
    # * Объединение IP-адрессов в неразрывные конструкции (IPv4 only)
    # *
    # * @param unknown_type $triads
    # * @return unknown
    def nowrap_ip_address(self, triads):        
        triad = triads.split('.')
        addTag = True
        
        for value in triad:
            value = int(value)
            if (value > 255):
                addTag = false
                break
        
        if (addTag == True):
            triads = self.tag(triads, 'span', {'class': "nowrap"})
        
        return triads

PYTHON**/

/**
 * @see EMT_Tret
 */

class EMT_Tret_Number extends EMT_Tret
{
	public $title = "Числа, дроби, математические знаки";
	
	
	public $rules = array(
		'minus_between_nums' => array(
				'description'	=> 'Расстановка знака минус между числами',
				'pattern' 		=> '/(\d+)\-(\d)/i',
				'replacement' 	=> '\1&minus;\2'
			),
		'minus_in_numbers_range' => array(
				'description'	=> 'Расстановка знака минус между диапозоном чисел',
				'pattern' 		=> '/(^|\s|\&nbsp\;)(\&minus\;|\-)(\d+)(\.\.\.|\&hellip\;)(\s|\&nbsp\;)?(\+|\-|\&minus\;)?(\d+)/ie',
				'replacement' 	=> '$m[1] ."&minus;".$m[3] . $m[4].$m[5].($m[6]=="+"?$m[6]:"&minus;").$m[7]'
			),
		'auto_times_x' => array(
				'description'	=> 'Замена x на символ × в размерных единицах',
				'cycled' 		=> true,
				'pattern' 		=> '/([^a-zA-Z><]|^)(\&times\;)?(\d+)(\040*)(x|х)(\040*)(\d+)([^a-zA-Z><]|$)/u',
				'replacement' 	=> '\1\2\3&times;\7\8'
			),
		'numeric_sub' => array(
				'description'	=> 'Нижний индекс',
				'pattern' 		=> '/([a-zа-яё0-9])\_([\d]{1,3})([^а-яёa-z0-9]|$)/ieu',
				'replacement' 	=> '$m[1] . $this->tag($this->tag($m[2],"small"),"sub") . $m[3]'
			),
		'numeric_sup' => array(
				'description'	=> 'Верхний индекс',
				'pattern' 		=> '/([a-zа-яё0-9])\^([\d]{1,3})([^а-яёa-z0-9]|$)/ieu',
				'replacement' 	=> '$m[1] . $this->tag($this->tag($m[2],"small"),"sup") . $m[3]'
			),
		'simple_fraction' => array(
				'description'	=> 'Замена дробей 1/2, 1/4, 3/4 на соответствующие символы',
				'pattern' 		=> array('/(^|\D)1\/(2|4)(\D)/', '/(^|\D)3\/4(\D)/'),
				'replacement' 	=> array('\1&frac1\2;\3', '\1&frac34;\2')
			),
		'math_chars' => array(
				'description'	=> 'Математические знаки больше/меньше/плюс минус/неравно',
				'simple_replace' => true,
				'pattern' 		=> array('!=', '<=', '>=', '~=', '+-'),
				'replacement' 	=> array('&ne;', '&le;', '&ge;', '&cong;', '&plusmn;' )
			),
			/*
		'split_number_to_triads' => array(
				'description'	=> 'Разбиение числа на триады',
				'cycled'		=> true,
				'pattern' 		=> '/([0-9])([0-9]{3})([^0-9]|$)/u',
				'replacement' 	=> '\1&thinsp;\2\3'
			),
			*/
		'thinsp_between_number_triads' => array(
				'description'	=> 'Объединение триад чисел полупробелом',			
				'pattern' 		=> '/([0-9]{1,3}( [0-9]{3}){1,})(.|$)/ue',
				'replacement' 	=> '($m[3]=="-"? $m[0]:str_replace(" ","&thinsp;",$m[1]).$m[3])'
			),
		'thinsp_between_no_and_number' => array(
				'description'	=> 'Пробел между симоволом номера и числом',			
				'pattern' 		=> '/(№|\&#8470\;)(\s|&nbsp;)*(\d)/iu',
				'replacement' 	=> '&#8470;&thinsp;\3'
			),
		'thinsp_between_sect_and_number' => array(
				'description'	=> 'Пробел между параграфом и числом',			
				'pattern' 		=> '/(§|\&sect\;)(\s|&nbsp;)*(\d+|[IVX]+|[a-zа-яё]+)/ui',
				'replacement' 	=> '&sect;&thinsp;\3'
			),
		);
}


/**
 * @see EMT_Tret
 */

class EMT_Tret_OptAlign extends EMT_Tret
{
	
	public $classes = array(
			'oa_obracket_sp_s' => "margin-right:0.3em;",
			"oa_obracket_sp_b" => "margin-left:-0.3em;",
			"oa_obracket_nl_b" => "margin-left:-0.3em;",
			"oa_comma_b"       => "margin-right:-0.2em;",
			"oa_comma_e"       => "margin-left:0.2em;",
			'oa_oquote_nl' => "margin-left:-0.44em;",
			'oa_oqoute_sp_s' => "margin-right:0.44em;",
			'oa_oqoute_sp_q' => "margin-left:-0.44em;",
		);
	
	/**
	 * Базовые параметры тофа
	 *
	 * @var array
	 */
	public $title = "Оптическое выравнивание";
	public $rules = array(	
		'oa_oquote' => array(
				'description'	=> 'Оптическое выравнивание открывающей кавычки',
				//'disabled'      => true,	
				'pattern' 		=> array(
							'/([a-zа-яё\-]{3,})(\040|\&nbsp\;|\t)(\&laquo\;)/uie',
							'/(\n|\r|^)(\&laquo\;)/ei'
						),
				'replacement' 	=> array(
							'$m[1] . $this->tag($m[2], "span", array("class"=>"oa_oqoute_sp_s")) . $this->tag($m[3], "span", array("class"=>"oa_oqoute_sp_q"))',
							'$m[1] . $this->tag($m[2], "span", array("class"=>"oa_oquote_nl"))',
						),
			),
		'oa_oquote_extra' => array(
			'description'	=> 'Оптическое выравнивание кавычки',
			//'disabled'      => true,	
			'function'	=> 'oaquote_extra'
		),
		'oa_obracket_coma' => array(
				'description'	=> 'Оптическое выравнивание для пунктуации (скобка и запятая)',
				//'disabled'      => true,	
				'pattern' 		=> array(
							'/(\040|\&nbsp\;|\t)\(/ei',
							'/(\n|\r|^)\(/ei',
							'/([а-яёa-z0-9]+)\,(\040+)/iue',
						),
				'replacement' 	=> array(
							'$this->tag($m[1], "span", array("class"=>"oa_obracket_sp_s")) . $this->tag("(", "span", array("class"=>"oa_obracket_sp_b"))',
							'$m[1] . $this->tag("(", "span", array("class"=>"oa_obracket_nl_b"))',
							'$m[1] . $this->tag(",", "span", array("class"=>"oa_comma_b")) . $this->tag(" ", "span", array("class"=>"oa_comma_e"))',
						),
			),					
		
		);
		
	/**
	 * Если стоит открывающая кавычка после <p> надо делать её висячей
	 *
	 * @return  void
	 */	
	protected function oaquote_extra()
	{
		$this->_text = $this->preg_replace_e(
				'/(<' .self::BASE64_PARAGRAPH_TAG . '>)([\040\t]+)?(\&laquo\;)/e', 
				'$m[1] . $this->tag($m[3], "span", array("class"=>"oa_oquote_nl"))',
				$this->_text);
	}
	
	
}


/**
 * @see EMT_Tret
 */

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
				'pattern' 		=> '/([a-zа-яё0-9])(\()/iu',
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


/**
 * @see EMT_Tret
 */

class EMT_Tret_Quote extends EMT_Tret
{
	/**
	 * Базовые параметры тофа
	 *
	 * @var array
	 */
	public $title = "Кавычки";
	
	
    public $rules = array(
		'quotes_outside_a' => array(
				'description'	=> 'Кавычки вне тэга <a>',
				//'pattern' 		=> '/(\<%%\_\_.+?\>)\"(.+?)\"(\<\/%%\_\_.+?\>)/s',
				'pattern' 		=> '/(\<%%\_\_[^\>]+\>)\"(.+?)\"(\<\/%%\_\_[^\>]+\>)/s',
				'replacement' 	=> '"\1\2\3"'
			),
			
		'open_quote' => array(
				'description'	=> 'Открывающая кавычка',
				'pattern' 		=> '/(^|\(|\s|\>|-)(\"|\\\")(\S+)/iue',
				'replacement' 	=> '$m[1] . self::QUOTE_FIRS_OPEN . $m[3]'
			),
		'close_quote' => array(
				'description'	=> 'Закрывающая кавычка',
				'pattern' 		=> '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)((\"|\\\")+)(\.|\&hellip\;|\;|\:|\?|\!|\,|\s|\)|\<\/|$)/uie',
				'replacement' 	=> '$m[1] . str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[2],"\"") ) . $m[4]'
			),		
		'close_quote_adv' => array(
				'description'	=> 'Закрывающая кавычка особые случаи',
				//'pattern' 		=> '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)((\"|\\\"|\&laquo\;)+)(\<.+?\>)(\.|\&hellip\;|\;|\:|\?|\!|\,|\s|\)|\<\/|$)/uie',
				'pattern' 		=> 
					array(
						'/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)((\"|\\\"|\&laquo\;)+)(\<[^\>]+\>)(\.|\&hellip\;|\;|\:|\?|\!|\,|\)|\<\/|$| )/uie',
						'/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)(\s+)((\"|\\\")+)(\s+)(\.|\&hellip\;|\;|\:|\?|\!|\,|\)|\<\/|$| )/uie',
						'/\>(\&laquo\;)\.($|\s|\<)/ui',
						'/\>(\&laquo\;),($|\s|\<|\S)/ui',
					),
				'replacement' 	=> 
					array(
						'$m[1] . str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[2],"\"")+substr_count($m[2],"&laquo;") ) . $m[4]. $m[5]',
						'$m[1] .$m[2]. str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[3],"\"")+substr_count($m[3],"&laquo;") ) . $m[5]. $m[6]',
						'>&raquo;.\2',
						'>&raquo;,\2',
					),
			),
		'open_quote_adv' => array(
				'description'	=> 'Открывающая кавычка особые случаи',
				'pattern' 		=> '/(^|\(|\s|\>)(\"|\\\")(\s)(\S+)/iue',
				'replacement' 	=> '$m[1] . self::QUOTE_FIRS_OPEN .$m[4]'
			),
		'quotation' => array(
				'description'	=> 'Внутренние кавычки-лапки и дюймы',
				'function' => 'build_sub_quotations'
			),
		);

	protected function inject_in($pos, $text)
	{
	    for($i=0;$i<strlen($text);$i++) $this->_text[$pos+$i] = $text[$i];
	}
	
	protected function build_sub_quotations()
	{
		global $__ax,$__ay;
		$okposstack = array('0');
		$okpos = 0;
		$level = 0;
		$off = 0;
		while(true)
		{
			$p = EMT_Lib::strpos_ex($this->_text, array("&laquo;", "&raquo;"), $off);
			if($p===false) break;
			if($p['str'] == "&laquo;")
			{
				if($level>0) if(!$this->is_on('no_bdquotes')) $this->inject_in($p['pos'], self::QUOTE_CRAWSE_OPEN);
				$level++;				
			}
			if($p['str'] == "&raquo;")
			{
				$level--;	
				if($level>0) if(!$this->is_on('no_bdquotes')) $this->inject_in($p['pos'], self::QUOTE_CRAWSE_CLOSE);				
			}
			$off = $p['pos']+strlen($p['str']);
			if($level == 0) 
			{
				$okpos = $off;
				array_push($okposstack, $okpos);
			} elseif($level<0) // уровень стал меньше нуля
			{
				if(!$this->is_on('no_inches'))
				{
					do{
						$lokpos = array_pop($okposstack);
						$k = substr($this->_text, $lokpos, $off-$lokpos);
						$k = str_replace(self::QUOTE_CRAWSE_OPEN, self::QUOTE_FIRS_OPEN, $k);
						$k = str_replace(self::QUOTE_CRAWSE_CLOSE, self::QUOTE_FIRS_CLOSE, $k);
						//$k = preg_replace("/(^|[^0-9])([0-9]+)\&raquo\;/ui", '\1\2&Prime;', $k, 1, $amount);
						
						$amount = 0;
						$__ax = preg_match_all("/(^|[^0-9])([0-9]+)\&raquo\;/ui", $k, $m);
						$__ay = 0;
						if($__ax)
						{
							$k = preg_replace_callback("/(^|[^0-9])([0-9]+)\&raquo\;/ui", 
								create_function('$m','global $__ax,$__ay; $__ay++; if($__ay==$__ax){ return $m[1].$m[2]."&Prime;";} return $m[0];'), 
								$k);
							$amount = 1;
						}
						
						
						
					} while(($amount==0) && count($okposstack));
					
					// успешно сделали замену
					if($amount == 1)
					{
						// заново просмотрим содержимое								
						$this->_text = substr($this->_text, 0, $lokpos). $k . substr($this->_text, $off);
						$off = $lokpos;
						$level = 0;
						continue;
					}
					
					// иначе просто заменим последнюю явно на &quot; от отчаяния
					if($amount == 0)
					{	
						// говорим, что всё в порядке
						$level = 0;		
						$this->_text = substr($this->_text, 0, $p['pos']). '&quot;' . substr($this->_text, $off);
						$off = $p['pos'] + strlen('&quot;');
						$okposstack = array($off);									
						continue;
					}
				}
			}
			
			
		}
		// не совпало количество, отменяем все подкавычки
		if($level != 0 ){
			
			// закрывающих меньше, чем надо
			if($level>0)
			{
				$k = substr($this->_text, $okpos);
				$k = str_replace(self::QUOTE_CRAWSE_OPEN, self::QUOTE_FIRS_OPEN, $k);
				$k = str_replace(self::QUOTE_CRAWSE_CLOSE, self::QUOTE_FIRS_CLOSE, $k);
				$this->_text = substr($this->_text, 0, $okpos). $k;
			}
		}
	}
	
}



/**PYTHON
    def inject_in(self, pos, text):
    	self._text = (self._text[0:pos] if pos>0 else u'') + text + self._text[pos+len(text):]
    	return
        i = 0
        
        while i < len(text):
            self._text[pos+i] = text[i]
            i += 1
    
    def build_sub_quotations(self):
        global __ax,__ay
        
        okposstack = [0]
        okpos = 0
        level = 0
        off = 0
        while True:
            p = EMT_Lib.strpos_ex(self._text, ["&laquo;", "&raquo;"], off)
            
            if isinstance(p, bool) and (p == False):
                break
            if (p['str'] == "&laquo;"):
                if (level>0) and (not self.is_on('no_bdquotes')):
                    self.inject_in(p['pos'], QUOTE_CRAWSE_OPEN) #TODO::: WTF self::QUOTE_CRAWSE_OPEN ???
                level += 1;
                
            if (p['str'] == "&raquo;"):
                level -= 1    
                if (level>0) and (not self.is_on('no_bdquotes')):
                    self.inject_in(p['pos'], QUOTE_CRAWSE_CLOSE) #TODO::: WTF self::QUOTE_CRAWSE_OPEN ???
            
            off = p['pos'] + len(p['str'])

            if(level == 0): 
                okpos = off
                okposstack.append(okpos)
                
            elif (level<0): # // уровень стал меньше нуля
                if(not self.is_on('no_inches')):

                    while (True):
                        lokpos = okposstack.pop(len(okposstack)-1)
                        k = EMT_Lib.substr(self._text,  lokpos, off - lokpos)
                        k = EMT_Lib.str_replace(QUOTE_CRAWSE_OPEN, QUOTE_FIRS_OPEN, k)
                        k = EMT_Lib.str_replace(QUOTE_CRAWSE_CLOSE, QUOTE_FIRS_CLOSE, k)
                        #//$k = preg_replace("/(^|[^0-9])([0-9]+)\&raquo\;/ui", '\1\2&Prime;', $k, 1, $amount);
                        
                        amount = 0
                        m = re.findall("(^|[^0-9])([0-9]+)\&raquo\;", k, re.I | re.U)
                        __ax = len(m)
                        __ay = 0
                        if(__ax):
                            def quote_extra_replace_function(m):
                                global __ax,__ay
                                __ay+=1
                                if __ay==__ax:
                                    return m.group(1)+m.group(2)+"&Prime;"
                                return m.group(0)
                            
                            k = re.sub("(^|[^0-9])([0-9]+)\&raquo\;",                                 
                                    quote_extra_replace_function,
                                k, 0, re.I | re.U);
                            amount = 1
                        
                        if not ((amount==0) and len(okposstack)):
                            break
                    
                    
                    #// успешно сделали замену
                    if (amount == 1):
                        #// заново просмотрим содержимое                                
                        self._text = EMT_Lib.substr(self._text, 0, lokpos) + k + EMT_Lib.substr(self._text, off)
                        off = lokpos
                        level = 0
                        continue
                    
                    #// иначе просто заменим последнюю явно на &quot; от отчаяния
                    if (amount == 0):
                        #// говорим, что всё в порядке
                        level = 0
                        self._text = EMT_Lib.substr(self._text, 0, p['pos']) + '&quot;' + EMT_Lib.substr(self._text, off)
                        off = p['pos'] + len('&quot;')
                        okposstack = [off]
                        continue
                    
        #// не совпало количество, отменяем все подкавычки
        if (level != 0 ):
            
            #// закрывающих меньше, чем надо
            if (level>0):
                k = EMT_Lib.substr(self._text, okpos)
                k = EMT_Lib.str_replace(QUOTE_CRAWSE_OPEN, QUOTE_FIRS_OPEN, k)
                k = EMT_Lib.str_replace(QUOTE_CRAWSE_CLOSE, QUOTE_FIRS_CLOSE, k)
                self._text = EMT_Lib.substr(self._text, 0, okpos) + k
         



PYTHON**/



/**
 * @see EMT_Tret
 */

class EMT_Tret_Space extends EMT_Tret
{
	public $title = "Расстановка и удаление пробелов";
	
	public $domain_zones = array('ru','ру','com','ком','org','орг', 'уа', 'ua');
	
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
						'/(\040|\t|\&nbsp\;|^)([a-zа-яё0-9]+)(\040|\t|\&nbsp\;)?\.([а-яёa-z]{4,})/iu', 
						'/(\040|\t|\&nbsp\;|^)([a-zа-яё0-9]+)\.([а-яёa-z]{1,3})/iue', 
						),
				'replacement' 	=> array(
						'\1\2. \4',
						'$m[1].$m[2]."." .(in_array(EMT_Lib::strtolower($m[3]), $this->domain_zones)? "":" "). $m[3]'
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


/**
 * @see EMT_Tret
 */

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


/**
 * @see EMT_Tret
 */

class EMT_Tret_Text extends EMT_Tret
{
	public $classes = array(
			'nowrap'           => 'word-spacing:nowrap;',
		);
	
	/**
	 * Базовые параметры тофа
	 *
	 * @var array
	 */
	public $title = "Текст и абзацы";
	public $rules = array(	
		'auto_links' => array(
				'description'	=> 'Выделение ссылок из текста',
				'pattern' 		=> '/(\s|^)(http|ftp|mailto|https)(:\/\/)([^\s\,\!\<]{4,})(\s|\.|\,|\!|\?|\<|$)/ieu', 
				'replacement' 	=> '$m[1] . $this->tag((substr($m[4],-1)=="."?substr($m[4],0,-1):$m[4]), "a", array("href" => $m[2].$m[3].(substr($m[4],-1)=="."?substr($m[4],0,-1):$m[4]))) . (substr($m[4],-1)=="."?".":"") .$m[5]'
			),
		'email' => array(
				'description'	=> 'Выделение эл. почты из текста',
				'pattern' 		=> '/(\s|^|\&nbsp\;|\()([a-z0-9\-\_\.]{2,})\@([a-z0-9\-\.]{2,})\.([a-z]{2,6})(\)|\s|\.|\,|\!|\?|$|\<)/e',
				'replacement' 	=> '$m[1] . $this->tag($m[2]."@".$m[3].".".$m[4], "a", array("href" => "mailto:".$m[2]."@".$m[3].".".$m[4])) . $m[5]'
			),
		'no_repeat_words' => array(
				'description'	=> 'Удаление повторяющихся слов',
				'disabled'      => true,
				'pattern' 		=> array(
					'/([а-яё]{3,})( |\t|\&nbsp\;)\1/iu',
					'/(\s|\&nbsp\;|^|\.|\!|\?)(([А-ЯЁ])([а-яё]{2,}))( |\t|\&nbsp\;)(([а-яё])\4)/eu',
					),
				'replacement' 	=> array(
					'\1',
					'$m[1].($m[7] === EMT_Lib::strtolower($m[3]) ? $m[2] : $m[2].$m[5].$m[6] )',
					)
			),			
		'paragraphs' => array(
				'description'	=> 'Простановка параграфов',
				'function'	=> 'build_paragraphs'
			),
		'breakline' => array(
				'description'	=> 'Простановка переносов строк',
				'function'	=> 'build_brs'
			),
		
		);

    /**
	 * Расстановка защищенных тегов параграфа (<p>...</p>) и переноса строки
	 *
	 * @return  void
	 */	
	protected function do_paragraphs($text) {
		$text = str_replace("\r\n","\n",$text);
		$text = str_replace("\r","\n",$text);
		$text = '<' . self::BASE64_PARAGRAPH_TAG . '>' . trim($text) . '</' . self::BASE64_PARAGRAPH_TAG . '>';
		//$text = $this->preg_replace_e('/([\040\t]+)?(\n|\r){2,}/e', '"</" . self::BASE64_PARAGRAPH_TAG . "><" .self::BASE64_PARAGRAPH_TAG . ">"', $text);
		//$text = $this->preg_replace_e('/([\040\t]+)?(\n){2,}/e', '"</" . self::BASE64_PARAGRAPH_TAG . "><" .self::BASE64_PARAGRAPH_TAG . ">"', $text);
		$text = $this->preg_replace_e('/([\040\t]+)?(\n)+([\040\t]*)(\n)+/e', '$m[1]."</" . self::BASE64_PARAGRAPH_TAG . ">".EMT_Lib::iblock($m[2].$m[3])."<" .self::BASE64_PARAGRAPH_TAG . ">"', $text);
		//$text = $this->preg_replace_e('/([\040\t]+)?(\n)+([\040\t]*)(\n)+/e', '"</" . self::BASE64_PARAGRAPH_TAG . ">"."<" .self::BASE64_PARAGRAPH_TAG . ">"', $text);
		//может от открвающего до закрывающего ?!
		$text = preg_replace('/\<' . self::BASE64_PARAGRAPH_TAG . '\>('.EMT_Lib::INTERNAL_BLOCK_OPEN.'[a-zA-Z0-9\/=]+?'.EMT_Lib::INTERNAL_BLOCK_CLOSE.')?\<\/' . self::BASE64_PARAGRAPH_TAG . '\>/s', "", $text);
		return $text;
	}
		
	/**
	 * Расстановка защищенных тегов параграфа (<p>...</p>) и переноса строки
	 *
	 * @return  void
	 */	
	protected function build_paragraphs()
	{
		$r = mb_strpos($this->_text, '<' . self::BASE64_PARAGRAPH_TAG . '>' );
		$p = EMT_Lib::rstrpos($this->_text, '</' . self::BASE64_PARAGRAPH_TAG . '>' )	;
		if(($r!== false) && ($p !== false)) {			
			
			$beg = mb_substr($this->_text,0,$r);
			$end = mb_substr($this->_text,$p+mb_strlen('</' . self::BASE64_PARAGRAPH_TAG . '>'));			
			$this->_text = 
							(trim($beg) ? $this->do_paragraphs($beg). "\n":"") .'<' . self::BASE64_PARAGRAPH_TAG . '>'.
							mb_substr($this->_text,$r + mb_strlen('<' . self::BASE64_PARAGRAPH_TAG . '>'),$p -($r + mb_strlen('<' . self::BASE64_PARAGRAPH_TAG . '>')) ).'</' . self::BASE64_PARAGRAPH_TAG . '>'.
							(trim($end) ? "\n".$this->do_paragraphs($end) :"") ;
		} else {
			$this->_text = $this->do_paragraphs($this->_text);
		}
	}
	
	/**
	 * Расстановка защищенных тегов параграфа (<p>...</p>) и переноса строки
	 *
	 * @return  void
	 */
	protected function build_brs()
	{
		$this->_text = $this->preg_replace_e('/(\<\/' . self::BASE64_PARAGRAPH_TAG . '\>)([\r\n \t]+)(\<' . self::BASE64_PARAGRAPH_TAG . '\>)/mse', '$m[1].EMT_Lib::iblock($m[2]).$m[3]', $this->_text);
		
		if (!preg_match('/\<' . self::BASE64_BREAKLINE_TAG . '\>/', $this->_text)) {
			$this->_text = str_replace("\r\n","\n",$this->_text);
			$this->_text = str_replace("\r","\n",$this->_text);
			//$this->_text = $this->preg_replace_e('/(\n|\r)/e', '"<" . self::BASE64_BREAKLINE_TAG . ">"', $this->_text);
			$this->_text = $this->preg_replace_e('/(\n)/e', '"<" . self::BASE64_BREAKLINE_TAG . ">\n"', $this->_text);
		}
	}
}
/**PYTHON

    def do_paragraphs(self, text):
        text = EMT_Lib.str_replace(u"\r\n",u"\n",text)
        text = EMT_Lib.str_replace(u"\r",u"\n",text)
        text = u'<' + BASE64_PARAGRAPH_TAG + u'>' + text.strip() + u'</' + BASE64_PARAGRAPH_TAG + u'>'
        text = self.preg_replace('/([\040\t]+)?(\n)+([\040\t]*)(\n)+/e', '(u"" if m.group(1) is None else m.group(1))+u"</" + BASE64_PARAGRAPH_TAG + u">"+EMT_Lib.iblock(m.group(2)+m.group(3))+u"<" +BASE64_PARAGRAPH_TAG + u">"', text)
        text = self.preg_replace('/\<' + BASE64_PARAGRAPH_TAG + '\>(' + INTERNAL_BLOCK_OPEN + '[a-zA-Z0-9\/=]+?' + INTERNAL_BLOCK_CLOSE + ')?\<\/' + BASE64_PARAGRAPH_TAG + '\>/s', "", text)
        return text
        
        
    def build_paragraphs(self):
        r = self._text.find( u'<' + BASE64_PARAGRAPH_TAG + u'>' )
        p = self._text.rfind( u'</'+  BASE64_PARAGRAPH_TAG + u'>' )    
        if(( r != -1) and (p != -1)):
            
            beg = EMT_Lib.substr(self._text,0,r);
            end = EMT_Lib.substr(self._text,p+len(u'</' + BASE64_PARAGRAPH_TAG + u'>'))           
            self._text = (self.do_paragraphs(beg)+ u"\n" if beg.strip() else u"") +u'<' + BASE64_PARAGRAPH_TAG + u'>'+EMT_Lib.substr(self._text,r + len(u'<' + BASE64_PARAGRAPH_TAG + u'>'),p -(r + len(u'<' + BASE64_PARAGRAPH_TAG + u'>')) )+u'</' + BASE64_PARAGRAPH_TAG + u'>'+( u"\n"+self.do_paragraphs(end) if end.strip() else u"") 
        else:
            self._text = self.do_paragraphs(self._text)
            
    def build_brs(self):
        self._text = self.preg_replace('/(\<\/' + BASE64_PARAGRAPH_TAG + '\>)([\r\n \t]+)(\<' + BASE64_PARAGRAPH_TAG + '\>)/mse', 'm.group(1)+EMT_Lib.iblock(m.group(2))+m.group(3)', self._text);
        
        if (not re.match('\<' + BASE64_BREAKLINE_TAG + '\>', self._text)):
            self._text = EMT_Lib.str_replace("\r\n","\n",self._text)
            self._text = EMT_Lib.str_replace("\r","\n",self._text)
            self._text = self.preg_replace('/(\n)/e', '"<" + BASE64_BREAKLINE_TAG + ">\\n"', self._text)
       

PYTHON**/

/**
* Evgeny Muravjev Typograph, http://mdash.ru
* Version: 3.0 Gold Master
* Release Date: September 28, 2013
* Authors: Evgeny Muravjev & Alexander Drutsa  
*/



/**
 * Основной класс типографа Евгения Муравьёва
 * реализует основные методы запуска и рабыоты типографа
 *
 */
class EMT_Base 
{
	private $_text = "";
	private $inited = false;

	/**
	 * Список Трэтов, которые надо применить к типогрфированию
	 *
	 * @var array
	 */
	protected $trets = array() ; 
	protected $trets_index = array() ; 
	protected $tret_objects = array() ; 

	public $ok             = false;
	public $debug_enabled  = false;
	public $logging        = false;
	public $logs           = array();
	public $errors         = array();
	public $debug_info     = array();
	
	private $use_layout = false;
	private $class_layout_prefix = false;
	private $use_layout_set = false;
	public $disable_notg_replace = false;
	public $remove_notg = false;
	
	public $settings = array();
	
	protected function log($str, $data = null)
	{
		if(!$this->logging) return;
		$this->logs[] = array('class' => '', 'info' => $str, 'data' => $data);
	}
	
	protected function tret_log($tret, $str, $data = null)
	{
		$this->logs[] = array('class' => $tret, 'info' => $str, 'data' => $data);
	}
		
	protected function error($info, $data = null)
	{
		$this->errors[] = array('class' => '', 'info' => $info, 'data' => $data);
		$this->log("ERROR $info", $data );		
	}
	
	protected function tret_error($tret, $info, $data = null)
	{
		$this->errors[] = array('class' => $tret, 'info' => $info, 'data' => $data);
	}
	
	protected function debug($class, $place, &$after_text, $after_text_raw = "")
	{
		if(!$this->debug_enabled) return;
		$this->debug_info[] = array(
				'tret'  => $class == $this ? false: true,
				'class' => is_object($class)? get_class($class) : $class,
				'place' => $place,
				'text'  => $after_text,
				'text_raw'  => $after_text_raw,
			);
	}
	
	
	
	protected $_safe_blocks = array();	
	
	
	/**
	 * Включить режим отладки, чтобы посмотреть последовательность вызовов
	 * третов и правил после
	 *
	 */
	public function debug_on()
	{
		$this->debug_enabled = true;
	}
	
	/**
	 * Включить режим отладки, чтобы посмотреть последовательность вызовов
	 * третов и правил после
	 *
	 */
	public function log_on()
	{
		$this->logging = true;
	}
	
	/**
     * Добавление защищенного блока
     *
     * <code>
     *  Jare_Typograph_Tool::addCustomBlocks('<span>', '</span>');
     *  Jare_Typograph_Tool::addCustomBlocks('\<nobr\>', '\<\/span\>', true);
     * </code>
     * 
     * @param 	string $id идентификатор
     * @param 	string $open начало блока
     * @param 	string $close конец защищенного блока
     * @param 	string $tag тэг
     * @return  void
     */
    private function _add_safe_block($id, $open, $close, $tag)
    {
    	$this->_safe_blocks[$id] = array(
    			'id' => $id,
    			'tag' => $tag,
    			'open' =>  $open,
    			'close' =>  $close,
    		);
    }
    
    /**
     * Список защищенных блоков
     *
     * @return 	array
     */
    public function get_all_safe_blocks()
    {
    	return $this->_safe_blocks;
    }
    
    /**
     * Удаленного блока по его номеру ключа
     *
     * @param 	string $id идентифиактор защищённого блока 
     * @return  void
     */
    public function remove_safe_block($id)
    {
    	unset($this->_safe_blocks[$id]);
    }
    
    
    /**
     * Добавление защищенного блока
     *
     * @param 	string $tag тэг, который должен быть защищён
     * @return  void
     */
    public function add_safe_tag($tag)
    {      	
    	$open = preg_quote("<", '/'). $tag."[^>]*?" .  preg_quote(">", '/');
    	$close = preg_quote("</$tag>", '/');
    	$this->_add_safe_block($tag, $open, $close, $tag);
    	return true;
    }
    
    
    /**
     * Добавление защищенного блока
     *
     * @param 	string $open начало блока
     * @param 	string $close конец защищенного блока
     * @param 	bool $quoted специальные символы в начале и конце блока экранированы
     * @return  void
     */
    public function add_safe_block($id, $open, $close, $quoted = false)
    {
    	$open = trim($open);
    	$close = trim($close);
    	
    	if (empty($open) || empty($close)) 
    	{
    		return false;
    	}
    	
    	if (false === $quoted) 
    	{
    		$open = preg_quote($open, '/');
            $close = preg_quote($close, '/');
    	}
    	
    	$this->_add_safe_block($id, $open, $close, "");
    	return true;
    }
    
    
    /**
     * Сохранение содержимого защищенных блоков
     *
     * @param   string $text
     * @param   bool $safe если true, то содержимое блоков будет сохранено, иначе - раскодировано. 
     * @return  string
     */
    public function safe_blocks($text, $way, $show = true)
    {
    	if (count($this->_safe_blocks)) 
    	{
    		$safeType = true === $way ? "EMT_Lib::encrypt_tag(\$m[2])" : "stripslashes(EMT_Lib::decrypt_tag(\$m[2]))";
       		foreach ($this->_safe_blocks as $block) 
       		{
        		$text = preg_replace_callback("/({$block['open']})(.+?)({$block['close']})/s",   create_function('$m','return $m[1].'.$safeType . '.$m[3];')   , $text);
        	}
    	}
    	
    	return $text;
    }
    
    
     /**
     * Декодирование блоков, которые были скрыты в момент типографирования
     *
     * @param   string $text
     * @return  string
     */
    public function decode_internal_blocks($text)
    {
		return EMT_Lib::decode_internal_blocks($text);
    }
	
	
	private function create_object($tret)
	{
		// если класса нету, попытаемся его прогрузить, например, если стандартный
		if(!class_exists($tret))
		{
			if(preg_match("/^EMT_Tret_([a-zA-Z0-9_]+)$/",$tret, $m))
			{
				$tname = $m[1];
				$fname = str_replace("_"," ",$tname);
				$fname = ucwords($fname);
				$fname = str_replace(" ",".",$fname);
				//if(file_exists("EMT.Tret.".$fname.".php"))
				{					
				}				
			}
		}
		if(!class_exists($tret))
		{
			$this->error("Класс $tret не найден. Пожалуйста, подргузите нужный файл.");
			return null;
		}
		
		$obj = new $tret();
		$obj->EMT     = $this;
		$obj->logging = $this->logging;
		return $obj;
	}
	
	private function get_short_tret($tretname)
	{
		if(preg_match("/^EMT_Tret_([a-zA-Z0-9_]+)$/",$tretname, $m))
		{
			return $m[1];
		}
		return $tretname;
	}
	
	private function _init()
	{
		foreach($this->trets as $tret)
		{
			if(isset($this->tret_objects[$tret])) continue;
			$obj = $this->create_object($tret);
			if($obj == null) continue;
			$this->tret_objects[$tret] = $obj;
		}
		
		if(!$this->inited)
		{
			$this->add_safe_tag('pre');
			$this->add_safe_tag('script');
			$this->add_safe_tag('style');
			$this->add_safe_tag('notg');
			$this->add_safe_block('span-notg', '<span class="_notg_start"></span>', '<span class="_notg_end"></span>');
		}
		$this->inited = true;
	}
	
	
	
	
	
	/**
	 * Инициализация класса, используется чтобы задать список третов или
	 * спсиок защищённых блоков, которые можно использовать.
	 * Такде здесь можно отменить защищённые блоки по умлочнаию
	 *
	 */
	public function init()
	{
		
	}
	
	/**
	 * Добавить Трэт, 
	 *
	 * @param mixed $class - имя класса трета, или сам объект
	 * @param string $altname - альтернативное имя, если хотим например иметь два одинаоковых терта в обработке
 	 * @return unknown
	 */
	public function add_tret($class, $altname = false)
	{
		if(is_object($class))
		{
			if(!is_a($class, "EMT_Tret"))
			{
				$this->error("You are adding Tret that doesn't inherit base class EMT_Tret", get_class($class));
				return false;	
			}
			
			$class->EMT     = $this;
			$class->logging = $this->logging;
			$this->tret_objects[($altname ? $altname : get_class($class))] = $class;
			$this->trets[] = ($altname ? $altname : get_class($class));
			return true;
		}
		if(is_string($class))
		{
			$obj = $this->create_object($class);
			if($obj === null)
				return false;
			$this->tret_objects[($altname ? $altname : $class)] = $obj;
			$this->trets[] = ($altname ? $altname : $class);
			return true;
		}
		$this->error("Чтобы добавить трэт необходимо передать имя или объект");
		return false;
	}
	
	/**
	 * Получаем ТРЕТ по идентивикатору, т.е. заванию класса
	 *
	 * @param unknown_type $name
	 */
	public function get_tret($name)
	{
		if(isset($this->tret_objects[$name])) return $this->tret_objects[$name];
		foreach($this->trets as $tret)
		{
			if($tret == $name)
			{
				$this->_init();
				return $this->tret_objects[$name];
			}
			if($this->get_short_tret($tret) == $name)
			{
				$this->_init();
				return $this->tret_objects[$tret];
			}
		}
		$this->error("Трэт с идентификатором $name не найден");
		return false;
	}
	
	/**
	 * Задаём текст для применения типографа
	 *
	 * @param string $text
	 */
	public function set_text($text)
	{
		$this->_text = $text;
	}
	
	
	
	/**
	 * Запустить типограф на выполнение
	 *
	 */
	public function apply($trets = null)
	{
		$this->ok = false;
		
		$this->init();
		$this->_init();		
		
		$atrets = $this->trets;
		if(is_string($trets)) $atrets = array($trets);
		elseif(is_array($trets)) $atrets = $trets;
		
		$this->debug($this, 'init', $this->_text);
		
		$this->_text = $this->safe_blocks($this->_text, true);
		$this->debug($this, 'safe_blocks', $this->_text);
		
		$this->_text = EMT_Lib::safe_tag_chars($this->_text, true);
		$this->debug($this, 'safe_tag_chars', $this->_text);
		
		$this->_text = EMT_Lib::clear_special_chars($this->_text);
		$this->debug($this, 'clear_special_chars', $this->_text);
		
		foreach ($atrets as $tret) 		
		{
			// если установлен режим разметки тэгов то выставим его
			if($this->use_layout_set)
				$this->tret_objects[$tret]->set_tag_layout_ifnotset($this->use_layout);
				
			if($this->class_layout_prefix)
				$this->tret_objects[$tret]->set_class_layout_prefix($this->class_layout_prefix);
			
			// влючаем, если нужно
			if($this->debug_enabled) $this->tret_objects[$tret]->debug_on();
			if($this->logging) $this->tret_objects[$tret]->logging = true;
						
			// применяем трэт
			//$this->tret_objects[$tret]->set_text(&$this->_text);
			$this->tret_objects[$tret]->set_text($this->_text);
			$this->tret_objects[$tret]->apply();
			
			// соберём ошибки если таковые есть
			if(count($this->tret_objects[$tret]->errors)>0)
				foreach($this->tret_objects[$tret]->errors as $err ) 
					$this->tret_error($tret, $err['info'], $err['data']);
			
			// логгирование 
			if($this->logging)
				if(count($this->tret_objects[$tret]->logs)>0)
					foreach($this->tret_objects[$tret]->logs as $log ) 
						$this->tret_log($tret, $log['info'], $log['data']);				
			
			// отладка
			if($this->debug_enabled)
				foreach($this->tret_objects[$tret]->debug_info as $di)
				{
					$unsafetext = $di['text'];
					$unsafetext = EMT_Lib::safe_tag_chars($unsafetext, false);
					$unsafetext = $this->safe_blocks($unsafetext, false);		
					$this->debug($tret, $di['place'], $unsafetext, $di['text']);
				}
					
			
		}
		
		
		$this->_text = $this->decode_internal_blocks($this->_text);
		$this->debug($this, 'decode_internal_blocks', $this->_text);
		
		if($this->is_on('dounicode'))
		{
			EMT_Lib::convert_html_entities_to_unicode($this->_text);
		}
		
		$this->_text = EMT_Lib::safe_tag_chars($this->_text, false);
		$this->debug($this, 'unsafe_tag_chars', $this->_text);
		
		$this->_text = $this->safe_blocks($this->_text, false);		
		$this->debug($this, 'unsafe_blocks', $this->_text);
		
		if(!$this->disable_notg_replace)
		{
			$repl = array('<span class="_notg_start"></span>', '<span class="_notg_end"></span>');
			if($this->remove_notg) $repl = "";
			$this->_text = str_replace( array('<notg>','</notg>'), $repl , $this->_text);
		}
		$this->_text = trim($this->_text);
		$this->ok = (count($this->errors)==0);
		return $this->_text;
	}
	
	/**
	 * Получить содержимое <style></style> при использовании классов
	 * 
	 * @param bool $list false - вернуть в виде строки для style или как массив
	 * @param bool $compact не выводить пустые классы
	 * @return string|array
	 */
	public function get_style($list = false, $compact = false)
	{
		$this->_init();
		
		$res = array();
		foreach ($this->trets as $tret) 		
		{
			$arr =$this->tret_objects[$tret]->classes;
			if(!is_array($arr)) continue;
			foreach($arr as $classname => $str)
			{
				if(($compact) && (!$str)) continue;
				$clsname = ($this->class_layout_prefix ? $this->class_layout_prefix : "" ).(isset($this->tret_objects[$tret]->class_names[$classname]) ? $this->tret_objects[$tret]->class_names[$classname] :$classname);
				$res[$clsname] = $str;
			}
		}
		if($list) return $res;
		$str = "";
		foreach($res as $k => $v)
		{
			$str .= ".$k { $v }\n";
		}
		return $str;
	}
	
	
	
	
	
	/**
	 * Установить режим разметки,
	 *   EMT_Lib::LAYOUT_STYLE - с помощью стилей
	 *   EMT_Lib::LAYOUT_CLASS - с помощью классов
	 *   EMT_Lib::LAYOUT_STYLE|EMT_Lib::LAYOUT_CLASS - оба метода
	 *
	 * @param int $layout
	 */
	public function set_tag_layout($layout = EMT_Lib::LAYOUT_STYLE)
	{
		$this->use_layout = $layout;
		$this->use_layout_set = true;
	}
	
	/**
	 * Установить префикс для классов
	 *
	 * @param string|bool $prefix если true то префикс 'emt_', иначе то, что передали
	 */
	public function set_class_layout_prefix($prefix )
	{
		$this->class_layout_prefix = $prefix === true ? "emt_" : $prefix;
	}
	
	/**
	 * Включить/отключить правила, согласно карте
	 * Формат карты:
	 *    'Название трэта 1' => array ( 'правило1', 'правило2' , ...  )
	 *    'Название трэта 2' => array ( 'правило1', 'правило2' , ...  )
	 *
	 * @param array $map
	 * @param boolean $disable если ложно, то $map соотвествует тем правилам, которые надо включить
	 *                         иначе это список правил, которые надо выключить
	 * @param boolean $strict строго, т.е. те которые не в списку будут тоже обработаны
	 */
	public function set_enable_map($map, $disable = false, $strict = true)
	{
		if(!is_array($map)) return;
		$trets = array();
		foreach($map as $tret => $list)
		{
			$tretx = $this->get_tret($tret);
			if(!$tretx)
			{
				$this->log("Трэт $tret не найден при применении карты включаемых правил");
				continue;
			}
			$trets[] = $tretx;
			
			if($list === true) // все
			{
				$tretx->activate(array(), !$disable ,  true);
			} elseif(is_string($list)) {
				$tretx->activate(array($list), $disable ,  $strict);
			} elseif(is_array($list)) {
				$tretx->activate($list, $disable ,  $strict);
			}
		}
		if($strict)
		{
			foreach($this->trets as $tret)
			{
				if(in_array($this->tret_objects[$tret], $trets)) continue;
				$this->tret_objects[$tret]->activate(array(), $disable ,  true);
			}
		}
		
	}
	
	
	/**
	 * Установлена ли настройка
	 *
	 * @param string $key
	 */
	public function is_on($key)
	{
		if(!isset($this->settings[$key])) return false;
		$kk = $this->settings[$key];
		return ((strtolower($kk)=="on") || ($kk === "1") || ($kk === true) || ($kk === 1));
	}
	
	
	/**
	 * Установить настройку
	 *
	 * @param mixed $selector
	 * @param string $setting
	 * @param mixed $value
	 */
	protected function doset($selector, $key, $value)
	{
		$tret_pattern = false;
		$rule_pattern = false;
		//if(($selector === false) || ($selector === null) || ($selector === false) || ($selector === "*")) $type = 0;
		if(is_string($selector))
		{
			if(strpos($selector,".")===false)
			{
				$tret_pattern = $selector;
			} else {
				$pa = explode(".", $selector);
				$tret_pattern = $pa[0];
				array_shift($pa);
				$rule_pattern = implode(".", $pa);
			}
		}
		EMT_Lib::_process_selector_pattern($tret_pattern);
		EMT_Lib::_process_selector_pattern($rule_pattern);
		if($selector == "*") $this->settings[$key] = $value;
		
		foreach ($this->trets as $tret) 		
		{
			$t1 = $this->get_short_tret($tret);
			if(!EMT_Lib::_test_pattern($tret_pattern, $t1))	if(!EMT_Lib::_test_pattern($tret_pattern, $tret)) continue;
			$tret_obj = $this->get_tret($tret);
			if($key == "active")
			{
				foreach($tret_obj->rules as $rulename => $v)
				{
					if(!EMT_Lib::_test_pattern($rule_pattern, $rulename)) continue;
					if((strtolower($value) === "on") || ($value===1) || ($value === true) || ($value=="1")) $tret_obj->enable_rule($rulename);
					if((strtolower($value) === "off") || ($value===0) || ($value === false) || ($value=="0")) $tret_obj->disable_rule($rulename);
				}
			} else {
				if($rule_pattern===false)
				{
					$tret_obj->set($key, $value);
				} else {
					foreach($tret_obj->rules as $rulename => $v)
					{
						if(!EMT_Lib::_test_pattern($rule_pattern, $rulename)) continue;
						$tret_obj->set_rule($rulename, $key, $value);
					}
				}
			}
		}
	}
	
	
	/**
	 * Установить настройки для тертов и правил
	 * 	1. если селектор является массивом, то тогда утсановка правил будет выполнена для каждого
	 *     элемента этого массива, как отдельного селектора.
	 *  2. Если $key не является массивом, то эта настрока будет проставлена согласно селектору
	 *  3. Если $key массив - то будет задана группа настроек
	 *       - если $value массив , то настройки определяются по ключам из массива $key, а значения из $value
	 *       - иначе, $key содержит ключ-значение как массив  
	 *
	 * @param mixed $selector
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function set($selector, $key , $value = false)
	{
		if(is_array($selector)) 
		{
			foreach($selector as $val) $this->set($val, $key, $value);
			return;
		}
		if(is_array($key))
		{
			foreach($key as $x => $y)
			{
				if(is_array($value))
				{
					$kk = $y;
					$vv = $value[$x];
				} else {
					$kk = $x;
					$vv = $y;
				}
				$this->set($selector, $kk, $vv);
			}
		}
		$this->doset($selector, $key, $value);
	}
	
	
	/**
	 * Возвращает список текущих третов, которые установлены
	 *
	 */
	public function get_trets_list()
	{
		return $this->trets;
	}
	
	/**
	 * Установка одной метанастройки
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function do_setup($name, $value)
	{
		
	}
	
	
	/**
	 * Установить настройки
	 *
	 * @param array $setupmap
	 */
	public function setup($setupmap)
	{
		if(!is_array($setupmap)) return;
		
		if(isset($setupmap['map']) || isset($setupmap['maps']))
		{
			if(isset($setupmap['map']))
			{
				$ret['map'] = $test['params']['map'];
				$ret['disable'] = $test['params']['map_disable'];
				$ret['strict'] = $test['params']['map_strict'];
				$test['params']['maps'] = array($ret);
				unset($setupmap['map']);
				unset($setupmap['map_disable']);
				unset($setupmap['map_strict']);
			}
			if(is_array($setupmap['maps']))
			{
				foreach($setupmap['maps'] as $map)
				{ 
					$this->set_enable_map
								($map['map'], 
								isset($map['disable']) ? $map['disable'] : false,
								isset($map['strict']) ? $map['strict'] : false 
							);
				}
			}
			unset($setupmap['maps']);
		}
		
		
		foreach($setupmap as $k => $v) $this->do_setup($k , $v);
	}
	
	
	
	
}


class EMTypograph extends EMT_Base 
{
	public $trets = array('EMT_Tret_Quote', 'EMT_Tret_Dash', 'EMT_Tret_Symbol', 'EMT_Tret_Punctmark', 'EMT_Tret_Number',  'EMT_Tret_Space', 'EMT_Tret_Abbr',  'EMT_Tret_Nobr', 'EMT_Tret_Date', 'EMT_Tret_OptAlign', 'EMT_Tret_Etc', 'EMT_Tret_Text');
	
	
	protected $group_list  = array(
		'Quote'     => true,
		'Dash'      => true,
		'Nobr'      => true,
		'Symbol'    => true,
		'Punctmark' => true,
		'Number'    => true,
		'Date'      => true,
		'Space'     => true,
		'Abbr'      => true,		
		'OptAlign'  => true,
		'Text'      => true,
		'Etc'       => true,		
	);
	protected $all_options = array(
	
		'Quote.quotes' => array( 'description' => 'Расстановка «кавычек-елочек» первого уровня', 'selector' => "Quote.*quote" ),
		'Quote.quotation' => array( 'description' => 'Внутренние кавычки-лапки', 'selector' => "Quote", 'setting' => 'no_bdquotes', 'reversed' => true ),
							
		'Dash.to_libo_nibud' => 'direct',
		'Dash.iz_za_pod' => 'direct',
		'Dash.ka_de_kas' => 'direct',
		
		'Nobr.super_nbsp' => 'direct',
		'Nobr.nbsp_in_the_end' => 'direct',
		'Nobr.phone_builder' => 'direct',
		'Nobr.ip_address' => 'direct',
		'Nobr.spaces_nobr_in_surname_abbr' => 'direct',
		'Nobr.nbsp_celcius' => 'direct',		
		'Nobr.hyphen_nowrap_in_small_words' => 'direct',
		'Nobr.hyphen_nowrap' => 'direct',
		'Nobr.nowrap' => array('description' => 'Nobr (по умолчанию) & nowrap', 'disabled' => true, 'selector' => '*', 'setting' => 'nowrap' ),
		
		'Symbol.tm_replace'     => 'direct',
		'Symbol.r_sign_replace' => 'direct',
		'Symbol.copy_replace' => 'direct',
		'Symbol.apostrophe' => 'direct',
		'Symbol.degree_f' => 'direct',
		'Symbol.arrows_symbols' => 'direct',
		'Symbol.no_inches' => array( 'description' => 'Расстановка дюйма после числа', 'selector' => "Quote", 'setting' => 'no_inches', 'reversed' => true ),
		
		'Punctmark.auto_comma' => 'direct',
		'Punctmark.hellip' => 'direct',
		'Punctmark.fix_pmarks' => 'direct',
		'Punctmark.fix_excl_quest_marks' => 'direct',
		'Punctmark.dot_on_end' => 'direct',
		
		'Number.minus_between_nums' => 'direct',
		'Number.minus_in_numbers_range' => 'direct',
		'Number.auto_times_x' => 'direct',
		'Number.simple_fraction' => 'direct',
		'Number.math_chars' => 'direct',
		//'Number.split_number_to_triads' => 'direct',
		'Number.thinsp_between_number_triads' => 'direct',
		'Number.thinsp_between_no_and_number' => 'direct',
		'Number.thinsp_between_sect_and_number' => 'direct',
		
		'Date.years' => 'direct',
		'Date.mdash_month_interval' => 'direct',
		'Date.nbsp_and_dash_month_interval' => 'direct',
		'Date.nobr_year_in_date' => 'direct',
		
		'Space.many_spaces_to_one' => 'direct',	
		'Space.clear_percent' => 'direct',	
		'Space.clear_before_after_punct' => array( 'description' => 'Удаление пробелов перед и после знаков препинания в предложении', 'selector' => 'Space.remove_space_before_punctuationmarks'),
		'Space.autospace_after' => array( 'description' => 'Расстановка пробелов после знаков препинания', 'selector' => 'Space.autospace_after_*'),
		'Space.bracket_fix' => array( 'description' => 'Удаление пробелов внутри скобок, а также расстановка пробела перед скобками', 
				'selector' => array('Space.nbsp_before_open_quote', 'Punctmark.fix_brackets')),
				
		'Abbr.nbsp_money_abbr' => 'direct',		
		'Abbr.nobr_vtch_itd_itp' => 'direct',		
		'Abbr.nobr_sm_im' => 'direct',		
		'Abbr.nobr_acronym' => 'direct',		
		'Abbr.nobr_locations' => 'direct',		
		'Abbr.nobr_abbreviation' => 'direct',		
		'Abbr.ps_pps' => 'direct',		
		'Abbr.nbsp_org_abbr' => 'direct',		
		'Abbr.nobr_gost' => 'direct',		
		'Abbr.nobr_before_unit_volt' => 'direct',		
		'Abbr.nbsp_before_unit' => 'direct',		
		
		'OptAlign.all' => array( 'description' => 'Inline стили или CSS', 'hide' => true, 'selector' => 'OptAlign.*'),
		'OptAlign.oa_oquote' => 'direct',	
		'OptAlign.oa_obracket_coma' => 'direct',	
		'OptAlign.oa_oquote_extra' => 'direct',	
		'OptAlign.layout' => array( 'description' => 'Inline стили или CSS' ),
		
		'Text.paragraphs' => 'direct',
		'Text.auto_links' => 'direct',
		'Text.email' => 'direct',
		'Text.breakline' => 'direct',
		'Text.no_repeat_words' => 'direct',
		
		
		//'Etc.no_nbsp_in_nobr' => 'direct',		
		'Etc.unicode_convert' => array('description' => 'Преобразовывать html-сущности в юникод', 'selector' => '*', 'setting' => 'dounicode' , 'disabled' => true),
	
	);
	
	/**
	 * Получить список имеющихся опций
	 *
	 * @return array
	 *     all    - полный список
	 *     group  - сгруппрованный по группам
	 */
	public function get_options_list()
	{
		$arr['all'] = array();
		$bygroup = array();
		foreach($this->all_options as $opt => $op)
		{
			$arr['all'][$opt] = $this->get_option_info($opt);
			$x = explode(".",$opt);
			$bygroup[$x[0]][] = $opt;
		}
		$arr['group'] = array();
		foreach($this->group_list as $group => $ginfo)
		{
			if($ginfo === true)
			{
				$tret = $this->get_tret($group);
				if($tret) $info['title'] = $tret->title; else $info['title'] = "Не определено";
			} else {
				$info = $ginfo;
			}
			$info['name'] = $group;
			$info['options'] = array();
			if(is_array($bygroup[$group])) foreach($bygroup[$group] as $opt) $info['options'][] = $opt;
			$arr['group'][] = $info;
		}
		return $arr;
	}
	
	
	/**
	 * Получить информацию о настройке
	 *
	 * @param string $key
	 * @return array|false
	 */
	protected function get_option_info($key)
	{
		if(!isset($this->all_options[$key])) return false;
		if(is_array($this->all_options[$key])) return $this->all_options[$key];
		
		if(($this->all_options[$key] == "direct") || ($this->all_options[$key] == "reverse"))
		{
			$pa = explode(".", $key);
			$tret_pattern = $pa[0];
			$tret = $this->get_tret($tret_pattern);
			if(!$tret) return false;		
			if(!isset($tret->rules[$pa[1]])) return false;
			$array = $tret->rules[$pa[1]];
			$array['way'] = $this->all_options[$key];
			return $array;
		}
		return false;		
	}
	
	
	/**
	 * Установка одной метанастройки
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function do_setup($name, $value)
	{
		if(!isset($this->all_options[$name])) return;
		
		// эта настрока связана с правилом ядра
		if(is_string($this->all_options[$name]))
		{
			$this->set($name, "active", $value );
			return ;
		}
		if(is_array($this->all_options[$name]))
		{
			if(isset($this->all_options[$name]['selector']))
			{
				$settingname = "active";
				if(isset($this->all_options[$name]['setting'])) $settingname = $this->all_options[$name]['setting'];
				$this->set($this->all_options[$name]['selector'], $settingname, $value);
			}
		}
		
		if($name == "OptAlign.layout")
		{
			if($value == "style") $this->set_tag_layout(EMT_Lib::LAYOUT_STYLE);
			if($value == "class") $this->set_tag_layout(EMT_Lib::LAYOUT_CLASS);
		}
		
	}
	
	/**
	 * Запустить типограф со стандартными параметрами
	 *
	 * @param string $text
	 * @param array $options
	 * @return string
	 */
	public static function fast_apply($text, $options = null)
	{
		$obj = new self();
		if(is_array($options)) $obj->setup($options);
		$obj->set_text($text);
		return $obj->apply();
	}
}


?>