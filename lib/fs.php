<?php

class FS
{
	
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
}


?>