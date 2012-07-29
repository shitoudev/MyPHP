<?php
/**
 * filename: Page.php
 * 2.0增加功能：支持自定义风格，自定义样式，同时支持PHP4和PHP5,
 * example:
 * 可以自定义多模式分页模式：
   require_once('Page.php');
   $page = new page(array('total'=>1000, 'perpage'=>20));
   echo 'mode:1<br>'.$page->show();
   echo '<hr>mode:2<br>'.$page->show(2);
   开启AJAX：
   $ajaxpage=new page(array('total'=>1000,'perpage'=>20,'ajax'=>'ajax_page','page_name'=>'test'));
   echo 'mode:1<br>'.$ajaxpage->show();
   采用继承自定义分页显示模式：
 */
class Page {
	/**
  * config ,public
  */
	var $page_name = "p"; // page标签，用来控制url页。比如说xxx.php?PB_page=2中的PB_page
	var $next_page = '>'; // 下一页
	var $pre_page = '<'; // 上一页
	var $first_page = 'First'; // 首页
	var $last_page= 'Last'; // 尾页
	var $pre_bar = '<<'; // 上一分页条
	var $next_bar = '>>';// 下一分页条
	var $format_left = '[';
	var $format_right = ']';
	var $is_ajax = false; // 是否支持AJAX分页模式

	/**
  * private
  *
  */
	var $pagebarnum = 8; // 控制记录条的个数。
	var $totalpage = 0; // 总页数
	var $ajax_action_name = ''; // AJAX动作名
	var $nowindex = 1; // 当前页
	var $url = ""; // url地址头
	var $offset = 0;
	var $totaln = 0; // 总记录数
	var $perpage = 10;
	var $rewrite = 0; // 是否启用rewrite 1 (-1) 2 (/1)
	var $is_set   = 0; // 判断当前页是否有参数值
	/**
  * constructor构造函数
  *
  * @param array $array['total'],$array['perpage'],$array['nowindex'],$array['url'],$array['ajax']...
  */
	function page($array)
	{
		if(is_array($array)){
			if(!array_key_exists('total',$array))$this->error(__FUNCTION__,'need a param of total');
			$total=intval($array['total']);
			$perpage=(array_key_exists('perpage',$array))?intval($array['perpage']):10;
			$nowindex=(array_key_exists('nowindex',$array))?intval($array['nowindex']):'';
			$url=(array_key_exists('url',$array))?$array['url']:'';
			$totalpage = (array_key_exists('totalpage', $array)) ? intval($array['totalpage']) : '';
			$this->perpage = $perpage;
		}else{
			$total=$array;
			$perpage=10;
			$nowindex='';
			$url='';
			$totalpage = '';
			$this->perpage = 10;
		}
		if((!is_int($total))||($total<0))$this->error(__FUNCTION__,$total.' is not a positive integer!');
		if((!is_int($perpage))||($perpage<=0))$this->error(__FUNCTION__,$perpage.' is not a positive integer!');
		if(!empty($array['page_name']))$this->set('page_name',$array['page_name']);//设置pagename
		$this->_set_nowindex($nowindex);//设置当前页
		$this->_set_url($url);//设置链接地址
//		$this->totalpage=ceil($total/$perpage);
		$this->totalpage = empty($totalpage) ? ceil($total/$perpage) : $totalpage;
		$this->totaln = $total;
		$this->offset=($this->nowindex-1)*$this->perpage;
		if(!empty($array['ajax']))$this->open_ajax($array['ajax']);//打开AJAX模式
	}
	/**
  * 设定类中指定变量名的值，如果改变量不属于这个类，将throw一个exception
  *
  * @param string $var
  * @param string $value
  */
	function set($var,$value)
	{
		if(in_array($var,get_object_vars($this)))
		$this->$var=$value;
		else {
			$this->error(__FUNCTION__,$var." does not belong to PB_Page!");
		}

	}
	/**
  * 打开倒AJAX模式
  *
  * @param string $action 默认ajax触发的动作。
  */
	function open_ajax($action)
	{
		$this->is_ajax=true;
		$this->ajax_action_name=$action;
	}
	/**
  * 获取显示"下一页"的代码
  *
  * @param string $style
  * @return string
  */
	function next_page($style='')
	{
		$text = $this->nowindex<$this->totalpage ? $this->nowindex+1 : $this->totalpage;
		if($this->is_ajax){
			$ajaxAction = $this->nowindex==$this->totalpage ? ';' : $this->ajax_action_name.'(\''.$text.'\');';
			//return '<a class="'.$style.'" href="javascript:'.$this->ajax_action_name.'(\''.$text.'\')">'.$this->next_page.'</a>';
			return '<a class="'.$style.'" href="javascript:'.$ajaxAction.'">'.$this->next_page.'</a>';
		} else {
			if($this->nowindex<$this->totalpage){
				return $this->_get_link($this->_get_url($this->nowindex+1),$this->next_page,$style);
			}
			return '<a class="'.$style.'">'.$this->next_page.'</a>';
		}
	}

