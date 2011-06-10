<?php
include_once("inc/runtime.php");

function FillMultiDBArrays()
{
	global $config,$databases;
	
	$databases["multi"]=Array();
	$databases["multi_praefix"]=Array();
	if(!isset($databases["db_selected_index"])) $databases["db_selected_index"]=0;
	if(!isset($databases["db_actual"]) && isset($databases["Name"])) $databases["db_actual"]=$databases["Name"][$databases["db_selected_index"]];
	if(!isset($databases["multisetting"])) $databases["multisetting"]="";
	
	if($config["multi_dump"]==1) {
		if($databases["multisetting"]==""){
			$databases["multi"][0]=$databases["db_actual"];
			$databases["multi_praefix"][0]=(isset($databases["praefix"][0])) ? $databases["praefix"][0] : "";
		} else {	
			$databases["multi"]=explode(";",$databases["multisetting"]);
			$flipped = array_flip($databases["Name"]);
			for($i=0;$i<count($databases["multi"]);$i++) {
				$ind=$flipped[$databases["multi"][$i]]; 
				$databases["multi_praefix"][$i]=(isset($databases["praefix"][$ind])) ? $databases["praefix"][$ind] : "";
			}
		}
		
	} else  {
		$databases["multi"][0]=(isset($databases["db_actual"])) ? $databases["db_actual"] : "";
		$databases["multi_praefix"][0]=(isset($databases["praefix"])) ? $databases["praefix"][$databases["db_selected_index"]] : "";
	}
}

function DBDetailInfo($index) 
{
	global $databases,$config;
	
	$databases["Detailinfo"]["tables"]=$databases["Detailinfo"]["records"]=$databases["Detailinfo"]["size"]=0;
	MSD_mysql_connect(); 
	mysql_select_db($databases["Name"][$index]);
	$databases["Detailinfo"]["Name"]=$databases["Name"][$index];
	$res=@mysql_query("SHOW TABLE STATUS FROM `".$databases["Name"][$index]."`");
	if($res) $databases["Detailinfo"]["tables"]=mysql_num_rows($res);
	if($databases["Detailinfo"]["tables"]>0) {
		$s1=$s2=0;
		for ($i = 0; $i < $databases["Detailinfo"]["tables"]; $i++)
		{
	    	$row = mysql_fetch_array($res);
			$s1+=$row["Rows"];
			$s2+=$row["Data_length"]+$row["Index_length"];
		}
		$databases["Detailinfo"]["records"]=$s1;
		$databases["Detailinfo"]["size"]=$s2;
	} 
}

//Globale Funktionen
function br($m=1)
{
	//gibt m Zeilenumbr�che zur�ck
	return str_repeat("\n",$m);
}
function Stringformat($s,$count)
{
	return str_repeat("0",$count-strlen($s)).$s;
}
function getmicrotime()
{
   list($usec, $sec) = explode(" ",microtime()); 
   return ((float)$usec + (float)$sec); 
}
function MD_FreeDiskSpace()
{
	global $lang;
	$dfs=@diskfreespace("../");
	return ($dfs) ? byte_output($dfs) : $lang['notavail'];
}

function WriteDynamicText($txt,$object)
{
	return '<script language="JavaScript">WP("'.addslashes($txt).','.$object.'");</script>';
}

function byte_output($bytes, $precision = 2, $names = Array())
{
   if (!is_numeric($bytes) || $bytes < 0) {
       return false;
   }
   for ($level = 0; $bytes >= 1024; $level++) {
       $bytes /= 1024;
   }
   switch ($level)
   {
       case 0:
           $suffix = (isset($names[0])) ? $names[0] : 'Bytes';
           break;
       case 1:
           $suffix = (isset($names[1])) ? $names[1] : 'KB';
           break;
       case 2:
           $suffix = (isset($names[2])) ? $names[2] : 'MB';
           break;
       case 3:
           $suffix = (isset($names[3])) ? $names[3] : 'GB';
           break;
       case 4:
           $suffix = (isset($names[4])) ? $names[4] : 'TB';
           break;
       default:
           $suffix = (isset($names[$level])) ? $names[$level] : '';
           break;
   }
   return round($bytes, $precision) . ' ' . $suffix;
}

