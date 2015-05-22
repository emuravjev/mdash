<?php

class EMTTest {
	
	public $list       = array();
	private $info       = array();	
	private $_class     = false;
	public $results     = array();
	private $count      = 0;
	public $double_test = false;
	
	public $grid       = "A";
	public $grtitle    = "Неклассифицированные";
	
	
	protected function _dotest($test, $layout)
	{
		$typograph = new $this->_class;
		$typograph->set_tag_layout($layout);
		$ok = true;
		if(isset($test['safetags'])) {
			$safetags = array();
			if(is_string($test['safetags'])) $safetags = array($test['safetags']);
			else $safetags = $test['safetags'];
			foreach($safetags as $st) {
				$typograph->add_safe_tag($st);
			}
		}
		if(isset($test['params']) && is_array($test['params'])) $typograph->setup($test['params']);
		
		/*
		if(isset($test['params']['map']) || isset($test['params']['maps']))
		{
			if(isset($test['params']['map']))
			{
				$ret['map'] = $test['params']['map'];
				$ret['disable'] = $test['params']['map_disable'];
				$ret['strict'] = $test['params']['map_strict'];
				$test['params']['maps'] = array($ret);
			}
			if(is_array($test['params']['maps']))
			{
				foreach($test['params']['maps'] as $map)
				{ 
					$typograph->set_enable_map
								($map['map'], 
								isset($map['disable']) ? $map['disable'] : false,
								isset($map['strict']) ? $map['strict'] : false 
							);
				}
			}
		} else {
			//if(isset($test['params']['ctl']))
			if(isset($test['params']['no_paragraph']) && $test['params']['no_paragraph'])
			{
				$typograph->get_tret('Etc')->disable_rule('paragraphs');
			}
		}*/
		$typograph->set_text($test['text']);
		return $typograph->apply();
	}
	
	
	protected function dotest($test)
	{
		$result = $this->_dotest($test, 1); // STYLE
		$ret['result'] = $result; 
		
		if($test['result_classes'])
		{
			$result = $this->_dotest($test, 2); // CLASSES
			$ret['result_classes'] = $result; 
		}		
		
		$ret['error'] = false;
		if($ret['result'] !== $test['result']) $ret['error'] = true;
		if($test['result_classes']) if($ret['result_classes'] !== $test['result_classes']) $ret['error'] = true;
		
		if(!$ret['error'])
		{
			// повторное типографирование оттипографированного текста
			if($this->double_test)
			{
				$test['text'] = $ret['result'];
				$ret['result_second'] = $this->_dotest($test, 1); // STYLE
				if($test['result_classes'])
				{
					$test['text'] = $ret['result_classes'];
					$ret['result_classes_second'] = $this->_dotest($test, 2); // CLASSES
				}
				
				// повторное тестирование
				if($ret['result_second'] !== $test['result']) $ret['error'] = true;
				if($test['result_classes']) if($ret['result_classes_second'] !== $test['result_classes']) $ret['error'] = true;
			}
			
			
		}
		
		
		return $ret;
	}
	
	/**
	 * Установить имя типографа класс
	 *
	 * @param string $class
	 */
	public function set_typoclass($class)
	{
		$this->_class = $class;
	}
	
	/**
	 * Установить группу тестов
	 *
	 * @param string $id
	 * @param string $title
	 */
	public function set_group($id, $title)
	{
		$this->grid    = $id;
		$this->grtitle = $title;
	}
	
	
	/**
	 * Добавить тест
	 *
	 * @param string $text - тестируемый тест
	 * @param string $result - результат, который должен получится
	 * @param string $id - Идентификатор теста или название теста, если массив, то оба
	 * @param array/null $params - параметра (то что надо отключить/включить) и прочее, информация о тесте
	 */
	public function add_test($text, $result, $result_classes = null, $id = null, $params = null, $safetags = null)
	{
		$arr = array(
			'text'  => $text,
			'result' => $result,
			'result_classes' => $result_classes,
		);
		if($params) $arr['params'] = $params;
		if($safetags) $arr['safetags'] = $safetags;
		if($id) 
		{
			if(is_array($id))
			{
				if(isset($id['title'])) $arr['title'] = $id['title'];
				if(isset($id['id'])) $arr['id'] = $id['id'];
			}
			if(is_string($id))  $arr['title'] = $id;
		}
		$arr['grid'] = $this->grid;
		$arr['grtitle'] = $this->grtitle;
		$this->list[$this->count] = $arr;		
		$this->count++;
	}

	
	
	/**
	 * Получить информацию об тесте
	 *   
	 *
	 * @param int $num
	 * @return array 
	 *         'text' - входной текст
	 *         'result' - то, что должно получится
	 *         'params' - переданые параметры
	 */
	public function get_test_info($num)
	{
		if($num >= $this->count) return false;
		return $this->list[$num];
	}
	
	/**
	 * Получить количество тестов
	 *
	 * @return int
	 */
	public function get_test_count()
	{
		return $this->count;
	}
	
	
	/**
	 * Протестировать типограф
	 *
	 * @return unknown
	 */
	public function testit()
	{
		if(count($this->list)==0) return true;
		$this->info    = array();
		$this->results = array();
		if(!$this->_class) {
			$this->results['error'] = "Не задан класс типографа";
			return false;
		}
		$this->results['raw'] = array();
		$this->results['errors'] = array();
		$num = 0;
		$num2 = 0;
		$prevgr = "A";
		foreach($this->list as $test)
		{
			if($test['grid']!=$prevgr) $num2=0;
			$prevgr = $test['grid'];
			$num++;
			$num2++;						
			$ret = $this->dotest($test);
			$test['grnum'] = $num2;
			$this->results['raw'][$num] = $ret;
			if($ret['error']) 
			{
				$this->results['errors'][] = array(
						'num'     => $num,
					);
			}
			$this->on_tested($num, $test, $ret);
		}
		if(count($this->results['errors'])>0) return false;
		return true;
	}
	
	/**
	 * То случается при обработке 
	 *
	 * @param ште $num
	 * @param array $test
	 * @param array $ret
	 */
	public function on_tested($num, $test, $ret)
	{
		
	}
	
}


?>