<?php

class BIOSFS
{
	function recurse_copy_ext( $src, $dst, $exclude = false)
	{
		$dir = @opendir($src); 
	    if(!$dir) return false;
	    self::force_drectories($dst);
	    
	    while(false !== ( $file = readdir($dir)) ) 
	    { 
	        if (( $file != '.' ) && ( $file != '..' )) 
	        {
	        	if(is_array($exclude))
	        		if(in_array($src."/".$file, $exclude)) continue;
	        		
	            if ( is_dir($src . '/' . $file) ) 
	            {
	                self::recurse_copy_ext($src . '/' . $file, $dst . '/' . $file, $exclude); 
	            } 
	            else 
	            { 
	                if(!@copy($src . '/' . $file,$dst . '/' . $file))
	                {
	                	closedir($dir);
	                	return false;
	                }
	            } 
	        } 
	    } 
	    closedir($dir); 
	    return true;
	}
	
	function recurse_copy($src,$dst) 
	{ 
	    self::recurse_copy_ext( $src, $dst );
	}
	
	function copy($src,$dst) 
	{ 
		return @copy($src ,$dst);		
	}
	function recurse_unlink_ext($src, $exclude = false)
	{
		if(!is_dir($src))
		{
			@unlink($src);
			return true;
		}
		$dir = opendir($src); 
	    while(false !== ( $file = readdir($dir)) ) 
	    { 
	    	
	        if(is_array($exclude))
	        	if(in_array($src."/".$file, $exclude)) continue;
	        		
	        if (( $file != '.' ) && ( $file != '..' )) 
	        { 
	            if ( is_dir($src . '/' . $file) ) 
	            { 
	                self::recurse_unlink_ext($src . '/' . $file, $exclude); 
	                @rmdir($src . '/' . $file);
	            } 
	            else 
	            { 
	                @unlink($src . '/' . $file);
	            } 
	        } 
	    }
	    closedir($dir); 
	    @rmdir($src);
	    return true;
	}
	function unlink($src)
	{
		@unlink($src);
		return true;
	}
	function recurse_unlink($src)
	{
		self::recurse_unlink_ext($src);
	}
	function force_directories($dir, $mode = 0777)
	{
		return @mkdir($dir, $mode, true);
	}
	function force_required_directories($file, $mode = 0777)
	{
		$s = explode("/",$file);
		if(count($s) <= 1) return;
		$k = count($s);
		unset($s[$k-1]);
		$dir = implode("/", $s);
		return self::force_directories($dir,$mode);
	}
	function xcopy($src,$dst) 
	{ 
		self::force_required_directories( $dst );
		return @copy($src ,$dst);		
	}
	function extract_file_name($file)
	{
		$t = explode("/", $file) ;
		return $t[count($t)-1];
	}
	function extract_file_path($file)
	{
		$t = explode("/", $file) ;
		unset($t[count($t)-1]);
		return implode("/",$t);
	}
	function remove_leading_slash($name)
	{
		while($name[0] == "/") $name = substr($name,1);
		return $name;		
	}
	function remove_trailing_slash($name)
	{
		while($name[strlen($name)-1] == "/") $name = substr($name,0,-1);
		return $name;
	}
	function set_path_slash($path)
	{
		return self::remove_trailing_slash($path)."/";
	}
	function remove_ext($file)
	{
		$p = explode(".", $file);
		unset($p[count($p)-1]);
		return implode(".",$p);
	}
	function list_only_files($dir, $template = "")
	{
		$list = array();
		$ddir  = $dir;
		if($ddir[mb_strlen($ddir)-1]!="/") $ddir .= "/";		
		if ($dh = @opendir($dir ))
		{
			while ($next = readdir($dh))
			{
				if($next[0]==".") continue;
				if (!is_dir($ddir . $next))
				{
					if($template == "")
					{
						$list[] = $next;
						continue;
					}
					if(preg_match($template, $next))
					{
						$list[] = $next;
						continue;
					}
				}
			}
			closedir($dh);
		}
		return $list;
	}
	function list_only_dirs($dir, $template = "")
	{
		$list = array();		
		$ddir  = $dir;
		if($ddir[mb_strlen($ddir)-1]!="/") $ddir .= "/";
		if ($dh = @opendir($dir ))
		{
			while ($next = readdir($dh))
			{
				if($next[0]==".") continue;
				
				if (is_dir($ddir . $next))
				{
					if($template == "")
					{
						$list[] = $next;
						continue;
					}
					if(preg_match($template, $next))
					{
						$list[] = $next;
						continue;
					}
				}
			}
			closedir($dh);
		}
		return $list;
	}
	function list_only_files_rec_ext($dir, $basedir, $template = "")
	{
		$list = array();
		$ddir  = $dir;
		if($ddir[mb_strlen($ddir)-1]!="/") $ddir .= "/";		
		if ($dh = @opendir($dir ))
		{
			while ($next = readdir($dh))
			{
				if($next[0]==".") continue;
				if (!is_dir($ddir . $next))
				{
					$ok = 0;
					if($template == "") $ok = 1; else if(preg_match($template, $next)) $ok = 1 ;
					if($ok) $list[] = $basedir . $next;
				} else {
					// may be here
					$l = self::list_only_files_rec_ext($ddir . $next, $basedir . $next . '/' , $template); 
					$list = array_merge($list, $l);
				}
			}
			closedir($dh);
		}
		return $list;
	}
	function list_only_files_rec($dir, $template = "")
	{
		return self::list_only_files_rec_ext($dir, "", $template);
	}
	function list_dir($dir, $template = "")
	{
		$list = array();
		$ddir  = $dir;
		if($ddir[mb_strlen($ddir)-1]!="/") $ddir .= "/";		
		if ($dh = @opendir($dir))
		{
			while ($next = readdir($dh))
			{
				if($next=="." || $next=="..") continue;
				if (!is_dir($ddir . $next))
				{
					$ok = 0;
					if($template == "") $ok = 1; else if(preg_match($template, $next)) $ok = 1 ;
					if($ok) $list[] = $ddir . $next;
				} else {
					$l = self::list_dir($ddir . $next."/", $template); 
					$list = array_merge($list, $l);
				}
			}
			closedir($dh);
		}
		return $list;
	}
	function fix_win_dirs(&$file)
	{
		$file = str_replace("\\","/",$file);
	}
	function get_contents($file, $allow_url= false)
	{
		if(!$allow_url)
		{
			if(preg_match("/:\/\//i",$file)) return false;
		}
		return @file_get_contents($file);
	}
	function put_contents($file, $data)
	{
		return @file_put_contents($file,$data);
	}
	
	function get_temp_file($pref = "lbi")
	{
		$z  = tempnam("/tmp", $pref);
		self::fix_win_dirs($z);
		return $z;
	}
	function getext2($name)
	{
		$t = explode(".",$name);
		$u = "";
		foreach($t as $v) $u = $v;
		return $u;	
	}
}


?>