function ExtractDBname($s)
{
	if(strpos(strtolower($s),"_structure_file")>0){
		return substr($s,0,strpos(strtolower($s),"_structure_file"));
		break;
	}
	$sp=explode("_",$s);
	$anz=count($sp)-1;
	$r=0;
	if($anz>4) {
	$df=5; //Datumsfelder
	if($sp[$anz-1]=="part") $df+=2;
	if($sp[$anz-3]=="crondump" || $sp[$anz-1]=="crondump") $df+=2;
	$anz=$anz-$df; //Datum weg
	for($i=0;$i<=$anz;$i++){ $r+=strlen($sp[$i])+1;}
	return substr($s,0,$r-1);
	} else {
	//Fremdformat
		return substr($s,0,strpos($s,"."));
	}
}
function ExtractBUT($s)
{
	$i=strpos(strtolower($s),"part");
	if($i>0) $s=substr($s,0,$i-1);
	$i=strpos(strtolower($s),"crondump");
	if($i>0) $s=substr($s,0,$i-1);
	$i=strpos(strtolower($s),".sql");
	if($i>0) $s=substr($s,0,$i);
	$sp=explode("_",$s);
	$anz=count($sp)-1;
	if($anz>4) {
		return $sp[$anz-2].".".$sp[$anz-3].".".$sp[$anz-4]." ".$sp[$anz-1].":".$sp[$anz];
	} else {
	//Fremdformat
		return "";
	}
}

function WriteLog($aktion)
{
	global $config,$lang;
	$log=date("d.m.Y h:i:s").':  '.$aktion."\n";
	
	$logfile=($config["logcompression"]==1) ? $config["files"]["log"].".gz" : $config["files"]["log"];
	if(@filesize($logfile)+strlen($log)>$config["log_maxsize"]) @unlink($logfile);
	
	//Datei �ffnen und schreiben
	if($config["logcompression"]==1) {
		
		$fp = @gzopen($logfile, "a");
		if($fp) {
			@gzwrite ($fp,$log) .'<br>';
			@gzclose ($fp);
		} else echo '<p class="warnung">'.$lang['logfilenotwritable'].' ('.$logfile.')</p>';
	} else {
		$fp = @fopen($logfile, "ab");
		if($fp) {
			@fwrite ($fp,$log);
			@fclose ($fp);
		} else echo '<p class="warnung">'.$lang['logfilenotwritable'].' ('.$logfile.')</p>';
	}
}

function WriteTempOut($aktion)
{
	global $config;
	
	$fp = @fopen($config["paths"]["log"]."out.tmp", "w");
	
	if($fp) {
		@fwrite ($fp,$aktion);
		@fclose ($fp);
	} 
}
function WritePageParams($aktion)
{
	global $config,$restore,$dump,$nl;
	
	$pars="<?$nl";
	if($aktion=="dump") {
		$datei="dump.tmp";
		foreach($dump as $var => $val){ 
			if(is_array($val)) {
				foreach($val as $var2 => $val2){
					if ($config["magic_quotes_gpc"]==0) {
						$pars.='$dump["'."$var\"][".((is_int($var2)) ? $var2 : '"'.$var2.'"')."] = \"".addslashes($val2)."\";$nl";
					} else {
						$pars.='$dump["'."$var\"][".((is_int($var2)) ? $var2 : '"'.$var2.'"')."] = \"$val2\";$nl";
					}
				}
			} else 	{
				if ($config["magic_quotes_gpc"]==0) {
					$pars.='$dump["'."$var\"] = \"".addslashes($val)."\";$nl";
				} else {
					$pars.='$dump["'."$var\"] = \"$val\";$nl";
				}
			}
		}
	} elseif($aktion=="restore") {
		$datei="restore.tmp";
		foreach($restore as $var => $val){ 
			if(is_array($val)) {
				foreach($val as $var2 => $val2){
					if ($config["magic_quotes_gpc"]==0) {
						$pars.='$restore["'."$var\"][".((is_int($var2)) ? $var2 : '"'.$var2.'"')."] = \"".addslashes($val2)."\";$nl";
					} else {
						$pars.='$restore["'."$var\"][".((is_int($var2)) ? $var2 : '"'.$var2.'"')."] = \"$val2\";$nl";
					}
				}
			} else 	{
				if ($config["magic_quotes_gpc"]==0) {
					$pars.='$restore["'."$var\"] = \"".addslashes($val)."\";$nl";
				} else {
					$pars.='$restore["'."$var\"] = \"$val\";$nl";
				}
			}
		}
	}
	$pars.="$nl?>";
	$fp = @fopen($config["paths"]["log"].$datei, "w");
	
	if($fp) {
		@fwrite ($fp,$pars."\n");
		@fclose ($fp);
	} 
}
function ErrorLog($dest,$db,$sql,$error)
{
	global $config;
	if(strlen($sql)>100) $sql=substr($sql,0,100)." ... (snip)";
	//Error-Zeile generieren
	$errormsg=date("d.m.Y h:i:s").':  ';
	$errormsg.=($dest=="RESTORE") ? " Restore of db `$db`|:|" : " Dump of db `$db`|:|";
	$errormsg.="Error-Message: $error|:|";
	$errormsg.="SQL : ".$sql."\n";
	//Datei �ffnen und schreiben
	if($config["logcompression"]==1) {
		$fp = @gzopen($config["paths"]["log"]."error.log.gz", "ab");
		if($fp) {
			@gzwrite ($fp,($errormsg));
			@gzclose ($fp);
		}
	} else {
		$fp = @fopen($config["paths"]["log"]."error.log", "ab");
		if($fp) {
			@fwrite ($fp,($errormsg));
			@fclose ($fp);
		} 
	}
}
function DirectoryWarnings($path="")
{
	global $config;
	$warn="";		
	if($path=="" || $path=="config") if(!is_writable($config["paths"]["config"])) $warn.='Configpath is not writable!&nbsp;&nbsp;&nbsp;<a href="main.php?help=1&thema=path&dir=config"><img src="images/help16.gif" alt="" width="16" height="16" border="0"></a><br>';
	if($path=="" || $path=="backup") if(!is_writable($config["paths"]["backup"])) $warn.='Backuppath is not writable!&nbsp;&nbsp;&nbsp;<a href="main.php?help=1&thema=path&dir=backup"><img src="images/help16.gif" alt="" width="16" height="16" border="0"></a><br>';
	if($path=="" || $path=="structure") if(!is_writable($config["paths"]["structure"])) $warn.='Structurepath is not writable!&nbsp;&nbsp;&nbsp;<a href="main.php?help=1&thema=path&dir=structure"><img src="images/help16.gif" alt="" width="16" height="16" border="0"></a><br>';
	if($path=="" || $path=="log") if(!is_writable($config["paths"]["log"])) $warn.='Logpath is not writable!&nbsp;&nbsp;&nbsp;<a href="main.php?help=1&thema=path&dir=log"><img src="images/help16.gif" alt="" width="16" height="16" border="0"></a><br>';
	if($warn!="") $warn='<span class="warnung"><strong>ERROR !</strong><br>'.$warn.'</span>';
	return $warn;
}

