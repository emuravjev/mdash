<?php


require_once("../EMT.php");
require_once("../lib/lib.php");
require_once("../lib/EMT.Test.php");
require_once("../lib/fs.php");
require_once("../lib/external/finediff.php");

header('Content-Type: text/html; charset=utf-8');

if($_GET['clean']) require("clean.php");

class EMTTester extends EMTTest {
	
	function doflush()
	{
		static $output_handler = null;
		if ($output_handler === null) $output_handler = @ini_get('output_handler');
		if ($output_handler == 'ob_gzhandler') return;
		flush();
		if (function_exists('ob_flush') AND function_exists('ob_get_length') AND ob_get_length() !== false) @ob_flush();
		else if (function_exists('ob_end_flush') AND function_exists('ob_start') AND function_exists('ob_get_length') AND ob_get_length() !== false)
		{
			@ob_end_flush();
			@ob_start();
		}
	}
	
	
	public function build_posle_nuzhno_raznica_block($type, $posle, $nuzhno, $raznica = false)
	{
		if($type == 0) $text = "";
		if($type == 1) $text = "<b>Инлайн стили</b>";
		if($type == 2) $text = "<b>Классы</b>";
		
		
		$razn = "";
		if($raznica)
		{
			
			return <<<HTML
		$text
		<div style="margin-left: 5px ;font-family: monospace; font-weight: bold">
			ПОСЛЕ: $posle<br>
			НУЖНО: $nuzhno<br>
			РАЗЛЧ: $raznica<br>
		</div>
HTML;
		}
		return <<<HTML
		$text
		<div style="margin-left: 5px ">
			<span style="font-family: monospace; font-weight: bold">ПОСЛЕ:</span> $posle<br>
			<span style="font-family: monospace; font-weight: bold">НУЖНО:</span> $nuzhno<br>
		</div>
HTML;
		
		
	}
	
	public function on_tested($num, $test, $ret)
	{
		$cc     = urlencode($test['text']);
		$text   = nl2br(htmlspecialchars($test['text']));
		$result = htmlspecialchars($test['result']);
		$out    = htmlspecialchars($ret['result']);
		
		$diffx = new FineDiff($out, $result, FineDiff::$characterGranularity);
		$diff = $diffx->renderDiffToHTML2();
		//$diffy = new FineDiff($ret['result'], $test['result'], FineDiff::$characterGranularity);
		//$diff_text = $diffy->renderDiffToHTML2();
		
		$block_html_inline = $this->build_posle_nuzhno_raznica_block($test['result_classes']? 1: 0, $out, $result, $diff);
		$block_text_inline = $this->build_posle_nuzhno_raznica_block($test['result_classes']? 1: 0, $ret['result'], $test['text']);
		$block_html_classes = "";
		$block_text_classes = "";
		if(isset($test['result_classes']) && $test['result_classes'])
		{
			$diffc_h = new FineDiff(htmlspecialchars($ret['result_classes']), htmlspecialchars($test['result_classes']), FineDiff::$characterGranularity);
			$diff_h = $diffc_h->renderDiffToHTML2();
			//$diffc_t = new FineDiff($ret['result_classes'], $test['result_classes'], FineDiff::$characterGranularity);
			//$diff_t = $diffc_t->renderDiffToHTML2();
			
			$block_html_classes = "<br>".$this->build_posle_nuzhno_raznica_block(2, htmlspecialchars($ret['result_classes']), htmlspecialchars($test['result_classes']), $diff_h);
			$block_text_classes = "<br>".$this->build_posle_nuzhno_raznica_block(2, $ret['result_classes'], $test['result_classes']);
		}
		
		// повторное тестирование<br>
		$second_html = "";
		$second_text = "";
		if(isset($ret['result_second']) && $ret['result_second'])
		{
			$out_second    = htmlspecialchars($ret['result_second']);
			$diffx2 = new FineDiff($out_second, $result, FineDiff::$characterGranularity);
			$diff_second = $diffx2->renderDiffToHTML2();
			$block_second_html_inline = $this->build_posle_nuzhno_raznica_block($test['result_classes']? 1: 0, $out_second, $result, $diff_second);
			$block_second_text_inline = $this->build_posle_nuzhno_raznica_block($test['result_classes']? 1: 0, $ret['result_second'], $test['text']);
			$block_second_html_classes = "";
			$block_second_text_classes = "";
			if(isset($test['result_classes_second']) && $test['result_classes_second'])
			{
				$diffc_h2 = new FineDiff(htmlspecialchars($ret['result_classes_second']), htmlspecialchars($test['result_classes']), FineDiff::$characterGranularity);
				$diff_second_h = $diffc_h2->renderDiffToHTML2();
				
				$block_second_html_classes = "<br>".$this->build_posle_nuzhno_raznica_block(2, htmlspecialchars($ret['result_classes_second']), htmlspecialchars($test['result_classes']), $diff_second_h);
				$block_second_text_classes = "<br>".$this->build_posle_nuzhno_raznica_block(2, $ret['result_classes_second'], $test['result_classes']);
			}
		
			$second_html = <<<HTML
			<br>
			<h4>Повторное типографирование</h4>
			$block_second_html_inline
			$block_second_html_classes
HTML;
			$second_text = <<<HTML
			<br>
			<h4>Повторное типографирование</h4>
			$block_second_text_inline
			$block_second_text_classes
HTML;
		}
		
		
		$infoblock = <<<HTML
		<div id="test{$num}_block" style="display:none;margin: 30px;">
		<div id="test{$num}_block_html" >
			<a href="#test$num" class="togglehtmlcode">Посмотреть текст</a>&nbsp;&nbsp;<a href="debug.php?inputdata=$cc" target="_blank">Открыть в отладчике</a><br>
			ДО: $text<br>
			<br>
			$block_html_inline
			$block_html_classes
			$second_html
		</div>
		<div id="test{$num}_block_code" style="display:none;">
			<a href="#test$num" class="togglehtmlcode">Посмотреть HTML</a>&nbsp;&nbsp;<a href="debug.php?inputdata=$cc" target="_blank">Открыть в отладчике</a><br>
			ДО: $text<br>
			<br>
			$block_text_inline
			$block_text_classes
			$second_text
		</div>
		
		</div>
HTML;
		$cnt = $this->get_test_count();
		
		// первый тест в группе тестов
		if($test['grnum'] == 1)
		{
			echo <<<HTML
	</ul>
	<h3 style="margin-left: 20px">$test[grtitle]</h3>
	<ul style="list-style: none">
HTML;
		}
		
		echo "		<li id='test$num'><span class='openlink ".($ret['error']? "witherror" : "")."'><a href='#test$num' class='seetest'><img style='margin-right: 0.7em' src='../misc/".($ret['error'] ? "no": "yes") .".png'>Тест $test[grid]".sprintf("%02d",$test['grnum'])." (".sprintf("%02d",$num)."/$cnt)". (isset($test['title'])?": ".$test['title']."" : "")."</a></span> $infoblock</li>\n";
		$this->doflush();
	}	
}

