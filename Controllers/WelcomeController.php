<?php
/**
 * 欢迎页控制器
 *
 * 欢迎页主要有三部分组成 用户信息 订单信息 营销推广信息
 *
 * 2012-xx-xx 1.0 xxx 创建
 *
 * @author  xxx
 * @version 1.0
 */
class WelcomeController extends Controller
{

	/**
	 * 欢迎页
	 *
	 * 2012-xx-xx 1.0 xxx 创建
	 *
	 * @author  xxx
	 * @version 1.0
	 */
	public function index()
	{
		// 加载示例
		// $testCommon = $this->load->common('test');
		$userModel  = $this->load->model('user');
		$user = $userModel->getID(11081);
		dump($user['love_team']);

		$teamModel  = $this->load->model('team');
		$team = $teamModel->getID(9);
		dump($team);

		$mysqldb = $teamModel->query("select * from mysql.db where 1=1 limit 2");
		$array = mysql_fetch_assoc($mysqldb);
		dump($array);

		// 加载视图
		// $this->load->view('index', $data);
		// phpinfo();
		$get = $this->getGet();
		dump($get);
	}

	public function test()
	{
		// echo __FUNCTION__;
		// 获取远程网页数据
		$unsetText = array("新浪图文", "新浪视频", "比分直播", "图文直播");
		$htmlStr = file_get_contents("http://match.sports.sina.com.cn/tvguide/program/top/");
		$htmlStr .= file_get_contents("http://match.sports.sina.com.cn/tvguide/program/top/?date=".date("Y-m-d", strtotime("+1 day")));
		$htmlStr .= file_get_contents("http://match.sports.sina.com.cn/tvguide/program/top/?date=".date("Y-m-d", strtotime("+2 day")));
		// preg_match_all('/<div class="main">(.*?)<\/body>/is', $htmlStr, $htmlMatches);
		preg_match_all('/<ul>(.*?)<\/ul>/is', $htmlStr, $htmlMatches);
		// dump($htmlMatches);
		if (is_array($htmlMatches[1])) {
			foreach ($htmlMatches[1] as $key => $val) {
				$html .= $val;
			}
		}
		
		$strPlace = '斯旺西(.*?)曼联';
		preg_match('/<li data-mtype="[a-z\d]+">.*?<div class="mth_title[\sa-z\_]*?">.*?<br \/>'.$strPlace.'<\/div>(.*?)<\/li>/is', $html, $matches);
		// preg_match('/<li data-mtype="[a-z\d]+">.*?[^<li>|^<\/ul>]<div class="mth_title[\sa-z\_]*?">.*?<br \/>'.$strPlace.'<\/div>(.*?[^<li>|^<\/ul>])<\/li>/is', $html, $matches);


		dump($matches[2]);
	}

	public function video()
	{
		$data = $this->getTitlePhoto('http://v.qq.com/cover/n/nqz14vkw0rxceqp.html?vid=w0011hwmxqb');
	    $array = json_decode($data, true);
	    dump($array);

	}