function TestWorkDir()
{
	global $config;

	//echo "work_chmod: $config["paths"]["work"]_chmod<br>";
	SetFileRechte($config["paths"]["work"]);
	SetFileRechte($config["paths"]["backup"]);
	SetFileRechte($config["paths"]["structure"]);
	SetFileRechte($config["paths"]["log"]);
	SetFileRechte($config["paths"]["config"]);

	if(!file_exists($config["files"]["parameter"])) SetDefault(true);
	if(!file_exists($config["files"]["log"])) DeleteLog();
}


function SetFileRechte($file,$perm="0777") 
{ 
   global $lang; 
   if(substr($file,-1)!="/") $file.="/";
   if(strlen($perm)!=4 || intval($perm)==0 || intval($perm)>777) $perm="0777"; 
   if( (ini_get('safe_mode')==1) && (!(is_dir($file))) ) 
   { 
      echo $lang['critical_safemode']; 
      return 0; 
   } else { 
   	   clearstatcache(); 
      if (!@is_dir($file)) @mkdir($file, $perm); 
      clearstatcache(); 
      $a=@substr(decoct(fileperms($file)),-4); 
      $ret=1; 
      if($a!=$perm && (ini_get('safe_mode')!=1)) { 
      $ret=@chmod($file,$perm); 
      } 
      return $ret; 
   } 
} 


function SelectDB($index)
{
	global $databases;
	$databases["db_actual"] = $databases["Name"][$index];
	$databases["praefix"][$databases["db_selected_index"]] = $databases["praefix"][$index];
	$databases["db_selected_index"]=$index;
}

function EmptyDB($dbn)
{
	global $config;
	//$res=mysql_query("DROP DATABASE `$dbn`") or die(mysql_error()."");
	//$res=mysql_query("CREATE DATABASE `$dbn`") or die(mysql_error()."");
	$t_sql=Array();
	$tabellen = mysql_list_tables($dbn,$config["dbconnection"]); 
	$num_tables = mysql_num_rows($tabellen); 
	for($i=0;$i<$num_tables;$i++) {
		$t=mysql_tablename($tabellen,$i);
		$t_sql[]="DROP TABLE `$t`;";
	}
	for($i=0;$i<count($t_sql);$i++) {
		$res=mysql_query($t_sql[$i]) or die(mysql_error()."");
	}
	
}

