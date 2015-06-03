<?php
/**
* Evgeny Muravjev Typograph, http://mdash.ru
* Version: 3.0 Gold Master
* Release Date: September 28, 2013
* Authors: Evgeny Muravjev & Alexander Drutsa  
*/


require_once("EMT.Lib.php");
require_once("EMT.Tret.php");

/**
 * Основной класс типографа Евгения Муравьёва
 * реализует основные методы запуска и работы типографа
 *
 */
class EMT_Base 
{
	private $_text = "";
	private $inited = false;

	/**
	 * Список Трэтов, которые надо применить к типографированию
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
    	$this->_safe_blocks[] = array(
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
    	foreach($this->_safe_blocks as $k => $block) {
    		if($block['id']==$id) unset($this->_safe_blocks[$k]);
    	}
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
    		$safeblocks = true === $way ? $this->_safe_blocks : array_reverse($this->_safe_blocks);
       		foreach ($safeblocks as $block) 
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
					require_once("EMT.Tret.".$fname.".php");
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
	 * список защищённых блоков, которые можно использовать.
	 * Также здесь можно отменить защищённые блоки по умлочнаию
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
	 * Получаем ТРЕТ по идентификатору, т.е. названию класса
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
	 * @param boolean $strict строго, т.е. те которые не в списке будут тоже обработаны
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
	 * 	1. если селектор является массивом, то тогда установка правил будет выполнена для каждого
	 *     элемента этого массива, как отдельного селектора.
	 *  2. Если $key не является массивом, то эта настройка будет проставлена согласно селектору
	 *  3. Если $key массив - то будет задана группа настроек
	 *       - если $value массив , то настройки определяются по ключам из массива $key, а значения из $value
	 *       - иначе, $key содержит ключ-значение как массив  
	 *  4. $exact_match - если true тогда array selector будет соответсвовать array $key, а не произведению массивов
	 *
	 * @param mixed $selector
	 * @param mixed $key
	 * @param mixed $value
	 * @param mixed $exact_match
	 */
	public function set($selector, $key , $value = false, $exact_match = false)
	{
		if($exact_match && is_array($selector) && is_array($key) && count($selector)==count($key)) {
			$idx = 0;
			foreach($key as $x => $y){
				if(is_array($value))
				{
					$kk = $y;
					$vv = $value[$x];
				} else {
					$kk = ( $value ? $y : $x );
					$vv = ( $value ? $value : $y );
				}
				$this->set($selector[$idx], $kk , $vv);
				$idx++;
			}
			return ;
		}
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
					$kk = ( $value ? $y : $x );
					$vv = ( $value ? $value : $y );
				}
				$this->set($selector, $kk, $vv);
			}
			return ;
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
				
		'Abbr.nbsp_money_abbr' => array( 'description' => 'Форматирование денежных сокращений (расстановка пробелов и привязка названия валюты к числу)', 
				'selector' => array('Abbr.nbsp_money_abbr', 'Abbr.nbsp_money_abbr_rev')),
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
		
		'OptAlign.all' => array( 'description' => 'Все настройки оптического выравнивания', 'hide' => true, 'selector' => 'OptAlign.*'),
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
		'Etc.unicode_convert' => array('description' => 'Преобразовывать html-сущности в юникод', 'selector' => array('*', 'Etc.nobr_to_nbsp'), 'setting' => array('dounicode','active'), 'exact_selector' => true ,'disabled' => true),
		'Etc.nobr_to_nbsp' => 'direct',
	
	);
	
	/**
	 * Получить список имеющихся опций
	 *
	 * @return array
	 *     all    - полный список
	 *     group  - сгруппированный по группам
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
				$this->set($this->all_options[$name]['selector'], $settingname, $value, isset($this->all_options[$name]['exact_selector']));
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