	// 获取视频信息
	public function getTitlePhoto($url){
		// 参数
		// $get = $this->request->get();
		$get = $this->getGet();
		// $url = urldecode($get['url']);
// curl GET
function curl_get($url, $gzip=false){
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	if($gzip) curl_setopt($curl, CURLOPT_ENCODING, "gzip");
	$content = curl_exec($curl);
	curl_close($curl);
	return $content;
}
		if (!empty($url)) {
			$html = curl_get($url);
			$time = time();
			// 匹配视频ID
			if (stripos($url, 's.sohu.com')) {
				// 搜狐体育视频
				// 标题
				preg_match('/<meta name="description" content="(.*?)">/is', $html, $titles);
				$title = iconv("GBK", "UTF-8", $titles[1]);
				// 图片地址
				preg_match('/cover[\s]*=[\s]*[\'|\"](.*?)[\'|\"]/is', $html, $pics);
				$pic = $pics[1];
				// ios 视频地址
				preg_match("/vid[\s]*=[\s]*[\'|\"](\d+)[\'|\"]/is", $html, $matches);
				$videoId = $matches[1];
				$videoIos = "http://hot.vrs.sohu.com/ipad".$videoId.".m3u8";
			}else if (stripos($url, 'youku.com')) {
				// 优酷视频
				// 标题
				preg_match('/<span.*?id="subtitle">(.*?)<\/span>/is', $html, $titles);
				$title = $titles[1];
				// 图片地址
				$sinaHtml = curl_get("http://service.weibo.com/share/share.php?url=".$url);
				preg_match('/scope.picLst[\s]*=[\s]*\[(.*?)\];/is', $sinaHtml, $pics);
				$picJson = '['.$pics[1].']';
				$picArr = json_decode($picJson);
				$pic = $picArr[1];
				// ios 视频地址
				preg_match("/videoId[\s]*=[\s]*[\'|\"](\d+)[\'|\"]/is", $html, $matches);
				$videoId = $matches[1];
				$videoIos = "http://v.youku.com/player/getM3U8/vid/".$videoId."/type/mp4/ts/".$time."/v.m3u8";
			}else if (stripos($url, 'video.sina.com.cn/p/sports')){
				// 新浪体育视频
				preg_match('/video[\s]*=[\s]*{(.*?)}/is', $html, $matches);
				$string = $matches[1];
				dump($matches);
				if (!empty($string)) {
					// 标题
					// preg_match('/title[\s]*:[\s]*[\'|\"]*(.*?)[\'|\"]*,/', $string, $titles);
					preg_match('/<div class="title" id="videoTitle">(.*?)<\/div>/is', $html, $titles);
					$title = $titles[1];
					// 图片地址
					preg_match('/pic[\s]*:[\s]*[\'|\"]*(.*?)[\'|\"]*,/', $string, $pics);
					$pic = $pics[1];
					// ios 视频地址
					preg_match('/ipad_vid[\s]*:[\s]*[\'|\"]*(\d+)[\'|\"]*,/', $string, $videos);
					$videoIos = "http://v.iask.com/v_play_ipad.php?vid=".$videos[1];
				}
			}else if (stripos($url, 'video.sina.com.cn')){
				// 新浪体育视频
				preg_match('/video[\s]*:[\s]*{(.*?)}/is', $html, $matches);
				$string = $matches[1];
				if (!empty($string)) {
					// 标题
					preg_match('/title[\s]*:[\s]*[\'|\"]*(.*?)[\'|\"]*,/', $string, $titles);
					$title = $titles[1];
					// 图片地址
					preg_match('/pic[\s]*:[\s]*[\'|\"]*(.*?)[\'|\"]*,/', $string, $pics);
					$pic = $pics[1];
					// ios 视频地址
					preg_match('/ipad_vid[\s]*:[\s]*[\'|\"]*(\d+)[\'|\"]*,/', $string, $videos);
					$videoIos = "http://v.iask.com/v_play_ipad.php?vid=".$videos[1];
				}
			}else if (stripos($url, 'sports.sina.com.cn/uclvideo')){
				// 新浪欧冠视频
				preg_match('/arguments[\s]*:[\s]*{(.*?)}/is', $html, $matches);
				$string = $matches[1];
				if (!empty($string)) {
					// 标题
					preg_match('/<div id="videotitle">(.*?)<\/div>/is', $html, $titles);
					$title = strip_tags(iconv("GBK", "UTF-8", $titles[1]));
					$title = str_replace('发表评论', '', $title);
					// echo $title."\n";
					// 图片地址
					preg_match('/pic[\s]*:[\s]*[\'|\"]*(.*?)[\'|\"]*,/', $string, $pics);
					$pic = $pics[1];
					// echo $pic;
				}
			}else if (stripos($url, 'v.qq.com/cover')){
				// qq视频
				$path = parse_url($url);
				preg_match('/vid=([a-z\d]+)/is', $path['query'], $matches);
				$vid = $matches[1];
				if (!empty($vid)) {
					/*preg_match('/<li id="li_'.$vid.'".*?>(.*?)<\/li>/is', $html, $matches);*/
					preg_match('/<li id="li_'.$vid.'"[^>]*?>(.*?)<\/li>/is', $html, $matches);
					if (!empty($matches[1])) {
						// 标题
						preg_match('/<a[^>]*?>(.*?)<\/a>/is', $matches[1], $titles);
						dump($titles);
						$title = trim($titles[1]);
						// 图片地址
						preg_match('/<img[^>]*?src="([^\"]*?)"[^>]*?>/is', $matches[1], $pics);
						$pic = $pics[1];
						dump($pics);

					}
					// dump($matches);
				}
			}
			// if (!empty($title) && !empty($videoIos)) {//&& !empty($pic)
				return json_encode(array("code"=>200, "title"=>$title, "video"=>$videoIos, "pic"=>$pic));
			// }else echo json_encode(array("code"=>201));

		}else return json_encode(array("code"=>201));
	}