function AutoDelete()
{
	global $del_files, $config, $lang,$out;

	//Files einlesen
	$dh = opendir($config["paths"]["backup"]);
	$dbbackups=Array();
	while (false !== ($filename = readdir($dh)))
	{


	    if ($filename != "." && $filename != ".." && !is_dir($config["paths"]["backup"].$filename)) {
			//statuszeile auslesen
			if(substr($filename,-2)=="gz"){
				$fp = gzopen ($config["paths"]["backup"].$filename, "r");
				$statusline=gzgets($fp,40960);
				gzclose ($fp);
			}else{
				$fp = fopen ($config["paths"]["backup"].$filename, "r");
				$statusline=fgets($fp,500);
				fclose ($fp);
			}
			$sline=ReadStatusline($statusline);
			if($sline[0]=="9999") {
				$tabellenanzahl="";
				$eintraege="";
			} else {
				$tabellenanzahl=$sline[0];
				$eintraege=$sline[1];
			}
			$part=($sline[2]=="") ? 0 : substr($sline[2],3);
			$databases["db_actual"]=$sline[3];
			$datum=substr($filename,strlen($databases["db_actual"])+1);
			$datum=substr($datum,0,16);
			if(isset($dbbackups[$databases["db_actual"]])) $dbbackups[$databases["db_actual"]]++; else $dbbackups[$databases["db_actual"]]=1;
			$files[] = "$datum|".$databases["db_actual"]."|$part|$filename";
		}
	}

	@rsort($files);
	// Mehr Dateien vorhanden, als es laut config.php sein d�rften? Dann weg damit :-)

	if($config["del_files_after_days"]>0) {
		$nowtime=strtotime("-".($config["del_files_after_days"]+1)." day");
		for($i=0; $i<sizeof($files); $i++)
		{
		     $delfile=explode("|",$files[$i]);
			 $ts=substr($delfile[0],0,4)."-".substr($delfile[0],5,2)."-".substr($delfile[0],8,2);
			 if(strtotime($ts)<$nowtime){
			 	$out.=DeleteFile($files[$i],"days");
				unset($files[$i]);
			 }
		}
	}
	@rsort($files);
	if($config["max_backup_files"] > 0)
	{
    	if (sizeof($files) > $config["max_backup_files"])
    	{
			if($config["max_backup_files_each"]==1) {
				//gilt es nur f�r jede Datenbank
				for($i=sizeof($files)-1; $i>=0; $i--)
				{
					$delfile=explode("|",$files[$i]);
					if($dbbackups[$delfile[1]] > $config["max_backup_files"])
					 {
					 	$out.=DeleteFile($files[$i],"max");
						unset($files[$i]);
						$dbbackups[$delfile[1]]--;
					 }
				}
			} else {
				//oder gilt es f�r alle Backups
				for($i=sizeof($files)-1; $i>=$config["max_backup_files"]; $i--)
				{
					$delfile=implode("|",$files);
					$out.=DeleteFile($files[$i],"max");
					unset($files[$i]);
				}
			}
    	}
	}



	return $out;
}

function DeleteFile($files,$function)
{
	global $config,$lang;
	$delfile=explode("|",$files);
	$ll=($function=="max") ? $lang["fm_autodel1"]:$lang["fm_autodel2"];
	$r="<font color=\"red\">".$ll."<br>";
	$r.= $delfile[3]."<br>";
	WriteLog("autodeleted ($function) '$delfile[3]'.");
   	unlink($config["paths"]["backup"].$delfile[3]);
	$r.= "</font>";
	return $r;
}

function ReadStatusline($line)
{
	//Format # Status:Tabellen:Datens�tze:Multipart:DBname
	if(substr($line,0,8)!="# Status" && substr($line,0,9)!="-- Status") {
		$s=Array("-1","-1","","","","","","");
	} else {
		$s=explode(":",$line);
		array_shift($s);
		if(count($s)<7) {
			for($i=count($s);$i<8;$i++) {$s[]="";}
		}
	}
	return $s;
}
function NextPart($s,$first=0) {
	
	$nf=explode("_",$s);
	$i=array_search("part",$nf)+1;
	$p=substr($nf[$i],0,strpos($nf[$i],"."));
	$ext=substr($nf[$i],strlen($p));
	if($first==1) {
		$nf[$i]="1".$ext;
	} else {
		$nf[$i]=++$p.$ext;
	}
	return implode("_",$nf);
}

