<?php
/**
 * @see EMT_Tret
 */
require_once('EMT.Tret.php');

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
				'pattern' 		=> '/(^|\(|\s|\>|-)((\"|\\\")+)(\S+)/iue',
				'replacement' 	=> '$m[1] . str_repeat(self::QUOTE_FIRS_OPEN, substr_count($m[2],"\"") ) . $m[4]'
			),
		'close_quote' => array(
				'description'	=> 'Закрывающая кавычка',
				'pattern' 		=> '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:|\+|\%|\@|\#|\$|\*)((\"|\\\")+)(\.|\&hellip\;|\;|\:|\?|\!|\,|\s|\)|\<\/|\<|$)/uie',
				'replacement' 	=> '$m[1] . str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[2],"\"") ) . $m[4]'
			),		
		'close_quote_adv' => array(
				'description'	=> 'Закрывающая кавычка особые случаи',
				//'pattern' 		=> '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)((\"|\\\"|\&laquo\;)+)(\<.+?\>)(\.|\&hellip\;|\;|\:|\?|\!|\,|\s|\)|\<\/|$)/uie',
				'pattern' 		=> 
					array(
						'/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:|\+|\%|\@|\#|\$|\*)((\"|\\\"|\&laquo\;)+)(\<[^\>]+\>)(\.|\&hellip\;|\;|\:|\?|\!|\,|\)|\<\/|$| )/uie',
						'/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:|\+|\%|\@|\#|\$|\*)(\s+)((\"|\\\")+)(\s+)(\.|\&hellip\;|\;|\:|\?|\!|\,|\)|\<\/|$| )/uie',
						'/\>(\&laquo\;)\.($|\s|\<)/ui',
						'/\>(\&laquo\;),($|\s|\<|\S)/ui',
						'/\>(\&laquo\;):($|\s|\<|\S)/ui',
						'/\>(\&laquo\;);($|\s|\<|\S)/ui',
						'/\>(\&laquo\;)\)($|\s|\<|\S)/ui',
						'/((\"|\\\")+)$/uie',
					),
				'replacement' 	=> 
					array(
						'$m[1] . str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[2],"\"")+substr_count($m[2],"&laquo;") ) . $m[4]. $m[5]',
						'$m[1] .$m[2]. str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[3],"\"")+substr_count($m[3],"&laquo;") ) . $m[5]. $m[6]',
						'>&raquo;.\2',
						'>&raquo;,\2',
						'>&raquo;:\2',
						'>&raquo;;\2',
						'>&raquo;)\2',
						'str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[1],"\"") )',
					),
			),
		'open_quote_adv' => array(
				'description'	=> 'Открывающая кавычка особые случаи',
				'pattern' 		=> '/(^|\(|\s|\>)(\"|\\\")(\s)(\S+)/iue',
				'replacement' 	=> '$m[1] . self::QUOTE_FIRS_OPEN .$m[4]'
			),
		'close_quote_adv_2' => array(
				'description'	=> 'Закрывающая кавычка последний шанс',
				'pattern' 		=> '/(\S)((\"|\\\")+)(\.|\&hellip\;|\;|\:|\?|\!|\,|\s|\)|\<\/|\<|$)/uie',
				'replacement' 	=> '$m[1] . str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[2],"\"") ) . $m[4]'
			),
		'quotation' => array(
				'description'	=> 'Внутренние кавычки-лапки и дюймы',
				'function' => 'build_sub_quotations'
			),
		);

	protected function inject_in($pos, $text, &$thetext)
	{
	    for($i=0;$i<strlen($text);$i++) $thetext[$pos+$i] = $text[$i];
	}
	
	protected function build_sub_quotations()
	{
		global $__ax,$__ay;
		
		$exp = strpos($this->_text, "</cA===>")!==false ? "</cA===>" : (strpos($this->_text,"\r\n")!==false ? "\r\n\r\n" :"\n\n");
		
		$texts_in = explode($exp, $this->_text);
		$texts_out = array();
		
		foreach($texts_in as $textx) {
		
			$okposstack = array('0');
			$okpos = 0;
			$level = 0;
			$off = 0;
			while(true)
			{
				$p = EMT_Lib::strpos_ex($textx, array("&laquo;", "&raquo;"), $off);
				if($p===false) break;
				if($p['str'] == "&laquo;")
				{
					if($level>0) if(!$this->is_on('no_bdquotes')) $this->inject_in($p['pos'], self::QUOTE_CRAWSE_OPEN, $textx);
					$level++;				
				}
				if($p['str'] == "&raquo;")
				{
					$level--;	
					if($level>0) if(!$this->is_on('no_bdquotes')) $this->inject_in($p['pos'], self::QUOTE_CRAWSE_CLOSE, $textx);				
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
							$k = substr($textx, $lokpos, $off-$lokpos);
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
							$textx = substr($textx, 0, $lokpos). $k . substr($textx, $off);
							$off = $lokpos;
							$level = 0;
							continue;
						}
						
						// иначе просто заменим последнюю явно на &quot; от отчаяния
						if($amount == 0)
						{	
							// говорим, что всё в порядке
							$level = 0;		
							$textx = substr($textx, 0, $p['pos']). '&quot;' . substr($textx, $off);
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
					$k = substr($textx, $okpos);
					$k = str_replace(self::QUOTE_CRAWSE_OPEN, self::QUOTE_FIRS_OPEN, $k);
					$k = str_replace(self::QUOTE_CRAWSE_CLOSE, self::QUOTE_FIRS_CLOSE, $k);
					$textx = substr($textx, 0, $okpos). $k;
				}
			}
			$texts_out[] = $textx;
		}
		$this->_text = implode($exp, $texts_out);
	}
	
}