	/**
  * 获取显示“上一页”的代码
  *
  * @param string $style
  * @return string
  */
	function pre_page($style='')
	{
		$text = $this->nowindex>1 ? $this->nowindex-1 : 1;
		if($this->is_ajax){
			$ajaxAction = $this->nowindex==1 ? ';' : $this->ajax_action_name.'(\''.$text.'\');';
			//return '<a class="'.$style.'" href="javascript:'.$this->ajax_action_name.'(\''.$text.'\')">'.$this->pre_page.'</a>';
			return '<a class="'.$style.'" href="javascript:'.$ajaxAction.'">'.$this->pre_page.'</a>';
		} else {
			if($this->nowindex>1){
				return $this->_get_link($this->_get_url($this->nowindex-1),$this->pre_page,$style);
			}
			return '<a class="'.$style.'">'.$this->pre_page.'</a>';
		}
	}

	/**
  * 获取显示“首页”的代码
  *
  * @return string
  */
	function first_page($style='')
	{
		if($this->nowindex==1){
			return '<a class="'.$style.'">'.$this->first_page.'</a>';
		}
		return $this->_get_link($this->_get_url(1),$this->first_page,$style);
	}


	/**
  * 获取显示“尾页”的代码
  *
  * @return string
  */
	function last_page($style='')
	{
		if($this->nowindex==$this->totalpage){
			return '<a class="'.$style.'">'.$this->last_page.'</a>';
		}
		return $this->_get_link($this->_get_url($this->totalpage),$this->last_page,$style);
	}

	/**
	 * 按需显示第一页
	 */
	private function need_first($style, $nowindex_style=''){
		if($this->totalpage > $this->pagebarnum+1){
			if($this->nowindex>$this->pagebarnum-4){
				$return.=$this->_get_text($this->_get_link($this->_get_url(1),1,$style));
				$return.=$this->_get_text(' <a class="'.$nowindex_style.'">...</a> ');
			}
		}
		return $return;
	}
	/**
	 * 	按需显示最后一页
	 */
	private function need_last($style, $nowindex_style=''){
		if($this->totalpage > $this->pagebarnum+1){
			if(($this->totalpage-$this->nowindex)>$this->pagebarnum-4){
				$return.=$this->_get_text('<a class="'.$nowindex_style.'">...</a> ');
				$return.=$this->_get_text($this->_get_link($this->_get_url($this->totalpage),$this->totalpage,$style));
			}
		}
		return $return;
	}