function zeit_format($t)
{
	$tt_m=floor($t/60);
	$tt_s=$t-($tt_m*60);
	return $tt_m.' min. '.floor($tt_s).' sec';
}

function ErrorReport()
{
	global $config,$PHP_SELF,$PATH,$PATH_TRANSLATED,$SCRIPT_FILENAME,$SCRIPT_NAME,$email,$_SERVER;

	$error_kind=$error_aus="";
	if(isset($_POST["p"]) && $_POST["p"]==1) {
		//von Programmfehler geposted
		$error_kind=$_POST["error_kind"];
		$error_aus=$_POST["error_aus"];
	}
	if(isset($_POST["create_sql"]))  {
		//vom Parser
		$error_kind="Parser-Fehler";
		$error_aus=stripslashes(trim($_POST["create_sql"]))."\n\n\n".stripslashes(trim($_POST["insert_sql"]));
	}
	$subject = "Fehlerbericht vom ".date("d.m.Y H:s");


	$r= '<form action="main.php?action=senderror" method="post">';
	$r.='<div  class="small"><table><tr><td align="center" colspan="2"><h4>'.$subject.'</h4></td></tr>';
	$r.='<tr><td>Absender</td><td><input type="text" name="error_absender" size="40" value="'.$config["email_recipient"].'"></td></tr>';
	$r.='<tr><td>Name</td><td><input type="text" name="error_name" size="40"></td></tr>';
	$r.='<tr><td>Art des Fehlers</td><td><input type="text" name="error_kind" value="'.$error_kind.'" size="40"></td></tr>';
	$r.='<tr><td valign="top">Anzeige des Fehlers</td><td><textarea name="error_aus" cols="30" rows="6">'.$error_aus.'</textarea></td></tr>';
	$r.='<tr><td valign="top">zus�tzliche Bemerkungen</td><td><textarea name="error_zusatz" cols="30" rows="6"></textarea></td></tr>';

	$vi= '<tr><td>MySQLDump-Version</td><td><b>'.MSD_VERSION.'</b></td></tr>';
	$vi.= '<tr><td>MySQL-Version</td><td><b>'.MSD_MYSQL_VERSION.'</b></td></tr>';
	$vi.= '<tr><td>PHP-Version</td><td><b>'.phpversion().'</b></td></tr>';
	//$vi.= '<tr><td>PHP-Extensions</td><td width="600"><b>'.implode(" ",get_loaded_extensions()).'</b></td></tr>';
	$vi.= '<tr><td>safe_mode</td><td width="600"><b>'.(($config["safe_mode"]==1) ? "on" : "off").'</b></td></tr>';
	$vi.= '<tr><td>magic_quotes</td><td width="600"><b>'.(($config["magic_quotes_gpc"]==1) ? "on" : "off").'</b></td></tr>';
	$vi.= '<tr><td>Variable rootdir</td><td width="600"><b>'.addslashes($config["paths"]["root"]).'</b></td></tr>';
	$vi.= '<tr><td>Server PHP_SELF</td><td width="600"><b>'.$_SERVER["PHP_SELF"].'</b></td></tr>';
	$vi.= '<tr><td>Server Path</td><td width="600"><b>'. addslashes($_SERVER["PATH"]).'</b></td></tr>';
	$vi.= '<tr><td>Server Script-Filename</td><td width="600"><b>'.$_SERVER["SCRIPT_FILENAME"].'</b></td></tr>';
	$vi.= '<tr><td>Server Script-Name</td><td width="600"><b>'.$_SERVER["SCRIPT_NAME"].'</b></td></tr>';
	$vi.= '<tr><td>Global PHP_SELF</td><td width="600"><b>'.$PHP_SELF.'</b></td></tr>';
	$vi.= '<tr><td>Global Path</td><td width="600"><b>'. addslashes($PATH).'</b></td></tr>';
	$vi.= '<tr><td>Global Path-Translated</td><td width="600"><b>'. addslashes($PATH_TRANSLATED).'</b></td></tr>';
	$vi.= '<tr><td>Global Script-Filename</td><td width="600"><b>'.addslashes($SCRIPT_FILENAME).'</b></td></tr>';
	$vi.= '<tr><td>Global Script-Name</td><td width="600"><b>'.addslashes($SCRIPT_NAME).'</b></td></tr>';
	
	$evars=str_replace("<td>","",$vi);
	$evars=str_replace('<td width="600">',"",$evars);
	$evars=str_replace('<tr>',"",$evars);
	$evars=str_replace('</td>',"",$evars);
	$evars=str_replace('</tr>',"\n",$evars);
	
	
	
	//Form-vars
	$r.=$vi.'<input type="hidden" name="error_vars" value="'.$evars.'">';
	$r.='<input type="hidden" name="error_subject" value="'.$subject.'">';
	$r.='<input type="hidden" name="error_mailfrom" value="'.$config["email_sender"].'">';

	$r.= '<tr><td colspan="2" align="center"><input class="Formbutton_small" type="submit" name="error_submit" value="Fehlerbericht absenden"></td></tr>';


	$r.= "<tr><td><a href='main.php' class='ul'>Home</a></td></tr></table></div></form></body></html>";
	return $r;
}

