<?php

$tester->set_group('T', "Блоки без типографирования и защищённые тэги");

	$no_p = array('Text.paragraphs'=>'off', 'OptAlign.all'=>'off');
	
	$tester->add_test("<notg>1 января 2009</notg>", "<span class=\"_notg_start\"></span>1 января 2009<span class=\"_notg_end\"></span>", null, "Отмена типографирования во всём тексте", $no_p);
	$tester->add_test("Я пишу текст, <notg>и не хочу,</notg> чтобы его часть типографировалась.", "Я&nbsp;пишу текст, <span class=\"_notg_start\"></span>и не хочу,<span class=\"_notg_end\"></span> чтобы его часть типографировалась.", null, "Отмена типографирования на части текста", $no_p);
	
	$tester->add_test(<<<HTML
<pre>Заяц сидел на опушке.</pre>
HTML
, <<<HTML
<pre>Заяц сидел на опушке.</pre>
HTML
, null, "Отмена типографирования тэга pre", array('Text.paragraphs'=>'off', 'OptAlign.all'=>'off'), "pre");

	$tester->add_test(<<<HTML
<pre><code>Заяц сидел на опушке.</code></pre>
HTML
, <<<HTML
<pre><code>Заяц сидел на опушке.</code></pre>
HTML
, null, "Отмена типографирования тэга pre с вложенным тэгом", array('Text.paragraphs'=>'off', 'OptAlign.all'=>'off'), "code");


	$tester->add_test(<<<HTML
<code><pre>Заяц сидел на опушке.</pre></code>
HTML
, <<<HTML
<code><pre>Заяц сидел на опушке.</pre></code>
HTML
, null, "Отмена типографирования тэга pre в внешним тэгом", array('Text.paragraphs'=>'off', 'OptAlign.all'=>'off'), "code");

	$tester->add_test(<<<HTML
http://mdash.ru/the-best-typograph.html
Что-то: http://mdash.ru/A0SAFESEQUENCENUM1ID 
HTML
, <<<HTML
<a href="http://mdash.ru/the-best-typograph.html">mdash.ru/the-best-typograph.html</a><br />
Что-то: <a href="http://mdash.ru/A0SAFESEQUENCENUM1ID">mdash.ru/A0SAFESEQUENCENUM1ID</a>
HTML
, null, "Обработка ссылок с имеющейся маркой", array('Text.paragraphs'=>'off', 'OptAlign.all'=>'off'), "code");

?>