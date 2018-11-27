<?php

require_once("../lib/lib.php");
require_once("../EMT.php");


header('Content-Type: text/html; charset=utf-8');


$debuglist     = "";
$ok = false;

$options = $_REQUEST['options'];
$typograph = new EMTypograph();

$optdisplay = 'style="display:none"';
$useoptions = "0";
$use_class_checked = "";
$use_style_checked = "checked";

if($_REQUEST['inputdata'])
{
	$ok = true;
	$inputdata = htmlspecialchars($_REQUEST['inputdata']);
	
	if($_REQUEST['useoptions'])
	{
		$optdisplay = "";
		$useoptions = "1";
	}
	
	$typograph->debug_on();
	$typograph->log_on();
	if($_REQUEST['useoptions'])
	{
		$use_class_checked = $_REQUEST['use_method']==2 ? "checked" : "";
		$use_style_checked = $_REQUEST['use_method']==1 ? "checked" : "";
		$layout = 0;
		if($_REQUEST['use_method']==2) $layout = EMT_Lib::LAYOUT_CLASS ;
		if($_REQUEST['use_method']==1) $layout = EMT_Lib::LAYOUT_STYLE ;
		//if($_REQUEST['use_style']) $layout |= EMT_Lib::LAYOUT_STYLE ;
		$typograph->set_tag_layout($layout);
		$opt_r = array();
		foreach($options as $tretname => $tlist) $opt_r[$tretname] = array_keys($tlist);
		$typograph->set_enable_map($opt_r);
	}
	$typograph->set_text($_REQUEST['inputdata']);
	$result = $typograph->apply();
	
	
	function draw_debug_item($tret_title, $tret_name, $rule_title, $rule_name, $result, $something_changed, $result_raw )
	{
		$h = "<div ".(!$something_changed? " style='display:none' class='debughidden'" : "").">";
		$h .= " <h3>$tret_title ".($tret_name?"<sub><small>$tret_name</small></sub>":"")."</h3>";
		$h .= "  <div  style='margin-left:30px;'>";
		if($rule_name) $h .= "    <h4>$rule_title".($rule_name?"<sub><small>$rule_name</small></sub>":"")."</h4>";
		$code = htmlspecialchars($result);
		$code_raw = htmlspecialchars($result_raw);
		$h .= "    Результат: <div style='margin-left:30px;'>$result</div><br />";
		$h .= "    HTML-код: <pre style='margin-left:30px;'>$code</pre>";
		$h .= "    <div class='rawhtmlcode' style='display:none'>Не обработанный HTML-код: <pre style='margin-left:30px;'>$code_raw</pre></div>";
		$h .= "  </div>";
		$h .= "</div>";
		return $h;
	}
	
	$prev = "";
	foreach($typograph->debug_info as $debug)
	{
		if($debug['tret'])
		{
			$tr = $typograph->get_tret($debug['class']);
			$tt = "Трэт: ".$tr->title;
			$tn = $debug['class'];
			$rt = $tr->rules[$debug['place']]['description'];
			$rn = $debug['place'];
		} else {
			$rt = "";
			$rn = "";
			$tn = "";
			switch($debug['place'])
			{
				case "init":  $tt = "До обработки типографом"; break;
				case "safe_sequences":  $tt = "Включение безопасных последовательностей"; break;
				case "unsafe_sequences":  $tt = "Выключение безопасных последовательностей"; break;
				case "safe_blocks":  $tt = "Включение безопасных блоков"; break;
				case "unsafe_blocks":  $tt = "Возврат безопасных блоков"; break;
				case "safe_tag_chars":  $tt = "Сохранение содержимого тэгов"; break;
				case "unsafe_tag_chars":  $tt = "Восстановление содержимого тэгов"; break;
				case "clear_special_chars":  $tt = "Замена всех специальных символов"; break;				
				default: $tt = $debug['place'];
			}
		}
		$debuglist .= draw_debug_item($tt, $tn, $rt, $rn , $debug['text'], $prev != $debug['text'], $debug['text_raw']);
		$prev = $debug['text'];
	}
	
	
	$logs = "";
	foreach($typograph->logs as $log)
	{
		$prefix =  "";
		if($log['class'])
		{
			$tr = $typograph->get_tret($log['class']);
			$prefix = "Трэт ".$tr->title . " (".$log['class'].") ";
		}
		$data = "";
		if($log['data'])
		{
			$data = (is_string($log['data']) ? $log['data'] : print_r($log['data'], true));
		}
		$logs .= $prefix . $log['info'] . ($data ?  ": ". $data : "" ).   "\n";
	}
	
	
	$html = $result;
	$code = htmlspecialchars($html);
}