function DynOutput($s,$fertig=0) {
	
	global $config;
	
	$out="out.html";
	$tempDatei=$config["paths"]["log"].$out;
	$tempURL="work/log/".$out;
	
	
	if($fertig==0) {
		//$meta='<meta http-equiv="Refresh" content="1">'."\n";
		$meta.='<meta http-equiv="Pragma" content="no-cache">'."\n";
		$meta.='<script language="JavaScript">
		function doReload() {
			document.loaction.href="";
		}
		setTimeout("doReload()", 400); 
		</script>';
		
	} else {
	
	}
	//Header f�r HTML
	$h="<html>\n<head>\n".$meta."\n<title>MySqlDumper</title>\n<link rel=\"stylesheet\" type=\"text/css\" href=\"../../styles.css\">";
	$h.="\n</head>\n<body bgcolor=\"#F5F5F5\">\n";
	//Footer
	$f='</body></html>';
	$page=$h.$s.$f;

	$f=fopen($tempDatei,"w");
	fwrite($f,$page);
	fclose($f);
	echo '<script type="text/javascript">parent.dump_visible.location.href='.$tempURL.';</script>';
	
	/*echo "<pre>$page</pre>";
	die;*/
}

function TesteFTP($ftp_server,$ftp_port,$ftp_user_name,$ftp_user_pass,$ftp_dir)
{
	global $lang,$config;
	if($ftp_port=="" || $ftp_port==0) $ftp_port=21;
	$pass=-1;
	if(!extension_loaded("ftp")) {
		$s='<span class="smallwarnung">'.$lang['noftppossible'].'</span>';
	} else $pass=0;
	
	if($pass==0) {
		if($ftp_server=="" || $ftp_user_name=="" || $ftp_user_pass=="") {
			$s='<span class="smallwarnung">'.$lang['wrongconnectionpars'].'</span>';
		} else $pass=1;
	}
	
	
	if($pass==1) {
		$s=$lang['connect_to'].' `'.$ftp_server.'` Port '.$ftp_port.' ... ';
		
		if($config["ftp_useSSL"]==0)
			$conn_id = @ftp_connect($ftp_server,$ftp_port,$config["ftp_timeout"]); 
		else
			$conn_id = @ftp_ssl_connect($ftp_server,$ftp_port,$config["ftp_timeout"]); 
		
		$login_result = @ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
		if(!$conn_id || (!$login_result)) {
			$s.='<span class="smallwarnung">'.$lang['conn_not_possible'].'</span>';
		} else $pass=2;
	} 
	
	if($pass==2) {
		$s.='<strong>ok, logged in</strong><br>'.$lang['changedir'].' `'.$ftp_dir.'` ... ';
		$dirc=@ftp_chdir($conn_id,$ftp_dir); 
		if(!$dirc) {
			$s.='<br><span class="smallwarnung">'.$lang['changedirerror'].'</span>';
		} else $pass=3;
		@ftp_close($conn_id);
	}
	if($pass==3) $s.='<br><br><strong>'.$lang['ftp_ok'].'</strong>';
	return $s;
}



function Realpfad($p) {
	global $config;
	if(!isset($config["disabled"])) $config["disabled"]=ini_get("disable_functions");
	if(strpos($config["disabled"],"realpath"))
		$s=getcwd();
	else
		$s=realpath($p);
	$s=str_replace("\\","/",$s);
	if(substr($s,-1)!="/") $s.="/";
	return $s;
}

