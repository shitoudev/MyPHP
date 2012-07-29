<?php
/**
 * 基于INI 文件的配置引擎工具
*/
class Config
{
    private $initSection = '';
	private $obj = null;
	private $initFile = '';
    static private $instance = null;
	
    /**
     * 构造函数,自动调用 init() 进行初始化
     *
     * @param string $iniFile :ini文件名
     * @param string $section :配置节点
     */
	public function __construct($iniFile='',$section='')
	{
	    $this->init($iniFile,$section);
	}
    
	/**
	 * 载入配置初始化
	 *
     * @param string $iniFile :ini文件名
     * @param string $section :节点名称
	 */
	public function init($iniFile='',$section='')
	{
		$this->obj = new stdClass;
        if ($iniFile != '')
        {
            if (!file_exists($iniFile)) 
            {
                $lang = Lang::getInstance();
                throw new Exception(sprintf($lang->get('Core_FileNotFound'),$iniFile));
            }
            $iniVal = parse_ini_file($iniFile,true);
            //--解析单个结点
            if ($section != '' && isset($iniVal[$section]))
            {
                $this->initSection = $section;
                foreach ($iniVal[$section] as $key=>$val)
                {
                    $this->obj->$key = $val;
                }
            }
            //--解析所有结点
            else{
                foreach ($iniVal as $section=>$arrVal)
                {
                    $this->obj->$section = new stdClass;
                    foreach ($arrVal as $key=>$val)
                    {
                        $this->obj->$section->$key = $val;
                    }
                }
            }
            $this->initFile = $iniFile;
        }//End if ($iniFile != '')
        return true;
	}
	
	/**
	 * 得到成员值,该方法由PHP系统自动调用
	 *
	 * @param string $key :调用的成员名称
	 * @return unknown
	 */
	public function __get($key)
	{
		return isset($this->obj->$key) ? $this->obj->$key : null;
	}

	/**
	 * 添加结点或键值
	 * 
	 * 注意: $arrVal 的格式为: array('name'=>'梨子',
	 *                             'sex'=>'男')
	 * 
	 * @param string $section :结点名称
	 * @param string $arrVal  :结点对应的键与值
	 */
    public function add($section,$arrVal=array())
    {
        if (!isset($this->obj->$section))
        {
            $this->obj->$section = new stdClass;
        }
        
        foreach ($arrVal as $key=>$val)
        {
            $this->obj->$section->$key = $val;
        }
        return true;
    }

    /**
     * 删除结点或结点的键
     *
     * @param string $section:结点名称
     * @param string|array $sectionKey:结点的键,如果是多个键则用数组传值,如: array('key1','key2');
     */
    public function del($section,$sectionKey='')
    {
        //--删除结点
        if ($sectionKey == '')
        {
            if (isset($this->obj->$section)) unset($this->obj->$section);
        }
        //--删除结点中的项
        else{
            if (!is_array($sectionKey)) $sectionKey = array($sectionKey);
            foreach ($sectionKey as $key)
            {
                if (isset($this->obj->$section->$key)) unset($this->obj->$section->$key);                
            }
        }
        return true;
    }
    
    /**
     * 判断指定的结点是否存在
     * 
     * @param string $section :结点名称
     */
    public function sectionExists($section)
    {
        if ($this->initSection == $section)
        {
            return true;
        }
        else{
            $array = (array)$this->obj;
            foreach ($array as $key=>$val)
            {
                if (is_object($val))
                {
                    if ($key == $section) return true;
                }
            }            
        }
        return false;
    }
    
    /**
     * 返回当前所有结点名称列表
     * @return array
     */
    public function sectionList()
    {
        $temp = array();
        $array = (array)$this->obj;
        foreach ($array as $key=>$val)
        {
            if (is_object($val)) $temp[] = $key;                
        }
        if ($this->initSection != '') $temp[] = $this->initSection;
        return $temp;
    }
    
    /**
     * 返回结点的所有键与其值
     * @return array
     */
    public function sectionValue($section)
    {
        $temp = array(); 
        if (isset($this->obj->$section))
        {
            $temp = (array)$this->obj->$section;
        }
        else{
            if ($section == $this->initSection)
            {
                $array = (array)$this->obj;
                foreach ($array as $key=>$val)
                {
                    if (!is_object($val))
                    {
                        $temp[$key] = $val;                   
                    }
                }                
            }
        }
        return $temp;
    }
    
    /**
     * 将当前操作的配置保存为文件
     *
     * @param string $saveIniFile :保存的INI文件名,如果为空则保存到构造函数或init()中所指定的文件中
     */
    public function save($saveIniFile='')
    {
        if ($saveIniFile == '') $saveIniFile = $this->initFile;
        if (!file_exists($saveIniFile)) 
        {
            $lang = Lang::getInstance();
            throw new Exception(sprintf($lang->get('Core_FileNotFound'),$saveIniFile));
        }        
        $fp = fopen($saveIniFile,'w+');
        if (!$fp)
        {
            $lang = Lang::getInstance();
            throw new Exception(sprintf($lang->get('Core_FileNotWirte'),$saveIniFile));
        }
        $array = (array)$this->obj;
        $temp = array(); 
        foreach ($array as $key=>$val)
        {
            if (is_object($val))
            {
                $str = "[$key]\r\n";
                fwrite($fp,$str);
                $arr = (array)$val;
                foreach ($arr as $k=>$v)
                {
                    $str = $k." = ".$v."\r\n";
                    fwrite($fp,$str);
                }
                fwrite($fp,"\r\n");
            }
            else{
               $temp[$key] = $val;
            }
        }
        
        if (count($temp) > 0)
        {
            $key = $this->initSection;
            $str = "[$key]\r\n";
            fwrite($fp,$str);
            foreach ($temp as $k=>$v)
            {
                $str = $k." = ".$v."\r\n";
                fwrite($fp,$str);
            }
            fwrite($fp,"\r\n");
        }
        fclose($fp);
        return true;
    }
    
    /**
     * 静态生成单例
     *
     * @return object
     */
	static public function getInstance($iniFile='',$section='') 
	{
    	if (null === self::$instance)
    	{
            self::$instance = new Inifile($iniFile,$section);
        }
        self::$instance->init($iniFile,$section);
        return self::$instance;
    }    
}
?>