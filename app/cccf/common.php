<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 获取当前服务器时间
 *
 * @return void
 */
function getNowTime()
{
	return date('Y-m-d H:i:s', time());
}
/**
 * 转换utf8到gbk
 *
 * @param string $str
 * @return void
 */
function _cv_utf8_to_gbk($str = '')
{
	return iconv("utf-8", "gbk", $str);
}
/**
 * 转换gbk到utf8
 *
 * @param string $str
 * @return void
 */
function _cv_gbk_to_utf8($str = '')
{
	return iconv("gbk", "utf-8", $str);
}
/**
 * 转换数组数据至json串
 *
 * @param unknown $data
 * @return string
 */
function _cv_to_json($data)
{
	return json_encode($data, JSON_UNESCAPED_UNICODE);
}
/**
 * 转换json串至数组
 *
 * @param string $json
 * @return mixed
 */
function _cv_to_array($json = "")
{
	return json_decode($json, true);
}

/**
 * 获取当前用户的IP地址
 * 
 * @return [type] [description]
 */
function get_client_ip()
{
	return request()->ip();
}

/**
 * 根据案号全称，切换案号内容
 * 
 * @param string $ahqc        	
 * @return multitype:string |multitype:string unknown |multitype:string unknown Ambigous <>
 */
function explodeCaseinfo($ahqc = "（2016）粤2071民初100号")
{
	$str = $ahqc;
	$caseinfo = array(
		"caseinfo" => $str,
		"caseyear" => "",
		"casetype" => "",
		"casenum" => ""
	);
	//是否是半角的括号
	$islower = mb_substr($str, 0, 1, 'utf-8') != "（";
	$first = mb_substr($str, 0, 1, 'utf-8');


	$match_str = "/（([0-9]+)）/";
	if ($islower) {
		$match_str  = str_replace("（", '\(', $match_str);
		$match_str  = str_replace("）", '\)', $match_str);
	}

	$rt = preg_match($match_str, $str, $match);
	if (!$rt) {
		return $caseinfo;
	}

	$caseinfo['caseyear'] = $match[1];
	if (intval($caseinfo['caseyear']) >= 2016) {
		$match_str = "/（([0-9]+)）(.+[^0-9]+)([0-9]+)号/";
	} else {
		$match_str = "/（([0-9]+)）(.+)字第([0-9]+)号/";
	}
	if ($islower) {
		$match_str  = str_replace("（", '\(', $match_str);
		$match_str  = str_replace("）", '\)', $match_str);
	}

	$match = array();
	$rt = preg_match($match_str, $str, $match);
	if (!$rt) {
		return $caseinfo;
	}

	$caseinfo['casetype'] = $match[2];
	$caseinfo['casenum'] = $match[3];

	return $caseinfo;
}

function instr($str, $search)
{
	$index = strpos($str, $search);
	if ($index === false) {
		return false;
	} else {
		return true;
	}
}

function uuid($prefix = '')
{
	$chars = md5(uniqid(mt_rand(), true));
	$uuid  = substr($chars, 0, 8) . '-';
	$uuid .= substr($chars, 8, 4) . '-';
	$uuid .= substr($chars, 12, 4) . '-';
	$uuid .= substr($chars, 16, 4) . '-';
	$uuid .= substr($chars, 20, 12);
	return $prefix . $uuid;
}


use setasign\Fpdi\Fpdi;