function GetPerlConfigs() {
	global $config;
	$default=$config["cron_configurationfile"];
	$dh = opendir($config["paths"]["config"]);
	$r="";
	while (false !== ($filename = readdir($dh)))
	{
	    if ($filename != "." && $filename != ".." && !is_dir($config["paths"]["config"].$filename) && substr($filename,-5)==".conf") {
			$f=substr($filename,0,strlen($filename)-5);
			$r.='<option value="'.$f.'" ';
			if($filename==$default) $r.=' SELECTED';
			$r.='>&nbsp;&nbsp;'.$f.'&nbsp;&nbsp;</option>'."\n";
		}
	}
	return $r;
}
function GetThemes() {
	global $config;
	$default=$config["theme"];
	$dh = opendir($config["paths"]["root"]."css/");
	$r="";
	while (false !== ($filename = readdir($dh)))
	{
	    if ($filename != "." && $filename != ".." && is_dir($config["paths"]["root"]."css/".$filename)) {
			
			$r.='<option value="'.$filename.'" ';
			if($filename==$default) $r.=' SELECTED';
			$r.='>&nbsp;&nbsp;'.$filename.'&nbsp;&nbsp;</option>'."\n";
		}
	}
	return $r;
}
function GetLanguageCombo($k="op",$class="",$name="",$start="",$end="") {
	global $config,$lang;
	$default=$config["language"];
	$dh = opendir($config["paths"]["root"]."language/");
	$r="";
	while (false !== ($filename = readdir($dh)))
	{
	    if ($filename != "." && $filename != ".." && $filename != "flags" && is_dir($config["paths"]["root"]."language/".$filename)) {
			//echo $config["paths"]["root"]."language/".$filename."<br>";
			if($k=="op") {
				$r.=$start.'<option value="'.$filename.'" ';
				if($filename==$default) $r.=' SELECTED';
				$r.=' class="'.$class.'">&nbsp;&nbsp;'.$lang[$filename].'&nbsp;&nbsp;</option>'.$end."\n";
			} elseif($k=="radio") {
				$r.=$start.'<input type="radio" class="'.$class.'" name="'.$name.'" value="'.$filename.'" '.(($filename==$default) ? "checked" : "").' onclick="show_tooldivs(\''.$filename.'\');"><img src="language/flags/'.$filename.'.gif" alt="" width="25" height="15" border="0">&nbsp;&nbsp;&nbsp;'.$lang[$filename].$end."\n";
			}
		}
	}
	return $r;
}

function headline($title,$mainframe=1) {
	global $config;
	$s="";
	if($config["interface_server_caption"]==1 && $config["interface_server_caption_position"]==$mainframe) 
		$s.='<div class="server"><a class="server" href="http://'.$_SERVER["SERVER_NAME"].'" target="_blank" title="'.$_SERVER["SERVER_NAME"].'">'.$_SERVER["SERVER_NAME"].'</a></div>';
	if ($mainframe==1) {
		$s.='<div class="pagetitle">'.$title.'</div>';
	 	$s.='<div class="content">';
	}
	return $s;
}

function PicCache($rpath="./") {
	global $BrowserIcon;
	return '<div style="display:none"><img src="'.$rpath.'images/ardown.gif" width="12" height="13" alt=""><img src="'.$rpath.'images/arrowdown.gif" width="16" height="16" alt=""><img src="'.$rpath.'images/arrowleft.gif" width="16" height="16" alt=""><img src="'.$rpath.'images/arrowup.gif" width="16" height="16" alt=""><img src="'.$rpath.'images/arup.gif" width="12" height="13" alt=""><img src="'.$rpath.'images/close.gif" width="12" height="13" alt=""><img src="'.$rpath.'images/delete.gif" width="11" height="13" alt=""><img src="'.$rpath.'images/edit.gif" width="12" height="13" alt=""><img src="'.$rpath.'images/gz.gif" width="32" height="32" alt=""><img src="'.$rpath.'images/help16.gif" width="16" height="16" alt=""><img src="'.$rpath.'images/notok.gif" width="32" height="32" alt=""><img src="'.$rpath.'images/ok.gif" width="32" height="32" alt=""><img src="'.$rpath.'images/openfile.gif" width="16" height="16" alt=""><img src="'.$rpath.'images/blank.gif" width="1" height="1" alt=""><img src="'.$rpath.'images/cellpic.gif" width="9" height="37" alt=""><img src="'.$rpath.'images/fbd.gif" width="40" height="16" alt=""><img src="'.$rpath.'images/fbr.gif" width="40" height="16" alt=""><img src="'.$rpath.'images/fbs.gif" width="40" height="16" alt=""><img src="'.$rpath.'images/konqueror.png" width="10" height="14" alt=""><img src="'.$rpath.'images/mozilla.png" width="14" height="14" alt=""><img src="'.$rpath.'images/msie.png" width="14" height="14" alt=""><img src="'.$rpath.'images/omniweb.png" width="14" height="14" alt=""><img src="'.$rpath.'images/opera.png" width="14" height="14" alt=""><img src="'.$rpath.'images/safari.png" width="14" height="14" alt=""><img src="'.$rpath.$BrowserIcon.'" alt=""></div>';
}

