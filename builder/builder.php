<?php

	require_once("../lib/fs.php");
	
	header("Content-type: text/html; charset=utf-8");
	
	$resname = "../EMT.php";
	$action = $_REQUEST['action'];
	if($action == "")
	{
		$phpself = $_SERVER['PHP_SELF'];
		echo <<<HTML
	<a href="$phpself?action=installer">Сгенерировать файл типографа для PHP</a><br>
	<a href="$phpself?action=installerpy">Сгенерировать файл типографа для Python</a><br>
HTML;
		exit;
	} 
	if($action == "installer" || $action == "installerpy") {
		
		function removebetween($str, $start, $end) {
			$z = explode($start, $str);
			$b = $z[0];
			if(count($z)==1) return $str;
			unset($z[0]);
			$d = explode($end, implode($start, $z));		
			unset($d[0]);
			$str =  $b.implode($end, $d);
			return removebetween($str, $start, $end);
		}
		
		function removelinewith($str, $inc) {
			$z = explode("\n", $str);
			$a = array();
			foreach($z as $d) {
				if(strpos($d,$inc)!==false) continue;
				$a[] = $d;
			}
			return implode("\n", $a);
		}
		
		function phpfile_read($file, &$size, $all = false)
		{
			$size = filesize($file);
			$fp2 = fopen($file, "r");
			fseek($fp2, 5);
			$s = fread($fp2, $size - 5 - 2);		
			fclose($fp2);
			
			$lines = explode("\n", $s);
			$lines2 = array();
			foreach($lines as $line)
			{
				if(preg_match("/^\s*require(_once)?\s*\([^\)]*\)\s*;\s*$/i", $line))
					continue;
				$lines2[] = $line;
			}
			$s = implode("\n", $lines2);
			
			if(!$all) {
				$s = removebetween($s, "/**PYTHON", "PYTHON**/");
				$s = removelinewith($s, "replacement_py");
			}
			
			$size = strlen($s);
			return $s;
		}
		
		
		$listx = FS::list_only_files("../src-php/","/^EMT.Tret\..*\.php$/");
		
		if($action == "installerpy") {
			$resname = "../EMT.forpy.php";
		}
		
		$fp = fopen($resname,"w");
		$dfile = "";
		
		$list = array();
		
		$list[] = "EMT.Lib.php";
		$list[] = "EMT.Tret.php";
		foreach($listx as $e) $list[] = $e;
		$list[] = "EMT.php";
		
		fprintf($fp, "<?php");
		fprintf($fp, "\n\n");
		fprintf($fp, 
<<<CODE
/**
* Evgeny Muravjev Typograph, http://mdash.ru
* Version: 3.4 Gold Master
* Release Date: May 4, 2014
* Authors: Evgeny Muravjev & Alexander Drutsa  
*/

CODE
);
	
		foreach($list as $file )
		{
			$s = phpfile_read("../src-php/$file", $size, $action == "installerpy");
			fputs($fp, $s, $size );
		}
		
		fprintf($fp, "?>");
		fclose($fp);
		
		echo "Сгенерирован скрипт типографа для PHP<br />";
	}
	if($action == "installerpy") {
		require_once("../EMT.forpy.php");
		$z = file_get_contents("../src-py/EMT.py");
		
		require_once("builder.py.php");
		
		$tretsx = array();
		$typograf = new EMTypograph();
		foreach($typograf->trets as $tret)
		{		
			$tretx = $typograf->get_tret($tret);
			$tretsx[] = work_for_py($tretx);
		}
		
		$zz = str_replace("#####EMT_TRETS#####",  implode("", $tretsx), $z);
		file_put_contents("../EMT.py",  $zz);
		@unlink("../EMT.forpy.php");
		echo "Сгенерирован скрипт типографа для Python<br />";
	}
	
	if($action == "testpy") {
		require_once("builder.py.php");
		$noecho = 1;
		require_once("../test.php");
		$list = FS::list_only_files("../tests/", '/^test\.[0-9a-z\.\-_]+\.php$/i');
		
		if(count($list)>0)
		{
			$tester = new EMTTester();
			$tester->double_test = isset($_GET['double_test']);
			$tester->set_typoclass("EMTypograph");
			foreach($list as $file) include("../tests/$file");
			//$ok = $tester->testit();
			//$result = $tester->results;
		} else {
			echo "В каталоге tests тесты не обнаружены";
			exit;
		}
		$r = file_get_contents("../EMT.test.py");
		//$r = str_replace ("TESTLIST", , $r);
		
		file_put_contents("../tests.json", json_encode($tester->list));
		//file_put_contents("../test.py", $r);
		echo "Сгенерирован скрипт теста типографа для Python<br />";
	}

?>