	function nowbar($style='',$nowindex_style='')
	{
		$plus=ceil($this->pagebarnum/2);
		if($this->pagebarnum-$plus+$this->nowindex>$this->totalpage)$plus=($this->pagebarnum-$this->totalpage+$this->nowindex);
		$begin=$this->nowindex-$plus+1;
		$begin=($begin>=1)?$begin:1;
		$return='';
		for($i=$begin;$i<$begin+$this->pagebarnum;$i++)
		{
			if($i<=$this->totalpage){
				if($i!=$this->nowindex)
				$return.=$this->_get_text($this->_get_link($this->_get_url($i),$i,$style));
				else
				$return.=$this->_get_text('<a class="'.$nowindex_style.'">'.$i.'</a>');
			}else{
				break;
			}
			$return.="\n";
		}
		unset($begin);
		return $return;
	}

	/**
  * 获取显示跳转按钮的代码
  *
  * @return string
  */
	function select()
	{
		$return='<select name="PB_Page_Select" onchange="window.location=\''.$this->url.'\'+this.options[this.selectedIndex].value; if (0) this.selectedIndex=0;">';
		for($i=1;$i<=$this->totalpage;$i++) {
			if($i==$this->nowindex) {
				$return.='<option value="'.$i.'" selected>'.$i.'</option>';
			}else {
				$return.='<option value="'.$i.'">'.$i.'</option>';
			}
		}
		unset($i);
		$return.='</select>';
		return $return;
	}

	/**
  * 获取mysql 语句中limit需要的值
  *
  * @return string
  */
	function offset()
	{
		return $this->offset;
	}

