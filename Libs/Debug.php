<?php
// 消耗内存
function convert($size)
{ 
	$unit = array('b','k','m','g','t','p'); 
	return @round($size/pow(1024, ($i=floor(log($size,1024)))),2).''.$unit[$i]; 
}
// 
function microtime_float($microtime = NULL)
{
	list($usec, $sec) = explode(' ', !$microtime ? microtime(TRUE) : $microtime);
	return ((float)$usec + (float)$sec);
}
// 赋值
$runTime   = @round(microtime_float() - microtime_float(MYPHP_BEGIN_TIME), 8);
$runMem    = convert(memory_get_usage() - MYPHP_START_MEMS);
$dbBebug   = Myphp::get('debug', 'db');
$flowBebug = Myphp::get('debug', 'flow');
$tplData   = Myphp::get('debug', 'tplData');
$debug     = Myphp::get('debug');
unset($debug['db'], $debug['flow'], $debug['tplData']);
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>debug</title>
</head>
<body>
<table border=0 width=100%>
	<tr><th style="color:#000000;background-color:#FFFFDD;padding:10px 10px 10px 36px;border:0.1em solid #CC6633">页面执行 调试信息</th></tr>
	<tr align="left" bgcolor=#FFFFFF>
		<td>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">执行时间:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo $runTime; ?>(s)</span>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">内存消耗:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo $runMem;?></span>
		</td>
	</tr>
</table>

<?php if (!empty($dbBebug)) {?>
<table border=0 width=100%>
	<tr><th style="color:#000000;background-color:#FFFFDD;padding:10px 10px 10px 36px;border:0.1em solid #CC6633">数据库 SQL 调试信息</th></tr>

	<?php
	foreach($dbBebug as $k=>$v)
	{
		$totalTime += $v['time'];
	?>
	<tr align="left" bgcolor=#FFFFFF>
		<td>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">执行时间:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo round($v['time'], 8);?>(s)</span>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">累计时间:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo round($totalTime, 8);?>(s)</span>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">SQL:</span><span style="color:blue;border-bottom:1px solid #DADADA;"><?php echo $v['sql'];?>;</span>
		</td>
	</tr>
	<?php
	}
	?>

</table>
<?php }?>

<table border=0 width=100%>
	<tr bgcolor=#cccccc><th colspan=2 style="color:#000000;background-color:#FFFFDD;padding:10px 10px 10px 36px;border:0.1em solid #CC6633">页面流 调试信息</th></tr>

	<tr bgcolor=#cccccc><td colspan=2><b>common Data:</b></td></tr>
	<?php
	if ($flowBebug['common'])
	{
	?>
	<?php
	foreach($flowBebug['common'] as $k => $v)
	{
		$mem = $v['mem'] < 0 ? 0 : round(($v['mem']/1024), 3);
	?>
	<tr align="left" bgcolor=#FFFFFF>
		<td>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">执行时间:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo round($v['time'], 8);?>(s)</span>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">消耗内存:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo $mem;?>k</span>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">common:</span><span style="color:blue;border-bottom:1px solid #DADADA;"><?php echo $v['txt'];?></span>
		</td>
	</tr>
	<?php
	}
	?>
	<?php
	}
	else
	{
	?>
	<tr bgcolor=#eeeeee><td colspan=2><tt><i>no debug data</i></tt></td></tr>
	<?php
	}
	?>

	<tr bgcolor=#cccccc><td colspan=2><b>view Data:</b></td></tr>
	<?php
	if ($flowBebug['view'])
	{
	?>
	<?php
	foreach($flowBebug['view'] as $k => $v)
	{
		$mem = $v['mem'] < 0 ? 0 : round(($v['mem']/1024), 3);
	?>
	<tr align="left" bgcolor=#FFFFFF>
		<td>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">执行时间:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo round($v['time'], 8);?>(s)</span>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">消耗内存:</span><span style="color:red;border-bottom:1px solid #DADADA;"><?php echo $mem;?>k</span>
			<span style="color:#339966;border-bottom:1px solid #DADADA;background:#F7F7F7">tpl:</span><span style="color:blue;border-bottom:1px solid #DADADA;"><?php echo $v['txt'];?>.php</span>
		</td>
	</tr>
	<?php
	}
	?>
	<?php
	}
	else
	{
	?>
	<tr bgcolor=#eeeeee><td colspan=2><tt><i>no debug data</i></tt></td></tr>
	<?php
	}
	?>
</table>

<table border=0 width=100%>
	<tr bgcolor=#cccccc><th colspan=2 style="color:#000000;background-color:#FFFFDD;padding:10px 10px 10px 36px;border:0.1em solid #CC6633">Debug 调试信息</th></tr>
	<tr bgcolor=#cccccc><td colspan=2><b>Debug Data:</b></td></tr>
	<?php
	if ($debug)
	{
	?>
	<tr align="left" bgcolor=#eeeeee><td colspan=2 ><tt><?php dump($debug);?><font size=-1></font></tt></td></tr>
	<?php
	}
	else
	{
	?>
	<tr bgcolor=#eeeeee><td colspan=2><tt><i>no debug data</i></tt></td></tr>
	<?php
	}
	?>
</table>

<table border=0 width=100%>
	<tr bgcolor=#cccccc><th colspan=2 style="color:#000000;background-color:#FFFFDD;padding:10px 10px 10px 36px;border:0.1em solid #CC6633">视图 调试信息</th></tr>
	<tr bgcolor=#cccccc><td colspan=2><b>Templates Data:</b></td></tr>
	<?php
	if ($tplData)
	{
	?>
	<tr align="left" bgcolor=#eeeeee><td colspan=2 ><tt><?php dump($tplData);?><font size=-1></font></tt></td></tr>
	<?php
	}
	else
	{
	?>
	<tr bgcolor=#eeeeee><td colspan=2><tt><i>no templates included</i></tt></td></tr>
	<?php
	}
	?>
</table>

</body>
</html>