	public function preschool(){
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310104"; // 徐汇
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310101"; // 黄浦
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310105"; // 长宁
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310106"; // 静安
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310107"; // 普陀
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310108"; // 闸北
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310109"; // 虹口
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310110"; // 杨浦
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310112"; // 闵行
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310113"; // 宝山
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310114"; // 嘉定
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310115"; // 浦东新
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310116"; // 金山
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310117"; // 松江
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310118"; // 青浦
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310226"; // 奉贤
		// $url = "http://www.age06.com/Age06.Web/Search/index.aspx?DistrictID=310230"; // 崇明
		$url = "";
		$html = file_get_contents($url);
		preg_match("/<table id=\"tblGarden\".*?>(.*?)<\/table>/is", $html, $matches);
		$htmlArray = $this->get_td_array($matches[0]);
		unset($htmlArray[0]);
		unset($htmlArray[1]);
		// var_dump($htmlArray);

		$preschoolModel  = $this->load->model('preschool');
		$province = "上海";
		if (is_array($htmlArray)) {
			foreach ($htmlArray as $key => $val) {
				$levelString = iconv("GBK", "UTF-8", $val[0]);
				$levelString = trim(str_replace(array("\r\n", "&nbsp;"), array("", ""), $levelString));
				if (empty($levelString)) {
					$levelString = $newHtmlArray[$key-1][0];
				}

				$level = $this->preschoolLevel($levelString);

				$property = iconv("GBK", "UTF-8", $val[1]);
				$property = trim(str_replace(array("\r\n", "&nbsp;"), array("", ""), $property));
				$property = $this->preschoolProperty($property);
				if (empty($property)) {
					$property = $newHtmlArray[$key-1][1];
				}

				$city = iconv("GBK", "UTF-8", $val[2]);
				$city = trim(str_replace(array("\r\n", "&nbsp;"), array("", ""), $city))."区";
				if ($city=="区") {
					$city = $newHtmlArray[$key-1][2];
				}
				if ($city=="浦东区") {
					$city = "浦东新区";
				}

				$name = strip_tags(iconv("GBK", "UTF-8", $val[3]));
				$name = trim(str_replace(array("\r\n", "&nbsp;"), array("", ""), $name));

				$address = strip_tags(iconv("GBK", "UTF-8", $val[4]));
				$address = trim(str_replace(array("\r\n", "&nbsp;", "上海市".$city, $city, "上海市"), array("", "", "", "", ""), $address));

				$telPhone = strip_tags(iconv("GBK", "UTF-8", $val[5]));
				$telPhone = trim(str_replace(array("\r\n", "&nbsp;"), array("", ""), $telPhone));

				$data = array("province"=>$province, "level"=>$level, "level_string"=>$levelString, "property"=>$property, "city"=>$city, "name"=>$name, "address"=>$address,
								"tel_phone"=>$telPhone);
				// print_r($data);
				if (empty($address)) {
					continue;
				}

				$newHtmlArray[$key] = array($levelString, $property, $city, $name, $address, $telPhone);

				// print_r($data);
				// $preschoolModel->add($data);
			}
			// print_r($newHtmlArray);
			// print_r($htmlArray);
		}
	}

	private function preschoolProperty($property)
	{
		// 1:公办 2:民办 3:集体 4:事业单位 5:地方企业
		switch ($property) {
			case '公办':
				return 1;
				break;
			case '民办':
				return 2;
				break;
			case '集体':
				return 3;
				break;
			case '事业单位':
				return 4;
				break;
			case '地方企业':
				return 5;
				break;
			// case '中外合作':
			// 	return 6;
			// 	break;
			default:
				# code...
				break;
		}
	}

	private function preschoolLevel($level)
	{
		// 1:示范园 2:一级 3:二级 4:三级 5:四级 6:一般
		switch ($level) {
			case '示范园':
				return 1;
				break;
			case '一级':
				return 2;
				break;
			case '二级':
				return 3;
				break;
			case '三级':
				return 4;
				break;
			case '四级':
				return 5;
				break;
			case '一般':
				return 6;
				break;
			default:
				# code...
				break;
		}
	}

	/**
	 +-------------------------------------------------------
	 * 表格转换为array
	 +-------------------------------------------------------
	 * 2010-02-02 v1.0 by lizi
	 +-------------------------------------------------------
	 */
	function get_td_array($table) {
		$table = preg_replace("'<table[^>]*?>'si","{tr}",$table);
		$table = preg_replace("'<tr[^>]*?>'si","",$table);
		$table = preg_replace("'<th[^>]*?>'si","",$table);
		$table = preg_replace("'<td[^>]*?>'si","",$table);
		$table = str_replace("</tr>","{tr}",$table);
		$table = str_replace("</th>","{td}",$table);
		$table = str_replace("</td>","{td}",$table);
		//去掉 HTML 标记
		/*$table = preg_replace("'<[/!]*?[^<>]*?>'si","",$table);*/
		//去掉空白字符
		//$table = preg_replace("'([rn])[s] '","",$table);
		//$table = str_replace(" ","",$table);
		//$table = str_replace(" ","",$table);

		$table = explode('{tr}', $table);
		array_pop($table);
		array_shift($table);
		foreach ($table as $key=>$tr) {
			$td = explode('{td}', $tr);
			array_pop($td);
			$td_array[] = $td;
		}
		return $td_array;
	}

}

/* End */