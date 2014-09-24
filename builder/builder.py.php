<?php

	function work_for_py($tret){
		$cls = get_class($tret);	
		
		$rules = $tret->rules;
		foreach($rules as $k => $b) {
			//echo $k."<br>";
			if(isset($rules[$k]['function'])) {
				echo "Функция в правиле: ".$rules[$k]['function']."<br >\n";
				continue;
			}
			if($rules[$k]['replacement_python']) {
				$rules[$k]['replacement'] = $rules[$k]['replacement_python'];
				continue;
			}
			if(is_string($rules[$k]['pattern'])) {
				if(!ispregeval($rules[$k]['pattern']))	 continue;
				$rules[$k]['replacement'] = processpyrepl($rules[$k]['replacement']);
				continue;
			}
			$eval = false;
			foreach($rules[$k]['pattern'] as $v) {
				if(ispregeval($v)) {
					$eval = true;
					break;
				}
			}
			if(!$eval) continue;
			if(is_string($rules[$k]['replacement'])) {
				$rules[$k]['replacement'] = processpyrepl($rules[$k]['replacement']);
				continue;
			}
			foreach($rules[$k]['pattern'] as $i => $v) {				
				if(ispregeval($v)) $rules[$k]['replacement'][$i] = processpyrepl($rules[$k]['replacement'][$i]);
			}
		}
		$rule_order = prepare_var(array_keys($rules));
		$rules = prepare_var($rules);
		
		
		$zf = file_get_contents("../src-php/".str_replace("_",".",$cls).".php");
		echo "Обработка класса $cls<br />\n";
		$b = array();
		if(preg_match_all('/(public|protected|private) +\$([a-z0-9_]+)/i', $zf, $m,PREG_SET_ORDER)) {
			foreach($m as $mx) {
				if(!in_array($mx[2], array('title', 'rules'))) {
					$b[] = $mx[2];
				}
			}
		}
		$extvars = "";
		foreach ($b as $var) 
		{
			echo " - Доп переменная $var<br>\n";
			$vc = prepare_var($tret->$var);
			$extvars .=<<<CODE
        self.$var = $vc;

CODE;
		}
	
		$extcode = getbetween($zf, "/**PYTHON","PYTHON**/");
		
		$res = <<<PYTHON

#######################################################
# $cls
#######################################################
class $cls(EMT_Tret):\r\n
    def __init__(self):
        EMT_Tret.__init__(self)
        self.title = "{$tret->title}"\r\n
        self.rules = {$rules}
        self.rule_order = {$rule_order}
$extvars
$extcode
PYTHON;
		$res = str_replace("\r\n","\n",$res);
		$res = str_replace("\n","\r\n",$res);
		return $res;
	}
	
	
	
	
	function getbetween($str, $start, $end) {
		$z = explode($start, $str);
		if(count($z)==1) return "";
		unset($z[0]);
		$d = explode($end, implode($start, $z));
		$str = $d[0] ;
		unset($d[0]);		
		$str .= getbetween(implode($end, $d), $start, $end);
		return $str;
	}
	
	
	function prepare_var($z) {
		return jsonToReadable(python_encode($z));
		if(is_string($z)) return "\"$z\"";
		if(is_array($z)){
			$z = arrayreplace(str_replace("array (","array(",var_export($z, true)));
			$z = str_replace(": true",": True",$z);
			$z = str_replace(": false",": False",$z);
		}
		return $z;
		
	}
	
	function jsonToReadable($json){
    $tc = 2;        //tab count
    $r = '';        //result
    $q = false;     //quotes
    $t = "    ";      //tab
    $nl = "\n";     //new line

    for($i=0;$i<strlen($json);$i++){
        $c = $json[$i];
        if($c=='"' && $json[$i-1]!='\\') $q = !$q;
        if($q){
            $r .= $c;
            continue;
        }
        switch($c){
            case '{':
            case '[':
                $r .= $c . $nl . str_repeat($t, ++$tc);
                break;
            case '}':
            case ']':
                $r .= $nl . str_repeat($t, --$tc) . $c;
                break;
            case ',':
                $r .= $c;
                if($json[$i+1]!='{' && $json[$i+1]!='[') $r .= $nl . str_repeat($t, $tc);
                break;
            case ':':
                $r .= $c . ' ';
                break;
            default:
                $r .= $c;
        }
    }
    return $r;
}


function ispregeval($v) {
	$chr = substr($v,0,1);
	$preg_arr = explode($chr, $v);		
	if(strpos($preg_arr[count($preg_arr)-1], "e")!==false) return true;
	return false;
}


