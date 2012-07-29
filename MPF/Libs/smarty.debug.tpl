{* Smarty *}

{* debug_print_var debug.tpl, last updated version 2.0.1 *}

{assign_debug_info}

<table border=0 width=100% style="margin-top:20px;">
	<tr bgcolor=#cccccc><th colspan=2 style="color:#000000;background-color:#FFFFDD;padding:10px 10px 10px 36px;border:0.1em solid #CC6633">MyPHP (Ver <font color=red>{$smarty.const.MYPHP_VER}</font>) 调试信息</th></tr>
	<tr align="left"  bgcolor=#eeeeee><td colspan=2><tt>
	<font color=green> 来源URL:&nbsp;</font><font color=blue>{$request->frontURL()}</font>
	</tt></td></tr>	
	
	<tr align="left"  bgcolor=#eeeeee><td colspan=2><tt>
	<font color=green> 当前URL:&nbsp;</font><font color=blue>{$request->currentURL()}</font>
	</tt></td></tr>	
	
	<tr align="left"  bgcolor=#eeeeee><td colspan=2><tt>
	<font color=green>　控制器:&nbsp;</font><font color=blue>{$response->get('ControllerFile')}</font>
	</tt></td></tr>	

	<tr align="left"  bgcolor=#eeeeee><td colspan=2><tt>
	<font color=green>动作名称:&nbsp;</font><font color=blue>{$response->get('ActionName')}</font>
	</tt></td></tr>	

	<tr align="left"  bgcolor=#eeeeee><td colspan=2><tt>
	<font color=green>视图文件:&nbsp;</font><font color=blue>{$response->get('TemplateFile')}</font>
	</tt></td></tr>	
</table>	
<table border=0 width=100%>
	
	<tr align="left"  bgcolor=#eeeeee><td width="20%" valign=top><tt><font color=blue>$_GET</font></tt></td><td width="83%" nowrap><tt><font color=green>{$request->get()|@dump}</font></tt></td></tr>

	<tr align="left"  bgcolor=#eeeeee><td valign=top><tt><font color=blue>$_POST</font></tt></td><td nowrap><tt><font color=green>{$request->getPost()|@dump}</font></tt></td></tr>

	<tr align="left"  bgcolor=#eeeeee><td valign=top><tt><font color=blue>$_COOKIE</font></tt></td><td nowrap><tt><font color=green>{$request->getCookie()|@dump}</font></tt></td></tr>
	
	<tr align="left"  bgcolor=#eeeeee><td valign=top><tt><font color=blue>$_SESSION</font></tt></td><td nowrap><tt><font color=green>{$request->getSession()|@dump}</font></tt></td></tr>

{if $response->getDebug()}		
		<tr align="left"  bgcolor=#eeeeee><td valign=top><tt><font color=blue>$response->Debug</font></tt></td><td nowrap><tt><font color=green>{$response->getDebug()|@dump}</font></tt></td></tr>
{/if}

{if $response->getError()}		
		<tr align="left"  bgcolor=#eeeeee><td valign=top><tt><font color=blue>$response->Error</font></tt></td><td nowrap><tt><font color=green>{$response->getError()|@dump}</font></tt></td></tr>
{/if}
</table>
	{* 数据库SQL调试语句 *}
	{assign var='prec' value=8} {* 浮点数显示多少位 *}
	{$response->getSQLDebugInfo($prec)}
	
<table border=0 width=100%>
	<tr bgcolor=#cccccc><th colspan=2>Smarty Debug Console</th></tr>
	<tr bgcolor=#cccccc><td colspan=2><b>included templates & config files (load time in seconds):</b></td></tr>
	{section name=templates loop=$_debug_tpls}
		<tr align="left" bgcolor={if %templates.index% is even}#eeeeee{else}#fafafa{/if}><td colspan=2 ><tt>{section name=indent loop=$_debug_tpls[templates].depth}&nbsp;&nbsp;&nbsp;{/section}<font color={if $_debug_tpls[templates].type eq "template"}brown{elseif $_debug_tpls[templates].type eq "insert"}black{else}green{/if}>{$_debug_tpls[templates].filename|escape:html}<s/font>{if isset($_debug_tpls[templates].exec_time)} <font size=-1><i>({$_debug_tpls[templates].exec_time|string_format:"%.5f"}){if %templates.index% eq 0} (total){/if}</i></font>{/if}</tt></td></tr>
	{sectionelse}
		<tr bgcolor=#eeeeee><td colspan=2><tt><i>no templates included</i></tt></td></tr>	
	{/section}

	<tr bgcolor=#cccccc><td colspan=2><b>assigned template variables:</b></td></tr>
		
	{section name=vars loop=$_debug_keys}
		<tr align="left"  bgcolor={if %vars.index% is even}#fafafa{else}#eeeeee{/if}><td valign=top><tt><font color=blue>{ldelim}${$_debug_keys[vars]}{rdelim}</font></tt></td><td nowrap><tt><font color=green>{$_debug_vals[vars]|@dump}</font></tt></td></tr>
	{sectionelse}
		<tr bgcolor=#eeeeee><td colspan=2><tt><i>no template variables assigned</i></tt></td></tr>	
	{/section}

	
	<tr bgcolor=#cccccc><td colspan=2><b>assigned config file variables (outer template scope):</b></td></tr>
	{section name=config_vars loop=$_debug_config_keys}
		<tr align="left"  bgcolor={if %config_vars.index% is even}#eeeeee{else}#fafafa{/if}><td valign=top><tt><font color=maroon>{ldelim}#{$_debug_config_keys[config_vars]}#{rdelim}</font></tt></td><td><tt><font color=green>{$_debug_config_vals[config_vars]|@debug_print_var}</font></tt></td></tr>
	{sectionelse}
		<tr bgcolor=#eeeeee><td colspan=2><tt><i>no config vars assigned</i></tt></td></tr>	
	{/section}
	</table>
    
</BODY></HTML>
