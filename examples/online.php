<?php


require_once("EMT.php");

header('Content-Type: text/html; charset=utf-8');

if($_REQUEST['inputdata'])
{
	$inputdata = htmlspecialchars($_REQUEST['inputdata']);
	$typograph = new EMTypograph();
	$typograph->set_text($_REQUEST['inputdata']);
	$result = $typograph->apply();
	
	$html = $result;
	$code = htmlspecialchars($html);
}


$phpself = $_SERVER['PHP_SELF'];
echo <<<HTML
<html>
	<head>
		<title>Типограф Евгения Муравьёва версия 3.0</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
		<script>
$(document).ready(function(){
     $('#applytypograph1').click(function(){
     	
     
     });
});
		</script>
	</head>
	<body>
	<form action="$phpself" method="post">
		<textarea name="inputdata" style="width: 500px; height: 200px;" id="inputdata" placeholder="Введите текст для типографирования" >$inputdata</textarea>
		<br />
		<input type="submit" id="applytypograph" value="Сотворить" />
	</form>
	
	Result: <br />
	<div id="result">$html</div>
	<br/>
	<br/>
	HTML code: <br />
	<pre id="htmlcode">$code</pre>
	
	
	</body>
</html>
HTML;


?>