/**PYTHON
    def inject_in(self, pos, text, chtext):
    	chtext = (chtext[0:pos] if pos>0 else u'') + text + chtext[pos+len(text):]
    	return chtext
    
    def build_sub_quotations(self):
        global __ax,__ay
        
        exp = "</cA===>" if self._text.find( "</cA===>")>=0 else ( "\r\n\r\n" if self._text.find( "\r\n")>=0 else "\n\n")
        
        texts_in = self._text.split(exp)
        texts_out = []
        
        for textx in texts_in:
            okposstack = [0]
            okpos = 0
            level = 0
            off = 0
            while True:
                p = EMT_Lib.strpos_ex(textx, ["&laquo;", "&raquo;"], off)
                
                if isinstance(p, bool) and (p == False):
                    break
                if (p['str'] == "&laquo;"):
                    if (level>0) and (not self.is_on('no_bdquotes')):
                        textx = self.inject_in(p['pos'], QUOTE_CRAWSE_OPEN, textx) #TODO::: WTF self::QUOTE_CRAWSE_OPEN ???
                    level += 1;
                    
                if (p['str'] == "&raquo;"):
                    level -= 1    
                    if (level>0) and (not self.is_on('no_bdquotes')):
                        textx = self.inject_in(p['pos'], QUOTE_CRAWSE_CLOSE, textx) #TODO::: WTF self::QUOTE_CRAWSE_OPEN ???
                
                off = p['pos'] + len(p['str'])
    
                if(level == 0): 
                    okpos = off
                    okposstack.append(okpos)
                    
                elif (level<0): # // уровень стал меньше нуля
                    if(not self.is_on('no_inches')):
    
                        while (True):
                            lokpos = okposstack.pop(len(okposstack)-1)
                            k = EMT_Lib.substr(textx,  lokpos, off - lokpos)
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
                            textx = EMT_Lib.substr(textx, 0, lokpos) + k + EMT_Lib.substr(textx, off)
                            off = lokpos
                            level = 0
                            continue
                        
                        #// иначе просто заменим последнюю явно на &quot; от отчаяния
                        if (amount == 0):
                            #// говорим, что всё в порядке
                            level = 0
                            textx = EMT_Lib.substr(textx, 0, p['pos']) + '&quot;' + EMT_Lib.substr(textx, off)
                            off = p['pos'] + len('&quot;')
                            okposstack = [off]
                            continue
                        
            #// не совпало количество, отменяем все подкавычки
            if (level != 0 ):
                
                #// закрывающих меньше, чем надо
                if (level>0):
                    k = EMT_Lib.substr(textx, okpos)
                    k = EMT_Lib.str_replace(QUOTE_CRAWSE_OPEN, QUOTE_FIRS_OPEN, k)
                    k = EMT_Lib.str_replace(QUOTE_CRAWSE_CLOSE, QUOTE_FIRS_CLOSE, k)
                    textx = EMT_Lib.substr(textx, 0, okpos) + k
            texts_out.append(textx)
            
        self._text = exp.join(texts_out) 



PYTHON**/


?>