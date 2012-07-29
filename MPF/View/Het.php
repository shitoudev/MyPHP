<?php
/**
 * Het接口
 */

require_once("Common.php");
class View_Het extends View_Common implements iView
{
	private $tpl;
	private $tplFile;
	private $tplData;
	private $template_dir;
	
	public function __construct($tplFile, $tplData)
	{
		$response = Response::getInstance();
		include_once('Libs/Het/Het.php');
		$this -> tpl = new HET ;
		$this -> tpl -> compile_dir = MY_HET_COMPILE_DIR; // 編繹目錄
		$this -> tpl -> single_dir  = MY_HET_SINGLE_DIR;  // 單目錄模式
		$this -> tpl -> cache_dir   = MY_HET_CACHE_DIR;   // 緩存目錄
		$this -> tpl -> caching     = MY_CACHE_DEFAULT;   // 是否緩存
		if ($this -> tpl -> caching) {
			$this -> tpl ->use_cache();
		}
		$this -> tpl -> cache_safe  = MY_HET_CACHE_SAFE;		
		// 模板調用自定義函數
		$this -> tpl->fn ( unserialize(MY_HET_FN) );
		$this -> tpl->fn ( $response->get('fn') );
		
		$this->tplFile = $tplFile;
		$this->tplData = $tplData;
		$this -> template_dir = 'Views/'.MY_TEMPLATE.'/'.$controllerPath;		
	
	}
	
	public function display()
	{		
	    $this ->tpl->out($this -> template_dir.$this -> tplFile, $this->tplData);
	    if (MY_DEBUG) {		
			// 输出视图变量
			dump($this->tplData);
			// 输出sql
			$response = Response::getInstance();
			echo $response->get('sql');
		}
	    
	}
	
	public function fetch()
	{
		return $this -> tpl -> result($this -> template_dir.$this -> tplFile, $this->tplData);
	}
}
?>