function processpyrepl($repl) {
	$replx = $repl;
	echo "" . $replx."<br />";
	$repl = opreplace($repl, '.', '+');
	$repl = arrayreplace($repl);
	$repl = condopreplace($repl);
	$repl = str_replace('self::', '', $repl);
	$repl = str_replace('$this->', 'self.', $repl);
	$repl = str_replace('EMT_Lib::', 'EMT_Lib.', $repl);
	$repl = str_replace('substr(', 'EMT_Lib.substr(', $repl);
	$repl = str_replace('str_replace(', 'EMT_Lib.str_replace(', $repl);
	$repl = str_replace('intval(', 'int(', $repl);
	$repl = str_replace_safequote('||', ' or ', $repl);
	$repl = str_replace_safequote('&&', ' and ', $repl);
	$repl = str_replace_safequote('===', '==', $repl);
	$repl = preg_replace('/trim\(([^\)]+)\)/', '\1.strip()', $repl);
	$repl = preg_replace('/isset\(\$m\[([0-9])+\]/', '(m.group(\1)', $repl);
	$repl = preg_replace('/\$m\[([0-9]+)\]/', 'm.group(\1)', $repl);
	$repl = preg_replace('/substr_count\(([^,]+),/', '\1.count(', $repl);
	$repl = preg_replace('/str_repeat\(([^,]+),/', '\1 * (', $repl);
	$repl = preg_replace('/in_array\(([^,]+),/', '\1 in (', $repl);
	$repl = str_replace_safequote2('"', 'u"', $repl);
	
	
	
	
	echo "<b>$repl</b><br /><br />\n";
	return $repl;
}


function opreplace($str, $op, $new) {
	$off = 0;
	while(($r = mb_strpos($str, $op, $off)) !== false) {
		$z = mb_strlen($op);
		if(!inbrackets($str, $r)) {			
			$str = mb_substr($str, 0, $r) . $new . mb_substr($str, $r+mb_strlen($op));
			$z = mb_strlen($new);
		}
		$off = $r+$z;
	}
	return $str;
}


function inbracketsx($str, $pos , $off = 0) {
	$p = mb_strpos($str, "\"", $off);
	$r = mb_strpos($str, "'", $off);
	if($p === false && $r === false) return false;
	if($p === false) $p = 10000000;
	if($r === false) $r = 10000000;
	$z = min($p,$r);
	if($z >= $pos) return false;
	$sym = mb_substr($str, $z, 1);
	$k = mb_strpos($str, $sym, $z+1);
	if($k === false) return true;
	if($k >= $pos) return true;
	
	return inbracketsx($str, $pos, $k+1);
}

function inbrackets($str, $pos ) {
	$str = str_replace('\\"', "||", $str);
	$str = str_replace("\\'", "||", $str);
	
	return inbracketsx($str, $pos);
}

function arrayreplace($str) {
	$off = 0;
	while(($r = mb_strpos($str, "array(", $off)) !== false) {
		if(mb_substr($str, $r-1,1) == "_") {
			$off = $r + 1;
			continue;
		}
		$l = getbetweensymb($str, $r+ 5, "(" , ")");
		if($l === false) {
			echo "<b>ERROR REPLACING ARRAY()</b><br />";
			exit(1);
			return false;
		}
		//eval('$xx = '.mb_substr($str, $r, $l['end'] - $r).' ;');
		$new = "{".str_replace("=>",":",mb_substr($str, $r+mb_strlen("array("), $l['end'] - $r-1-mb_strlen("array(")))."}";
	
		//$new = json_encode($xx);
		$str = mb_substr($str, 0, $r) . $new . mb_substr($str, $l['end']);
		//echo "A";
	}
	return $str;
}

function getbetweensymb($str, $start, $open, $close) {
	$off = $start+mb_strlen($open);
	$dif = 1;
	while($dif>0) {
		$poff = $off;
		$roff = $off;		
		do {			
			$p = mb_strpos($str, $open, $poff);
			if($p === false) break;
			$poff = $p + mb_strlen($open);
			
		} while(inbrackets($str,$p));
		do {
			$r = mb_strpos($str, $close, $roff);
			if($r !== false) break;
			$roff = $r + mb_strlen($close);
		} while(inbrackets($str,$r));
		if($r === false ) {
			return false;
		}
		if ($p === false) $p = 10000000;
		$z = min($p, $r);
		if (mb_substr($str, $z, mb_strlen($open) ) == $open) {
			$dif++;
			$off = $z + mb_strlen($open);
		}
		if (mb_substr($str, $z, mb_strlen($close) ) == $close) {
			$dif--;
			$off = $z + mb_strlen($close);
		}
	}
	return array('start' => $start, 'end' => $off);
}


