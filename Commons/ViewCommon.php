<?php
/**
 * 视图公共方法
 *
 * 前端视图公共页头、页脚调用
 *
 * 2012-08-18 1.0 lizi 创建
 *
 * @author  lizi
 * @version 1.0
 */
class ViewCommon extends Common
{
	/**
	 * 输出header html
	 *
	 * 主要用于前端公共页头调用
	 *
	 * @param  mixed $param 参数
	 * @return string html
	 */
	public function header($param = '')
	{
		//  参数设置
		$data['menu'] = $param;
		$data['user'] = get_user_info();
		$data['uid']  = $_SESSION['userid'];
		$cookie       = $this->load->cookie();

		// 加载公共类库
		$memcache    = $this->load->memcache();
		$cartCommon  = $this->load->common('ShopCart');

		// 加载视图
		unset($pspData, $categoryData);
		$this->load->view('common/header', $data);
	}

	/**
	 * 输出footer html
	 *
	 * 主要用于前端公共页脚调用
	 *
	 * @return string html
	 */
	public function footer()
	{
		// 加载视图
		$this->load->view('common/footer', $data);
	}

}

/* End */