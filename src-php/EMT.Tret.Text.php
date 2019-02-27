<?php
/**
 * @see EMT_Tret
 */
require_once('EMT.Tret.php');

class EMT_Tret_Text extends EMT_Tret
{
	public $classes = array(
			'nowrap'           => 'white-space:nowrap;',
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
				'pattern' 		=> '/(\s|^|\&nbsp\;|\()([a-z0-9\-\_\.]{2,})\@([a-z0-9\-\.]{2,})\.([a-z]{2,6})(\)|\s|\.|\,|\!|\?|$|\<)/ie',
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
?>