function utf8_strrev($str){
 preg_match_all('/./us', $str, $ar);
 return implode(array_reverse($ar[0]));
}
function fundleftopen($str, $start) {
	$strx = utf8_strrev($str);
	$x = getbetweensymb($strx, mb_strlen($str) - $start-1, ")", "(");
	if($x === false) return false;
	//var_dump($strx);
	//var_dump(mb_strlen($strx));
	//var_dump($x);
	//exit;
	return mb_strlen($str) -1- ($x['end'] -1) ;
}




function condopreplace($str) {
	$off = 0;
	while(($r = mb_strpos($str, "?", $off)) !== false) {
		if(inbrackets($str, $r)) {
			$off = $r+1;
			continue;
		}
		$left = fundleftopen($str, $r);
		if($left === false) {
			echo "CANT'T FIND LEFT BRACKET FOR ?<br>";
			exit(1);
			return false;
		}
		
		//echo "FOR ? we have -> ". $left. "<br>";
		$z = getbetweensymb($str, $left, "(", ")");
		if($z === false) {
			echo "CANT'T FIND RIGHT BRACKET FOR ?<br>";
			exit(1);
			return false;
		}
		$right = $z['end'] ;
		$offx = $r;
		while(true) {
			$col = mb_strpos($str, ":", $offx);
			if($col === false)  {
				echo "CANT'T FIND COLON FOR ?<br>";
				exit(1);
				return false;
			}
			if($colon >= $right) {
				echo "CANT'T FIND COLON FOR ?<br>";
				exit(1);
				return false;
			}
			if(inbrackets($str,$col)) {
				$offx = $col+1;
				continue;
			}
			break;
		}
		
		
		$condition = mb_substr($str, $left+1, $r - ($left + 1));
		$iftrue = mb_substr($str, $r+1, $col - ($r + 1));
		$iffalse = mb_substr($str, $col+1, ($right-1) - ($col + 1));
		
		$str = mb_substr($str, 0, $left+1) . "(".$iftrue ." if " . $condition . " else ". $iffalse .")" . mb_substr($str, $right-1);
		//$off = $right;
		
		echo " AFTER: $str<br>";
		
	}
	return $str;
}


function str_replace_safequote($what, $with, $text) {
	$off = 0;
	while(($r = mb_strpos($text, $what, $off)) !== false) {
		if(inbrackets($text, $r)) {
			$off = $r+mb_strlen($what);
			continue;
		}
		$text = mb_substr($text, 0, $r) . $with . mb_substr($text, $r + mb_strlen($what));
		
	}
	
	return $text;
}

function str_replace_safequote2($what, $with, $text) {
	$off = 0;
	while(($r = mb_strpos($text, $what, $off)) !== false) {
		if(inbrackets($text, $r)) {
			$off = $r+mb_strlen($what);
			continue;
		}
		$text = mb_substr($text, 0, $r) . $with . mb_substr($text, $r + mb_strlen($what));
		$off = $r + mb_strlen($with);
		
	}
	
	return $text;
}











function utf162utf8($utf16)
    {
        if(!function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
            case ((0x7F & $bytes) == $bytes):
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                     . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                     . chr(0x80 | (($bytes >> 6) & 0x3F))
                     . chr(0x80 | ($bytes & 0x3F));
        }
        return '';
    }

    function utf82utf16($utf8)
    {
        if(!function_exists('mb_convert_encoding')) {
             return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch(strlen($utf8)) {
            case 1:
                return $utf8;

            case 2:
                return chr(0x07 & (ord($utf8{0}) >> 2))
                     . chr((0xC0 & (ord($utf8{0}) << 6))
                         | (0x3F & ord($utf8{1})));

            case 3:
                return chr((0xF0 & (ord($utf8{0}) << 4))
                         | (0x0F & (ord($utf8{1}) >> 2)))
                     . chr((0xC0 & (ord($utf8{1}) << 6))
                         | (0x7F & ord($utf8{2})));
        }

        return '';
    }

	
	
	function python_pair($key, $val)
    {
        $jsonval = python_encode($val);
        return python_encode(strval($key)) . ':' . $jsonval;
    }
	
	
	function python_encode($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'True' : 'False';

            case 'NULL':
                return 'null';

            case 'integer':
                return (int) $var;

            case 'double':
            case 'float':
                return (float) $var;

            case 'string':
                
            	$ascii = '';
                $strlen_var = strlen($var);
                return 'u"'.addslashes($var).'"';

            case 'array':
               
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                    $properties = array_map('python_pair', array_keys($var), array_values($var));
                    return '{' . implode(',', $properties) . '}';
                }
                $elements = array_map('python_encode', $var);
                return '[' . implode(',', $elements) . ']';

            case 'object':
                $vars = get_object_vars($var);
                $properties = array_map('python_pair',array_keys($vars),array_values($vars));
                return '{' . implode(',', $properties) . '}';

            default:
                return 'null';
        }
    }


?>