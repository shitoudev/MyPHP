<?php
/**
 * 分页工具
 *
 * $page = $this->load->tool('Page');
 * dump($page->show(100, 20));
 *
 * 2012-08-18 1.0 lizi 创建
 *
 * @author  lizi
 * @version 1.0
 */
class PageTool
{
	public $listRows      = 10;       // 默认列表每页显示行数
	public $firstRow      = 1;        // 起始行数
	protected $totalPages = 0;        // 分页总页面数
	protected $totalRows  = 0;        // 总行数
	protected $nowPage    = 1;        // 当前页数
	protected $varPage    = 'p';      // 默认分页变量名
	protected $isRewrite  = FALSE;   // rewirte 1.html/p=1
	protected $config     = array(); // 分页显示风格定制
	protected $theme      = '';       // 分页显示风格定制模板

	/**
	 * 构造函数,自动连接数据库
	 */
	// public function __construct() {}

	/**
	 * 分页显示
	 *
	 * @param int     $totalRows 总的记录数
	 * @param array   $listRows  每页显示记录数
	 * @param string  $url       指定跳转url
	 * @param string  $style     分页风格
	 * @return string html
	 */
	public function show($totalRows, $listRows = 10, $style = 1, $url = '')
	{
		// 参数设置
		if(0 == $totalRows) return '';
		$this->nowPage    = !empty(Myphp::$data['get'][$this->varPage]) ? intval(Myphp::$data['get'][$this->varPage]) : 1;
		$this->firstRow   = $listRows * ($this->nowPage - 1); // 超始行
		$this->totalRows  = $totalRows; // 总行数
		$this->listRows   = $listRows;  // 每页显示行数
		$this->totalPages = ceil($totalRows / $listRows);     //总页数
		$this->_setStyle($style);

		// 上下翻页字符串
		$prev = $this->nowPage - 1;
		$next = $this->nowPage + 1;
		$prevPage = ($prev > 0) ? '<a href="'.$this->_getUrl($prev, $url).'">'.$this->config['prev'].'</a>' : '';
		$nextPage = ($next <= $this->totalPages) ? '<a href='.$this->_getUrl($next, $url).'>'.$this->config['next'].'</a>' : '';

		// 首页尾页
		$firstPage = ($prev > 0) ? '<a href='.$this->_getUrl(1, $url).'>'.$this->config['first'].'</a>' : '';
		$lastPage  = ($next <= $this->totalPages) ? '<a href='.$this->_getUrl($this->totalPages, $url).'>'.$this->config['last'].'</a>' : '';

		// 1 2 3 4 5
		// 暂时未有需要有待补充

		// 输出
		$pageStr = str_replace(array('%first%','%prev%','%next%','%last%'), array($firstPage, $prevPage, $nextPage, $lastPage), $this->theme);
		return $pageStr;
	}

	/**
	 * 分页风格
	 *
	 * @param int $style 风格
	 */
	private function _setStyle($style = 1) {
		switch ($style) {
			case 1:
				$firstRow = $this->firstRow + 1;               // 第1条记录 
				$lastRow  = $this->firstRow + $this->listRows; // 最后一条记录 
				$this->isRewrite = FALSE;
				$this->config = array('prev'=>'< 上一页', 'next'=>'下一页 >', 'first'=>'<< 首页', 'last'=>'尾页 >>');
				$this->theme  = $firstRow.'-'.$lastRow.'条 共'.$this->totalRows.'条 %first% %prev% %next% %last%';
				break;
			case 2:
				$firstRow = $this->firstRow + 1;               // 第1条记录 
				$lastRow  = $this->firstRow + $this->listRows; // 最后一条记录 
				$this->isRewrite = TRUE;
				$this->config = array('prev'=>'< 上一页', 'next'=>'下一页 >', 'first'=>'<< 首页', 'last'=>'尾页 >>');
				$this->theme  = $firstRow.'-'.$lastRow.'条 共'.$this->totalRows.'条 %first% %prev% %next% %last%';
				break;
			default:
				$this->config = array();
				$this->theme  = '';
		}
	}

	/**
	 * 获取url
	 *
	 * @param int $page    需要替换的页数
	 * @return string url
	 */
	private function _getUrl($page, $url = '')
	{
		if (empty($url)) {
			if ($this->isRewrite)
				$url = str_replace($this->nowPage.".html", $page.".html", $_SERVER['REQUEST_URI']);
			else
				$url = str_replace($this->varPage."=".$this->nowPage, $this->varPage."=".$page, $_SERVER['REQUEST_URI']);
		} else {
			if ($this->isRewrite) {
				$url = $url.$page.'.html';
			}else {
				$url = '/'.$page;
			}
		}
		return $url;
	}
}

/* End */