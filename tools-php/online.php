<?php

require_once("../lib/lib.php");
require_once("../EMT.php");

$typograph = new EMTypograph();
$option_list = $typograph->get_options_list();
$options = array();
foreach($option_list['all'] as $opt => $info)
{
	// опции были переданы
	if(isset($_REQUEST['text']))
	{
		if(!isset($_REQUEST['options'][$opt]))
		{
			if(isset($info['way']) && ($info['way'] == "reverse")) $options[$opt] = "on"; else $options[$opt] = "off";
			if(isset($info['selector'])) $options[$opt] = isset($info['reversed']) ? "on" : "off";
			continue;
		}
		
		$options[$opt] = $_REQUEST['options'][$opt];
	} else { // опции не были переданы, тогда всё по умолчанию
		
		// одно из правил ядра
		if(isset($info['way']))
		{
			if($info['way']!="reverse") $options[$opt] = ( $info['disabled'] ? "off" : "on" ) ; else $options[$opt] =  ( $info['disabled'] ? "on" : "off" ) ;  
		}
		if(isset($info['selector']))
		{
			if(isset($info['reversed'])) $options[$opt] = ( $info['disabled'] ? "on" : "off" ); else  $options[$opt] = ( $info['disabled'] ? "off" : "on" );
		}
	}
}

$text   = false;
$result = false;
$code   = false;
$error  = false;
if(isset($_REQUEST['text']))
{
	$text       = $_REQUEST['text'];
	$inputdata  = htmlspecialchars($_REQUEST['text']);
	
	$typograph->setup($options);
	$typograph->set_text($text);
	$result     = $typograph->apply();	
}

if(isset($_REQUEST['format']))
{
	json_encode($result);
} else {
	
	$options_html = "";	
	foreach($option_list['group'] as $key => $ginfo)
	{
		$group = $ginfo['name'];
		$option_html = "";
		if(is_array($ginfo['options']))
		{
			foreach($ginfo['options'] as $optname)
			{
				$option = $option_list['all'][$optname];
				
				if($optname == "Nobr.nowrap")
				{
					$option_html .= "<input type='radio' name='options[$optname]' value='off' ".((isset($options[$optname]) && ($options[$optname]=="off")) ? "checked":"")." > Использовать nobr<sub>$optname=off</sub> &nbsp;&nbsp;&nbsp;".
									"<input type='radio' name='options[$optname]' value='on' ".((isset($options[$optname]) && ($options[$optname]!="on")) ? "":"checked")." > Использовать nowrap<sub>$optname=on</sub><br>\n";
					continue;
				}
				if($optname == "OptAlign.layout")
				{
					$option_html .= "<input type='radio' name='options[$optname]' value='style' ".((isset($options[$optname]) && ($options[$optname]!="style")) ? "":"checked")." > Использовать стили<sub>$optname=style</sub> &nbsp;&nbsp;&nbsp;".
									"<input type='radio' name='options[$optname]' value='class' ".((isset($options[$optname]) && ($options[$optname]=="class")) ? "checked":"")." > Использовать классы<sub>$optname=class</sub><br>\n";
					continue;
				}
				if(isset($option['way']))
				{
					$value = ($option['way']=="direct"? "on" : "off");
					$option_html .= "<input type='checkbox' name='options[$optname]' value='$value' ".((isset($options[$optname]) && ($options[$optname]==$value)) ? "checked":"")." > $option[description]<sub>$optname=on|off</sub><br>\n";
					continue;
				}
				if(isset($option['selector']) && !(isset($option['hide']) && $option['hide']))
				{
					$value =  isset($option['reversed']) ? "off" : "on" ;
					$option_html .= "<input type='checkbox' name='options[$optname]' value='$value' ".((isset($options[$optname]) && ($options[$optname]==$value)) ? "checked":"")." > $option[description]<sub>$optname=on|off</sub><br>\n";
					continue;
				}
				
			}
		}
		$options_html .= "<h3>$ginfo[title]</h3>$option_html";
	}
	
	if($text !== false )
	{
		if(!$typograph->ok)
		{
			$error = "";
			foreach($typograph->errors as $err) $error .= "<li>".$err['info']."</li>";
		}
		$code = htmlspecialchars($result);
		$html = $result;
	}
}

$typograph_style = $typograph->get_style();


if($text !== false)
{
$htmlresult =  <<<HTML
	Результат: <br />
	<div id="result">$html</div>
	<br/>
	<br/>
	HTML code: <br />
	<span style="font-family: monospace; font-weight: bold" id="htmlcode">$code</span>
	<br>
	<br>
HTML;
}


$phpself = $_SERVER['PHP_SELF'];
echo <<<HTML
<html>
	<head>
		<title>Типограф Евгения Муравьёва версия 3.0</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
		<script>
$(document).ready(function(){
     $('#showoptions').click(function(){
        $('#typooptions').toggle();
        if($('#typooptions').is(":visible")) {
           $('#useoptions').val('1');
        } else {
           $('#useoptions').val('0');
        }
     });
     $('#applytypograph').click(function(){
     	$('#typoform').submit();
     });
});
		</script>
<style>
$typograph_style
</style>
	</head>
	<body>
	<h1>Типограф Евгения Муравьёва.</h1>
	<form action="$phpself" method="post" id="typoform" >
		<table cellpadding="5" cellspacing="5" border="0" >
		   <tr>
		    <td style="width: 700px" valign="top">
			 <textarea name="text" style="width: 700px; height: 300px;" id="text" placeholder="Введите текст для типографирования" >$inputdata</textarea>
			 <br />
			 <input type="button" id="applytypograph" value="Сотворить" />
			 <br>
			 <br>
$htmlresult
			</td>
			<td valign="top">
			 <a href="#typooptions" id="showoptions">Настроить типограф</a><br />
			 <div id="typooptions" $optdisplay>$options_html</div>
			</td>
		   </tr>
		</table>
	</form>
	

HTML;


echo <<<HTML
	</body>
</html>
HTML;

?>