$typograph_style = $typograph->get_style();


$typooptions = "";
$tret_list = $typograph->get_trets_list();
foreach($tret_list as $tret)
{
	$tret_obj = $typograph->get_tret($tret);
	
	$rhtml = "";
	foreach($tret_obj->rules as $rulename => $rule)
	{
		$checked = "";
		if($options)
		{
			if($options[$tret][$rulename]) $checked="checked";
		} else {
			if(!$rule['disabled']) $checked="checked";
		}
		$rhtml .= "<input type='checkbox' name='options[".$tret."][".$rulename."]' value='1' $checked class='opt$tret'> ".$rule['description']."<sub><small>".$rulename."</small></sub><br>\n";
	}
	$typooptions .= "<input type='checkbox' data='$tret' checked class='opttrets'>".
			"<b>{$tret_obj->title}<sub><small>".$tret."</small></sub></b><br />\n<div style='margin-left: 20px;'>$rhtml</div>\n";
}


$phpself = $_SERVER['PHP_SELF'];
echo <<<HTML
<html>
	<head>
		<title>Типограф Евгения Муравьёва версия 3.0</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
		<script>
$(document).ready(function(){
     $('#showlog').click(function(){
     	$('#log').toggle();
     });
     $('#showfulldebug').click(function(){
     	$('.debughidden').toggle();
     });
     $('#showrawdebug').click(function(){
     	$('.rawhtmlcode').toggle();
     });
     
     $('#showoptions').click(function(){
        $('#typooptions').toggle();
        if($('#typooptions').is(":visible")) {
           $('#useoptions').val('1');
        } else {
           $('#useoptions').val('0');
        }
     });
     $('.opttrets').click(function(){
     	if($(this).is(':checked'))
     	{
     		$(".opt"+$(this).attr('data')).prop('checked', true);
     	} else {
     		$(".opt"+$(this).attr('data')).prop('checked', false);
     	}
     });
     $('#applytypograph1').click(function(){
     
     });
});
		</script>
<style>
$typograph_style
</style>
	</head>
	<body>
	<h1>Типограф Евгения Муравьёва. Отладчик.</h1>
	<form action="$phpself" method="post" >
		<table cellpadding="5" cellspacing="5" border="0" >
		   <tr>
		    <td style="width: 500px" valign="top">
			 <textarea name="inputdata" style="width: 500px; height: 200px;" id="inputdata" placeholder="Введите текст для типографирования" >$inputdata</textarea>
			 <br />
			 <input type="submit" id="applytypograph" value="Сотворить" />
			</td>
			<td valign="top">
			 <a href="#typooptions" id="showoptions">Настроить типограф</a><br />
			 <div id="typooptions" $optdisplay>
			 <input type="hidden" name="useoptions" id="useoptions" value="$useoptions" />
			 <h3>Вывод</h3>
			 <input type='radio' name='use_method' value='1' $use_style_checked> <b>Использовать style</b>&nbsp;&nbsp;&nbsp;<input type='radio' name='use_method' value='2' $use_class_checked> <b>Использовать классы</b>
			 <br>
			 <h3>Правила</h3>
			 $typooptions
			 </div>
			</td>
		   </tr>
		</table>
	</form>
	

HTML;

if($ok)
echo <<<HTML
	Результат: <br />
	<div id="result">$html</div>
	<br/>
	<br/>
	HTML code: <br />
	<span style="font-family: monospace; font-weight: bold" id="htmlcode">$code</span>
	<br>
	<br>
	<hr>
	<br>
	<h2 id="debug">Отладка</h2> <a href="#debug" id="showfulldebug" >показать всё</a> <a href="#debug" id="showrawdebug" >показать необработанные рездуьтаты</a>
	<div  style='margin-left:30px;'>
	$debuglist
	</div>
	<br>
	<br>
	<hr>
	<br>
	<h2>Логи</h2> <a href="#log" id="showlog">Посмотреть</a>
	<pre id="log" style="display: none">$logs</pre>
	
HTML;

echo <<<HTML
	</body>
</html>
HTML;

?>