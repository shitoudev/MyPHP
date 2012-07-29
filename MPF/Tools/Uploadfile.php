<?php
/*
 @名称:文件上传工具
 @功能:单个文件上传,类可自动得到控件的名字,无需指定表单中的名称
 @版本:V2.0
 
-------------- 公共成员接口如下 ---------------------

setAttrib()        :设置上传的属性
setInputName()     :设置 FILE 表单域的名称
isUploaded()       :判断当前是否有文件上传了
upload()           :执行上传文件
error()            :返回错误信息
getUploadedFile()  :得到上传后的文件名

别忘记 form 标记中指定 enctype="multipart/form-data" 
---------------------------------------------------

[示例如下]
----------------- Upload.php 控制器代码如下 ---------------
$set = array(
         'fexts'=>'.jpg|.gif|.png', //可以上传的文件扩展名
         'maxSize'=>0, //单位为K,0:表示不限制
         'savePath'=>'./data/',//上传文件后保存的目录(要可写)
         'isThumb'=>true, //生成缩略图
         'thumb'=>array(array(100,120));//只生成一张缩略图,宽:100 高:120
     );        
//如果当前提交表单中只有一个 FILE 域的话,则不需要指定期名称,否则需要指定其FILE域名称.
//如 new uploadfile(''); 要想在多个不同FILE域名之间切换的话请用如 $up->setInputName('picfile');
$up = new uploadfile();
$up->setAttrib($set); //设置属性
$pic = '';
if ($up->isUploaded()) //有文件上传
{
    $up->upload();//开始上传      
    if ($up->hasError()) //如果发生了错误
    {
        return View::Alert($up->error(),5);
    }else{
        echo $pic = $up->getUploadedFile(); //得到上传后的文件名
        dump($up->getThumbFile()); //得到缩略图文件名
    }
}
---------------------------------------------------------
[注意]
1:该工具暂不支持 FILE域为数组的方式上传,如下
 <input type="file" name="pfile[]" />
 <input type="file" name="pfile[]" />
 
2:对于上传文件安全方面,该版本暂时只是简单的基于扩展名判断的,下一版本将改为基于文件类型判断.
*/
 class Uploadfile{
     
     private $set = array(
         'fexts'=>'*',  //上传的文件扩展名集合,"*":表示任何文件;格式为:".jpg|.swf|.png"
         'maxSize'=>0,  //允许的最大文件大小,单位:KB [0]:表示任何大小
         'isDel'=>false,//当目标文件存在时是否删除
         'savePath'=>'',//上传文件后保存的目录(要可写)       
         'fileName'=>'',//上传文件后保存的文件名(不需要直接设置),请参考 upload() 方法

         'isThumb'=>false,//是否为生成缩略图,注意:缩略图都是JPG类型的
         'thumb'=>array(),//生成缩略图的尺寸,支持多组尺寸(即同时生成多张缩略图)定义.格式如下:
/*
  'thumb'=>array(array(50,40), //第一组尺寸, 50:缩略图最大宽度 40:缩略图最大高度
                 array(100,80),//第二级尺寸
                ); 

  通过以上设置后,生成的缩略图文件名格式为:"[缩略图宽]_[缩略图高]_[上传后的文件名(包含扩展名)]_[thumb.jpg]" 
  例如文件上传后的文件名为:"xxxxxx.jpg"则第一组尺寸生成后的缩略图文件名为:"50_40_xxxxxx.jpg_thumb.jpg"
*/
     );
     
     private $thumbFiles = array(); //缩略图文件名
	 private $inputName = ''; //FILE控件名称
	 private $errorNo = -1;   //错误序号
	 private $error = array(  //错误定义
	     0=>'没有指定FILE表单域',
	     1=>'没有文件上传',
	     2=>'文件超过大小',
	     3=>'不是指定的类型',
	     4=>'目标文件已存在',
	     5=>'目标文件不能写入',
	 );
	 
	 /**
	  * 判断是否出错了
	  */
	 public function hasError()
	 {
	     return $this->errorNo>-1;
	 }
	 
	 /**
	  * 返回错误信息
	  *
	  * @return string
	  */
	 public function error()
	 {
	     return $this->hasError() ? $this->error[$this->errorNo] : '';
	 }
	 
     /**
      * 构造函数
      * 
      * @param string $inputName: 表单中FILE控件名称,如   <input type="file" name="upfile"> 中的"upfile"
      * 说明:如果 $inputName == '' 则自动使用第一个 FILE 控件的名称,这种情况很适合表单中只有一个FILE控件 
      */
	 public function __construct($inputName = '')
	 {
		 if($inputName != '')
		 {
		    $this->inputName = $inputName;
		 }else{
		 	list($h, $v) = each($_FILES);
		 	$this->inputName = $h;
		 }
	 }
	 
 	/**
	 * 判断当前表单是否有文件上传了
	 *
	 * @return bool
	 */
	 public function isUploaded()
	 {
		 return $_FILES[$this->inputName]['name'] != '';
	 }

	 /**
	  * 设置 FILE控件名称
	  *
	  * @param string $inputName:FILE域名称
	  */
	 
	 public function setInputName($inputName)
	 {
		 $this->inputName = $inputName;
	 }

	 /**
	  * 设置上传属性
	  *
	  * @param array $sets:属性设置
	  */
	 public function setAttrib($sets)
	 {
	     $this->set = array_merge($this->set,$sets);
	 }
     
	 /**
	  * 上传文件动作
	  * 
	  * 说明:$dstFileName 有以下三种处理情况
	  * 1:为空时,系统自动生成随机唯一的文件名;其扩展名与上传时所选择的文件一致.
	  * 2:没有指定扩展名时如 "fileName".则系统自动加上上传时所选择文件的扩展名
	  * 3:指定了完整的文件名如 "fileName.jpg" 则完全采用自定义的文件名
	  * 
	  * @param string $dstFileName 目标文件名
	  * @return bool
	  */
	 public function upload($dstFileName='')
	 {
	     if ($this->inputName == '')
	     {
	         $this->errorNo = 0;
	         return false;
	     }
		 $this->_makeFileName($dstFileName);
		 
		 $tmpFile = $_FILES[$this->inputName]['tmp_name'];
		 if ($tmpFile =='' || !is_uploaded_file($tmpFile))
		 {
	         $this->errorNo = 1;
	         return false;
		 }
		 
		 if ($this->set['maxSize'] > 0)
		 {
		     if ($_FILES[$this->inputName]['size'] > ($this->set['maxSize']*1024))
		     {
		         $this->errorNo = 2;
		         return false;
		     }
		 }
		 
		 $ext = $this->_fileExt($this->set['fileName']);
		 if ($this->set['fexts'] != '*')
		 {
		     if(strstr($this->set['fexts'],$ext) == '' )
		     {
		         $this->errorNo = 3;
		         return false;
		     }
		 }
		 
		 $dstfile = $this->set['savePath'].$this->set['fileName'];
		 if (file_exists($dstfile))
		 {
		     if ($this->set['isDel']) 
		     {
		         @unlink($dstfile);
		     }else{
		         $this->errorNo = 4;
		         return false;
		     }
		 }

         if(@copy($tmpFile,$dstfile))
		 {
		     @chmod($dstfile,0777);
		 }else{
		     $this->errorNo = 5;
		     return false;
		 }
         
         if($this->set['isThumb'])
         {
             $this->_makethumb();
         }
		 return true;
	 }
	 
	 /**
	  * 得到上传后的文件名
	  * @param bool $path: true:包括全路径 false:不带路径
	  * @return string
	  */
	 public function getUploadedFile($path=false)
	 {
	     return $this->errorNo>-1 ? '' : ($path ? $this->set['savePath'].$this->set['fileName'] : $this->set['fileName']); 
	 }
	 
	 /**
	  * 得到所有缩略图的文件名
	  * @return array
	  */
     public function getThumbFile()
     {
         return $this->thumbFiles;
     }
         

	 //////////////////////////////////////////////////////////////////
     //                                                              //  
	 //         以下为私有方法                                        //
     //                                                              //
     //////////////////////////////////////////////////////////////////
	 
	 /**
	  * 生成文件名
	  */
	 private function _makeFileName($dstFileName)
	 {
	     if ($dstFileName == '')
	     {
	         $this->set['fileName'] = $this->_randFileName().$this->_fileExt($_FILES[$this->inputName]['name']);
	     }else{
	         if (!strpos($dstFileName,'.')) $this->set['fileName'] = $dstFileName.$this->_fileExt($_FILES[$this->inputName]['name']);
	         else $this->set['fileName'] = $dstFileName;
	     }
	 }

	 /**
	  * 得到随机唯一的文件名
	  */
     private function _randFileName()
     {  //date('YmdHis')
    	srand((double)microtime()*1000000);
    	return md5(uniqid(time().rand()));
     }
     
	 /**
	  * 得到小写的文件扩展名,如".php"
	  *
	  * @param string $fileName:文件名
	  * @return string
	  */
	 private function _fileExt($fileName)
	 {
		 return strtolower(strrchr($fileName,"."));
	 }

     /**
      * 生成缩略图
      *
      */
     private function _makethumb()
     {
         $img = $this->getUploadedFile();
         $srcfile = $this->set['savePath'].$img;
         foreach ($this->set['thumb'] as $row)
         {
             @list($thumbWidth, $thumbHeight) = $row;
             $basefile = $thumbWidth.'_'.$thumbHeight.'_'.$img.'_thumb.jpg';
             $dstfile = $this->set['savePath'].$basefile;
             if (Image::thumbImg($srcfile,$dstfile,$thumbWidth,$thumbHeight)) $this->thumbFiles[] = $basefile; 
         }
     }
     
 }//End Class
?>