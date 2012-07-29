<?php
/**
 * 简单XML的应用,注意：该工具只能处理以下表记录结构的二维数组格式
 * array(array('id'=>1,'name'=>'yuan'),
 *       array('id'=>2,'name'=>'wei'),
 *       array('id'=>3,'name'=>'yuanwei'));
 *
 * 新增方法: toTree() 将任何XML文件或内容解析为树型数组
 *
 */
/*
[示例]
-------------------- Mxml.php 控制器代码如下 -----------------------
$array = array(array('id'=>1,'name'=>'yuan'),
               array('id'=>2,'name'=>'wei'),
               array('id'=>3,'name'=>'yuanwei'));
$xml = new Xml();
$xmlStr = $xml->toXml($array); //将数组以对应的XML形式输出
$xml->save($xmlStr,'test.xml') //保存为XML文件
$ay = $xml->toArray('test.xml'); //将XML格式转为数组
dump($ay);
------------------------------------------------------------------

[注意]
1:该工具只能处理示例中这种格式的二维数组,换句话说就是只能处理像查询数据库表后的二级结果集数组

*/
define("XML_ENTER",chr(13).chr(10));
class Xml{
    
    /**
     * 将数组转为XML
     *
     * @param array $data :数组
     * @param string $encoding :XML的编码
     * @param string $rootElement :XML的根结点
     * @return string
     */
    static public function toXml($data,$element='data',$rootElement="MyPHPPHP",$encoding='utf-8')
    {
    	$xml = '<?xml version="1.0" encoding="'.$encoding.'"?>'.XML_ENTER;
    	$xml.= '<'.$rootElement.'>'.XML_ENTER;
    	foreach ($data as $val)
    	{
    	    $xml.= '<'.$element.'>'.XML_ENTER;
    	    foreach ($val as $k=>$v) $xml .= "<$k>$v</$k>".XML_ENTER;
    	    $xml.= '</'.$element.'>'.XML_ENTER;
    	}
    	$xml.= '</'.$rootElement.'>'.XML_ENTER;
    	return $xml;
    }
    
    /**
     * 将 toXml() 方法得到的XML保存为文件
     *
     * @param string $xmlContent
     * @param string $xmlFile
     */
    static public function save($xmlContent,$xmlFile)
    {
        return File::write($xmlFile,$xmlContent);
    }
    
    /**
     * 将XML解析为数组，注意:$xmlFile 必需是由 toXml() 方法得到的XML文档
     * @param string $xmlFileOrContent : XML文件 或 XML内容
     */
    static public function toArray($xmlFileOrContent,$element='data',$rootElement="MyPHPPHP")
    {
		//不是文件则认为是XML内容
        if (file_exists($xmlFileOrContent)) $xmlText = File::read($xmlFileOrContent);
		else $xmlText = $xmlFileOrContent;
		include_once(MY_CORE_ROOT.'Tools/_Libs/parseXmlToArray.php');
		include_once(MY_CORE_ROOT.'Tools/_Libs/parseXmlToArray.php');
        $xml = new My_ParseXmlToArray($xmlText,$element,$rootElement);
        return $xml->getData();
    }

    /**
     * 新增方法:将XML内容解析为 树型 数组类型，支持XML节点中的属性解析
     * @param string $xmlFileOrContent : XML文件 或 XML内容
     */
    static public function toTree($xmlFileOrContent)
    {
		//不是文件则认为是XML内容
        if (file_exists($xmlFileOrContent)) $xmlText = File::read($xmlFileOrContent);
		else $xmlText = $xmlFileOrContent;
		include_once(MY_CORE_ROOT.'Tools/_Libs/parseXmlToTree.php');
        $xml = new My_ParseXmlToTree();
		$xml->parseString($xmlText);
        $tree = $xml->getTree();
		$xml->free();
        return $tree;
    }
	
}
?>