	/**
  * 控制分页显示风格（你可以增加相应的风格）
  *
  * @param int $mode
  * @return string
  */
	function show($mode=10)
	{
		switch ($mode)
		{

			/**
				 +-------------------------------------------------------
				 * Mode1 CSS 样式说明
				 +-------------------------------------------------------
				 * a_num      非当前页样式
				 * a_curpage  当前页样式
				 +-------------------------------------------------------
				 * 其它的样式随主css的样式
				 * 下划线之类的样式均在主css中设置
				 +-------------------------------------------------------
				 */

			case '10':

				$this->next_page  = '下页';
				$this->pre_page   = '上页';
				$this->first_page = '首页';
				$this->last_page  = '末页';
				$this->format_left  = '[';
				$this->format_right = ']';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page();

				break;

			case '11':
				$this->next_page  = '下頁';
				$this->pre_page   = '上頁';
				$this->first_page = '首頁';
				$this->last_page  = '末頁';
				$this->format_left  = '[';
				$this->format_right = ']';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page();

				break;

			case '12':
				$this->next_page  = 'Next';
				$this->pre_page   = 'Previous';
				$this->first_page = 'First';
				$this->last_page  = 'Last';
				$this->format_left  = '[';
				$this->format_right = ']';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page();

				break;


			case '20':

				$this->next_page  = '下页';
				$this->pre_page   = '上页';
				$this->first_page = '首页';
				$this->last_page  = '末页';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('p_num_3', 'p_curpage_3').' '.$this->next_page().' '.$this->last_page().' 共'.$this->totalpage.'页 / '.$this->totaln.'条记录';
				break;

			case '10':
				$this->next_page  = '下页';
				$this->pre_page   = '上页';
				$this->first_page = '首页';
				$this->last_page  = '末页';
				$this->format_left  = '';
				$this->format_right = '';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('p_num_1', 'p_curpage_1').' '.$this->next_page().' '.$this->last_page().' 第'.$this->select().'页';
				break;

			case '2':

				/**
				 +-------------------------------------------------------------------
				 * Mode2样式说明
				 +-------------------------------------------------------------------
				 * p_bar      整个DIV总的样式
				 * p_total    一共有多少条记录样式
				 * p_pages    当前页/总页 样式
				 * p_redirect 首页、上页样式
				 * p_num      非当前页样式
				 * p_curpage  当前页样式
				 * p_redirect 下页、末页样式
				 * p_input    文本跳转框样式
				 +-------------------------------------------------------------------
				 */

				$this->format_left  = '';
				$this->format_right = '';
				$this->next_page    = '&#8250;&#8250;';
				$this->pre_page     = '&#8249;&#8249;';
				$this->first_page   = '|&#8249;';
				$this->last_page    = '&#8250;|';

				$this->pagestr  = '<DIV class=p_bar><A class=p_total>&nbsp;'.$this->totaln.'&nbsp;</A>';
				$this->pagestr .= '<A class=p_pages>&nbsp;'.$this->nowindex.'/'.$this->totalpage.'&nbsp;</A>';
				$this->pagestr .= $this->first_page('p_redirect');
				$this->pagestr .= $this->pre_page('p_redirect');
				$this->pagestr .= $this->nowbar('p_num', 'p_curpage');
				$this->pagestr .= $this->next_page('p_redirect');
				$this->pagestr .= $this->last_page('p_redirect');
				if (empty($_GET['p'])) {
					$url = explode('.', $this->url);
					$url[0] .= '_p_';
					$url[1]  = '.html';
				} else {
					$url = explode('_'.$_GET['p'], $this->url);
					$url[0] .= '_';
				}
				$this->pagestr .= '<A class=p_pages style="PADDING-RIGHT: 0px; PADDING-LEFT: 0px; PADDING-BOTTOM: 0px; PADDING-TOP: 0px"><INPUT class=p_input onkeydown="if(event.keyCode==13) {window.location=\''.$url[0].'\'+this.value+\''.$url[1].'\'; return false;}" name=custompage></A></DIV>';
				return $this->pagestr;
				break;

				/**
				 +-------------------------------------------------------
				 * Mode3 CSS 样式说明
				 +-------------------------------------------------------
				 * a_num      非当前页样式
				 * a_curpage  当前页样式
				 +-------------------------------------------------------
				 * 其它的样式随主css的样式
				 * 下划线之类的样式均在主css中设置
				 +-------------------------------------------------------
				 */

			case '30':

				$this->next_page  = '下页';
				$this->pre_page   = '上页';
				$this->first_page = '首页';
				$this->last_page  = '末页';
				$this->format_left  = '[';
				$this->format_right = ']';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page().' 第'.$this->select().'页';

				break;

			case '31':
				$this->next_page  = '下頁';
				$this->pre_page   = '上頁';
				$this->first_page = '首頁';
				$this->last_page  = '末頁';
				$this->format_left  = '[';
				$this->format_right = ']';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page().' 第'.$this->select().'頁';

				break;

			case '32':
				$this->next_page  = 'Next';
				$this->pre_page   = 'Previous';
				$this->first_page = 'First';
				$this->last_page  = 'Last';
				$this->format_left  = '[';
				$this->format_right = ']';
				return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page().' Page:'.$this->select();

				break;


				/**
				 +-------------------------------------------------------
				 * Mode3 CSS 样式说明
				 +-------------------------------------------------------
				 * 其它的样式跟随主css的样式
				 +-------------------------------------------------------
				 */

			case '40':

				$this->next_page  = '下页';
				$this->pre_page   = '上页';
				$this->first_page = '首页';
				$this->last_page  = '末页';
				return $this->first_page().' '.$this->pre_page().' '.$this->next_page().' '.$this->last_page().' 页次：<strong>'.$this->nowindex.'</strong>/<strong>'.$this->totalpage.' </strong><strong>'.$this->perpage.'</strong>条记录/页 转到 '.$this->select().' 页 共 <strong>'.$this->totaln.'</strong> 条记录';

				break;

			case '41':

				$this->next_page  = '下頁';
				$this->pre_page   = '上頁';
				$this->first_page = '首頁';
				$this->last_page  = '末頁';
				return $this->first_page().' '.$this->pre_page().' '.$this->next_page().' '.$this->last_page().' 页次：<strong>'.$this->nowindex.'</strong>/<strong>'.$this->totalpage.' </strong><strong>'.$this->perpage.'</strong>條記錄/頁 轉到 '.$this->select().' 頁 共 <strong>'.$this->totaln.'</strong> 條記錄';

				break;

			case '42':

				$this->next_page  = 'Next';
				$this->pre_page   = 'Previous';
				$this->first_page = 'First';
				$this->last_page  = 'Last';
				return $this->first_page().' '.$this->pre_page().' '.$this->next_page().' '.$this->last_page().' Page: <strong>'.$this->nowindex.'</strong>/<strong>'.$this->totalpage.' </strong> P <strong>'.$this->perpage.'</strong> Records/P Turnto '.$this->select().' P Total <strong>'.$this->totaln.'</strong> Records';

				break;


			case '5':

				$this->next_page  = '<img src="images/pages/btn_next02_off.gif" border="0">';
				$this->pre_page   = '<img src="images/pages/btn_prev03_off.gif" border="0">';
				$this->first_page = '<img src="images/pages/btn_prev02_off.gif" border="0">';
				$this->last_page  = '<img src="images/pages/btn_next03_off.gif" border="0">';
				$this->format_left  = '';
				$this->format_right = '';
				return $this->first_page().' <img src="images/pages/bu_01.gif" border="0"> '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' <img src="images/pages/bu_01.gif" border="0"> '.$this->last_page();

				break;

			case '6':

				$this->format_left  = '';
				$this->format_right = '';
				return $this->nowbar('a_num', 'a_curpage');

				break;



			case '70':

				$this->next_page  = '下一页';
				$this->pre_page   = '上一页';
				$this->first_page = '第一页';
				$this->last_page  = '最后一页';
				$this->format_left  = '';
				$this->format_right = '';
				return '共'.$this->totalpage.'页 '.$this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page();

				break;

			case '71':
				$this->next_page  = '下一頁';
				$this->pre_page   = '上一頁';
				$this->first_page = '第一頁';
				$this->last_page  = '最後一頁';
				$this->format_left  = '';
				$this->format_right = '';
				return '共'.$this->totalpage.'页 '.$this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page();

				break;

			case '72':
				$this->next_page  = 'Next';
				$this->pre_page   = 'Previous';
				$this->first_page = 'First';
				$this->last_page  = 'Last';
				$this->format_left  = '';
				$this->format_right = '';
				return 'Total '.$this->totalpage.' P '.$this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page();

				break;



			case '8':

				/**
				 +-------------------------------------------------------------------
				 * Mode2样式说明
				 +-------------------------------------------------------------------
				 * p_bar      整个DIV总的样式
				 * p_total    一共有多少条记录样式
				 * p_pages    当前页/总页 样式
				 * p_redirect 首页、上页样式
				 * p_num      非当前页样式
				 * p_curpage  当前页样式
				 * p_redirect 下页、末页样式
				 * p_input    文本跳转框样式
				 +-------------------------------------------------------------------
				 */

				$this->format_left  = '';
				$this->format_right = '';
				$this->next_page    = '&#8250;&#8250;';
				$this->pre_page     = '&#8249;&#8249;';
				$this->first_page   = '|&#8249;';
				$this->last_page    = '&#8250;|';

				$this->pagestr = '<div class="pagination">&nbsp;&nbsp;</A>';
				$this->pagestr .= '<A>&nbsp;'.$this->nowindex.'/'.$this->totalpage.'&nbsp;</A>';
				$this->pagestr .= $this->first_page('');
				$this->pagestr .= $this->pre_page('');
				$this->pagestr .= $this->nowbar('', 'noncepage');
				$this->pagestr .= $this->next_page('');
				$this->pagestr .= $this->last_page('');
				$this->pagestr .= '</div>';
				return $this->pagestr;

				break;
			case '81':
				$this->next_page  = '下一页';
				$this->pre_page   = '上一页';
				//$this->first_page = '第一页';
				//$this->last_page  = '最后一页';
				$this->format_left  = '';
				$this->format_right = '';
				return $this->pre_page().' '.$this->need_first('p_num', 'none').' '.$this->nowbar('p_num', 'none').' '.$this->need_last('p_num', 'none').' '.$this->next_page();

				break;

			case '22':
				$this->next_page  = '下一页';
				$this->pre_page   = '上一页';
				//$this->first_page = '第一页';
				//$this->last_page  = '最后一页';
				//$this->format_left  = '';
				//$this->format_right = '';
				//return $this->first_page().' '.$this->pre_page().' '.$this->nowbar('a_num', 'a_curpage').' '.$this->next_page().' '.$this->last_page();
				return $this->pre_page().' '.$this->next_page();

				break;

		}
	}
	/*----------------private function (私有方法)-----------------------------------------------------------*/
	/**
  * 设置url头地址
  * @param: String $url
  * @return boolean
  */
	function _set_url($url="")
	{
		if(!empty($url)){
			// 手动设置
			$this->url=$url.((stristr($url,'?'))?'&':'?').$this->page_name."=";
		}else{
			// 静态rewrite
			$this->url=$_SERVER['REQUEST_URI'];
		}//end if
	}