function MSDHeader($kind=0)
{
	global $config, $databases,$lang;
	//kind 0=main 1=menu 2=help
	$d=($kind==2) ? "../../" : "./";
	$r='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html>
<head>';
if ($kind==1) $r.='<META HTTP-EQUIV="cache-control" CONTENT="public" />';
else $r.='<META HTTP-EQUIV="Pragma" CONTENT="no-cache" />';
$r.='<META NAME="robots" CONTENT="none" />
<META NAME="robots" CONTENT="noindex">
<title>MySqlDumper</title>
<link rel="stylesheet" type="text/css" href="'.$d.'css/'.$config["theme"].'/style.css">
<script language="JavaScript" src="'.$d.'js/script.js"></script>';
	$r.='<script language="JavaScript">
			var params="";
			if(document.URL.indexOf("/") != -1)  
			{
    			var stringArray = document.URL.split("/");
    			params = stringArray.pop();  
			}
			if (parent.frames.length == 0) { parent.location.href="index.php?page="+params; }
		</script>';
	$r.='
</head>
<body '.(($kind==1) ? 'class="menu"' : '').'>
	';
	
	return $r;
}
function MSDFooter($rfoot="",$enddiv=1)
{
	global $config, $databases,$dump,$restore,$lang;
	
	$svice=(isset($_GET["svice"])) ? $_GET["svice"] : 0;
	$config_array=(isset($config)) ? "<strong>CONFIG</strong><pre>".@print_r($config,true)."</pre>": "";
	$database_array=(isset($databases)) ? "<strong>DATABASE</strong><pre>".@print_r($databases,true)."</pre>": "";
	$dump_array=(isset($dump)) ? "<strong>DUMP</strong><pre>".@print_r($dump,true)."</pre>": "";
	$restore_array=(isset($restore)) ? "<strong>RESTORE</strong><pre>".@print_r($restore,true)."</pre>" : "";
	$f= '<p align="center" class="small">'.$lang['authors'].':&nbsp;
	<a class="small" href="http://www.daniel-schlichtholz.de" target="_new">
	Daniel Schlichtholz & Steffen Kamper</a> - Infoboard: 
	<a class="small" href="'.$config["homepage"].'" target="_blanc">'.
	$config["homepage"].'</a></p>';
	
	if($svice==1) $f.= '<table width="100%"><tr bgcolor="#808080"><td>'.$config_array.'</td></tr><tr bgcolor="#808080"><td>'.$database_array.'</td></tr><tr bgcolor="#808080"><td>'.$dump_array.'</td></tr><tr bgcolor="#808080"><td>'.$restore_array.'</td></tr></table>';
	if($enddiv==1) $f.= '</div>';
	
	$f.=$rfoot.'</body></html>';
	
	return $f;
}

function DownGrade($s,$show=true)
{
	if (MSD_NEW_VERSION && strpos(strtolower($s),"collate ") && ($show) ) {
		return $s;
	} else {
		$tmp=explode(",",$s);
		for($i=0;$i<count($tmp);$i++) {
			$t=strtolower($tmp[$i]);
			if(strpos($t,"collate ")) {
				$tmp2=explode(" ",$tmp[$i]);
				for($j=0;$j<count($tmp2);$j++) {
					if(strtolower($tmp2[$j])=="collate") {$tmp2[$j]="";$tmp2[$j+1]="";$j++;}
				}
				$tmp[$i]=implode(" ",$tmp2);
			}
			if(strpos($t,"engine=")) {
				$tmp2=explode(" ",$tmp[$i]);
				for($j=0;$j<count($tmp2);$j++) {
					//echo $j.": ".$tmp2[$j]."<br>";
					if(substr(strtoupper($tmp2[$j]),0,7)=="ENGINE=") $tmp2[$j]="TYPE=".substr($tmp2[$j],7);
					if(substr(strtoupper($tmp2[$j]),0,8)=="COLLATE=") $tmp2[$j]="";
					if(substr(strtoupper($tmp2[$j]),0,8)=="CHARSET=" && strtoupper($tmp2[$j-1])=="DEFAULT") {$tmp2[$j-1]="";$tmp2[$j]="";}
				}
				$tmp[$i]=implode(" ",$tmp2);
			}

		}	
		$t=implode(",",$tmp);
		if(substr(rtrim($t),-1)!=";") $t=rtrim($t).";";
		return $t;
	}
}
?>