$noecho = isset($noecho)?$noecho:false;
if(!$noecho) {
	
	
	$phpself = $_SERVER['PHP_SELF'];
	echo <<<HTML
	<html>
		<head>
			<title>Автотест - Типограф Евгения Муравьёва версия 3.0</title>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
			<script>
			$(document).ready(function(){
				$('.seetest').click(function(){
					$($(this).attr('href')+'_block').toggle();
				});
				$('.togglehtmlcode').click(function(){
					$($(this).attr('href')+'_block_html').toggle();
					$($(this).attr('href')+'_block_code').toggle();
				});
			});
			</script>
			<style>
	del {
		color: red;
		background: #fdd;
		text-decoration: none;
	}
	ins {
		color: green;
		background: #dfd;
		text-decoration: none;
	}
	.openlink A:link  {text-decoration: none; color: black;}
	.openlink A:visited  {text-decoration: none; color: black;}
	.openlink A:active  {text-decoration: none; color: black;}
	.openlink A:hover  {text-decoration: underline; color: red;}
	.witherror  {font-weight: bold;}
			</style>
		</head>
		<body>
		<h1>Типограф Евгения Муравьёва. Автотесты.</h1>
	<br>
HTML;
	
	if($_GET['run'])
	{
		$list = array();
		if($_GET['run'] == "all")
		{
			$list = FS::list_only_files("../tests/", '/^test\.[0-9a-z\.\-_]+\.php$/i');
			
		} else {
			preg_match("/[a-z0-9\.\-_]+/i", $_GET['run'], $m);
			$f = $m[0];
			if(file_exists("../tests/test.$f.php"))
			{
				$list[] = "test.$f.php";
			}
		}
		
		
		$type = $_GET['run']=="all" ? "полное": "только группы ".$_GET['run'];
		echo <<<HTML
		Тестирование <b>$type</b>. <span style="display:none" id="results"></span>
		<br>
		<ul style="list-style: none">
HTML;
		if(count($list)>0)
		{
			$tester = new EMTTester();
			$tester->double_test = isset($_GET['double_test']);
			$tester->set_typoclass("EMTypograph");
			foreach($list as $file) include("../tests/$file");
			$ok = $tester->testit();
			$result = $tester->results;
		} else {
			$result['error'] = "В каталоге tests тесты не обнаружены";
		}
		
		if(isset($result['error']) && $result['error'])
		{
			$text = $result['error'];	
		}else {
			if($ok)
			{
				$text = "УСПЕХ. ТЕСТОВ ПРОЙДЕНО: ".$tester->get_test_count() . ".";	
			} else {
				$cnt = $tester->get_test_count();
				$err = count($tester->results['errors']);
				$text = "ОБНАРУЖЕНЫ ОШИБКИ. ПРОЙДЕНО ".($cnt-$err)." из $cnt.";	
			}
		}	
		
		
		echo <<<HTML
		</ul>
		<script>
		$(document).ready(function(){
			$('#results').text('$text');
			$('#results').show();
		});
		</script>
HTML;
	
	} else {
		echo "<b>Полное тестирование: <a href='$phpself?run=all&clean=1&double_test=1'>ЗАПУСК</a></b><br />";
		echo "Тестирование без повторного проверки: <a href='$phpself?run=all&clean=1'>ЗАПУСК</a></b><br />";
	}
	
	echo <<<HTML
	<br>
	<br>
		</body>
	</html>
HTML;
}	

?>