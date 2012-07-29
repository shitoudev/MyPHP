<?php
/**
 * 该类为私有类,只用于 Xml 工具间接的调用, 注意:它只能处理以下表记录结构的二维数组格式
 * array(array('id'=>1,'name'=>'yuan'),
 *       array('id'=>2,'name'=>'wei'),
 *       array('id'=>3,'name'=>'yuanwei'));
 * 
 */
class My_ParseXmlToArray
{
	private $data = array();
	private $element = '';
	private $rootElement = '';
	private $row = array();
	private $tag;
	public function __construct($xmlContent,$element,$rootElement)
	{
		$this->element = $element;
		$this->rootElement = $rootElement;
		//创建XML解析器
		$parser = xml_parser_create();
		//设置对象
		xml_set_object($parser,$this);  
		//设置处理开始和结束的标记函数
		xml_set_element_handler($parser,"elementOn","elementOff");     
		//设置数据管理事件,即处理解析到的数据处理函数
		xml_set_character_data_handler($parser,"parseData");
		//要求严格区分大小写
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		//开始解析
		xml_parse($parser,$xmlContent); 
		//释放空间
		xml_parser_free($parser);
	}
	
	/**
	 * 返回解析后的数组
	 */
	public function getData()
	{
		return $this->data;
	}
	/**
	 * 处理开始元素标记的函数 
	 * 参数: $parser:解析器句柄 $tag:元素标记 $attributes:元素属性
	 */        
	private function elementOn($parser,$tag,$attributes)
	{
		$this->tag = $tag;
		if ($tag == $this->element) $this->row = array();
	}
	
	/*
	  功能:处理结束元素标记的函数
	  参数: $parser:解析器句柄 $tag:元素标记
	*/
	private function elementOff($parser,$tag) 
	{
		if ($tag == $this->element) $this->data[] = $this->row;
	}

	/*
	  功能:处理元素数据的函数 
	  参数: $parser:解析器句柄 $cdata:元素数据
	*/
	private function parseData($parser,$cdata)
	{
		//元素是否为开始标记，并且不是根元素,并且数据不是换行符
		if($this->tag!=$this->rootElement && $this->tag!=$this->element && ord($cdata)!=10) 
		{
			$this->row[$this->tag] = $cdata;
		}
	}
	
}//end Class    
?>