function PDF_add_water($file = '', $text = '')
{
	$base = "../extend/fpdf/";
	$fontsize = 72; // 字体大小
	$font = "Helvetica"; // 字体
	// 字体颜色
	$font_color_red = 220;
	$font_color_green = 220;
	$font_color_blue = 220;


	require_once($base . 'fpdf/fpdf.php');
	require_once($base . 'fpdi2/src/autoload.php');

	$pdf = new Fpdi();

	$pageCount = $pdf->setSourceFile($file);

	$pic = getTextImage($text);
	
	$picsize = getimagesize($pic);
	
	$pic_width = $picsize[0];
	$pic_height = $picsize[1];


	for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
		// import a page
		$templateId = $pdf->importPage($pageNo);

		// get the size of the imported page
		$size = $pdf->getTemplateSize($templateId);
		// var_dump($size);

		// create a page (landscape or portrait depending on the imported page size)
		if ($size['width'] > $size['height']) {
			$pdf->AddPage('L', array($size['width'], $size['height']));
		} else {
			$pdf->AddPage('P', array($size['width'], $size['height']));
		}


		// use the imported page
		$pdf->useTemplate($templateId);


		$pdf->SetFont($font);
		// sign with current date
		// $pdf->SetTextColor($font_color_red, $font_color_green, $font_color_blue);
		// $pdf->SetFontSize($fontsize);


		// $string = $text;

		// pdf里的位置单位是mm，而图片生成的长宽大小是 px，需要经过换算
		$dpi = 3.78; // 默认的屏幕像素点为96，即1mm约为3.78px
		

		$left = ($size['width']-$pic_width/$dpi)/2;
		$top = ($size['height']-$pic_height/$dpi)/2;
		$pdf->Image($pic, $left, $top);
		// unlink($pic);
		// $pdf->SetXY($left, $top); // you should keep testing untill you find out correct x,y values
		// $pdf->Write(7, iconv("utf-8", 'gbk', get_real_ip()));
		// $pdf->Write(7, $string);
	}
	// $pdf->Output('I', 'generated.pdf');// 直接输出给浏览器，S是输出内容

	$tempname  = tempnam('../temp', 'pdf_');
	$content = $pdf->Output($tempname, 'F');
	unlink($pic);

	return $tempname;
}



function getTextImage($text = '测试案号')
{

	$base = "../extend/fpdf/";
	$size = 72; //字体大小

	$font = $base . "font/simhei.ttf"; //字体类型，这里为黑体，具体请在windows/fonts文件夹中，找相应的font文件
	$fontpath = realpath($font);


	$height = $size * 2;
	$width = ($size) * strlen($text)/3*2+10; //神奇的算法

	$angle = 45; //倾斜角度


	$top = $height / 2 + $size / 2;


	$img = imagecreate($width, $height); 

	$backcolor = imagecolorallocate($img, 0xff, 0xff, 0xff); //设置图片背景颜色 为黑色，仅为测试使用


	// imagecolorallocate($img, 0xff, 0xff, 0xff); //设置图片背景颜色，这里背景颜色为#ffffff，也就是白色
	// imagecolorallocate($img, 0xcc, 0xcc, 0xcc); //设置图片背景颜色，这里背景颜色为#ffffff，也就是白色
	// $color = imagecolorallocate($img, 0xff, 0xff, 0xff);
	$fontcolor = imagecolorallocatealpha($img, 0xcc, 0xcc, 0xcc,100); // 半透明效果，最后一个参数是透明度0-127之间，0完全不透明，127完全透明

	
	imagecolortransparent($img, $backcolor); // 设置白色为透明色
	// $fontcolor = imagecolorallocate($img, 0xcc, 0xcc, 0xcc); //设置字体颜色，这里为#000000，也就是黑色



	// 如果要加粗，可以写两次，把x轴加1即第4个参数

	
	imagettftext($img, $size,0, 0, $top, $fontcolor, $fontpath, $text); //将ttf文字写到图片中
	// imagettftext($img, $size, 0, 1, $top, $fontcolor, $path, $text); //将ttf文字写到图片中
	// header('Content-Type: image/png'); //发送头信息
	$filepath = '../temp/text_' . uniqid() . '.png';
	// 倾斜图片
	if($angle){
		$img = imagerotate($img,$angle,$backcolor,true);
	}

	// 旋转之后，生成的文字可能会有毛边。@todo 待解决毛边的bug

	imagecolortransparent($img, $backcolor); // 设置白色为透明色
	$bool = imagesavealpha($img, true); // 支持透明度

	$img = imagepng($img, $filepath); //输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

	// file_put_contents($filepath,$img);
	return $filepath;
}