	/**
  * 设置当前页面
  *
  */
	function _set_nowindex($nowindex)
	{
		if(empty($nowindex)){
			//系统获取

			if(isset($_GET[$this->page_name])){
				$this->nowindex=intval($_GET[$this->page_name]);
			}
		}else{
			//手动设置
			$this->nowindex=intval($nowindex);
		}
	}

	/**
  * 为指定的页面返回地址值
  *
  * @param int $pageno
  * @return string $url
  */
	function _get_url($pageno=1) {
		if (empty($_GET['p'])) {
			if ($this->rewrite==1) { // -1
				if ($this->is_set==0) {
					$url = str_replace('.html', '-'.$pageno.'.html', $this->url);
				} else {
					$url = str_replace('-'.$this->nowindex.'.html', '-'.$pageno.'.html', $this->url);
				}
			} elseif ($this->rewrite==2) { // /1
				$url = $this->url.'/'.$pageno;
			} else {
				$url = str_replace('.html', '_'.$this->page_name.'_'.$pageno.'.html', $this->url);
			}

		} else {
			if ($this->rewrite==2) { //  /1
				$exurl 	= explode('/', $this->url);
				$count	= count($exurl);
				$url   	= str_replace('/'.$exurl[$count-2].'/'.$exurl[$count-1], '/'.$exurl[$count-2].'/'.$pageno, $this->url);
				//$url = str_replace('/'.$this->nowindex, '/'.$pageno, $this->url);
			} else {
				$url = str_replace('p_'.$_GET['p'], 'p_'.$pageno, $this->url);
			}
		}
		return $url;
	}

	/**
  * 获取分页显示文字，比如说默认情况下_get_text('<a href="">1</a>')将返回[<a href="">1</a>]
  *
  * @param String $str
  * @return string $url
  */
	function _get_text($str)
	{
		return $this->format_left.$str.$this->format_right;
	}

	/**
   * 获取链接地址
 */
	function _get_link($url,$text,$style=''){
		$style=(empty($style))?'':'class="'.$style.'"';
		if($this->is_ajax){
			//如果是使用AJAX模式
			//return '<a '.$style.' href="javascript:'.$this->ajax_action_name.'(\''.$url.'\')">'.$text.'</a>';
			return '<a '.$style.' href="javascript:'.$this->ajax_action_name.'(\''.$text.'\')">'.$text.'</a>';
		}else{
			return '<a '.$style.' href="'.$url.'">'.$text.'</a>';
		}
	}
	/**
   * 出错处理方式
 */
	function error($function,$errormsg)
	{
		die('Error in file <b>'.__FILE__.'</b> ,Function <b>'.$function.'()</b> :'.$errormsg);
	}
}
?>