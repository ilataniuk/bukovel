<?php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'lvsoft_bukovel');
define('DB_USER', 'root');
define('DB_PASS', '');

class Bukovel {

	protected $db, $dir, $cams = array(
		'01' => array('origin'=>'1','url'=>'https://bukovel.com/media/delays/lift1r.jpg'),
		'02' => array('origin'=>'2R','url'=>'https://bukovel.com/media/delays/lift2r.jpg'),
#		'2R'=> array('origin'=>'2','url'=>'https://bukovel.com/media/delays/lift2.jpg'),
		'05' => array('origin'=>'5', 'url'=>'https://bukovel.com/media/delays/lift5.jpg' ),
#		'06' => array('origin'=>'6', 'url'=>'https://bukovel.com/media/delays/lift6.jpg' ),
		'07' => array('origin'=>'7', 'url'=>'https://bukovel.com/media/delays/lift7.jpg' ),
		'08' => array('origin'=>'8', 'url'=>'https://bukovel.com/media/delays/lift8.jpg' ),
		'09' => array('origin'=>'9', 'url'=>'https://bukovel.com/media/delays/lift3.jpg' ),
		'11'=> array('origin'=>'11','url'=>'https://bukovel.com/media/delays/lift11.jpg'),
		'12'=> array('origin'=>'12','url'=>'https://bukovel.com/media/delays/lift12.jpg'),
		'13'=> array('origin'=>'13','url'=>'https://bukovel.com/media/delays/lift13.jpg'),
		'14'=> array('origin'=>'14','url'=>'https://bukovel.com/media/delays/lift14.jpg'),
		'15'=> array('origin'=>'15','url'=>'https://bukovel.com/media/delays/lift15.jpg'),
		'16'=> array('origin'=>'16','url'=>'https://bukovel.com/media/delays/lift16.jpg'),
		'17'=> array('origin'=>'17','url'=>'https://bukovel.com/media/delays/lift17.jpg'),
		'22'=> array('origin'=>'22','url'=>'https://bukovel.com/media/delays/lift22.jpg'),
	);

	function __construct(){

		$this->dir = dirname(__FILE__).'/';

		if(isset($_SERVER['SERVER_NAME']) && isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'touch')){
			$this->draw_touch();
		}elseif(isset($_SERVER['SERVER_NAME'])){
			$this->draw_page();
		}else{
			# running form command line
			global $argv;
			array_shift($argv);
			switch (array_shift($argv)) {
				case 'init':
					$this->db_init();
				break;
				case 'deploy':
					$this->deploy($argv);
				break;
				default:
					$this->cron_update();
			}

		}
	}

	function get_cam_path($k){
		return 's/'.$k.'.jpg';
	}

	function cron_update(){
		$data = $this->get_lift_status();

		$ua = array (
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 YaBrowser/17.10.0.2017 Yowser/2.5 Safari/537.36',
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063',
			'Mozilla/5.0 (Windows NT 6.2; WOW64)',
			'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0_3 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A432 Safari/604.1',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8',
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.124 Safari/537.36',
			'Opera/9.61 (Windows NT 5.1; U; ru)',
			'Mozilla/5.0 (Linux; Android 7.0; MI 5 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 YaBrowser/17.9.0.523.00 Mobile Safari/537.36',
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36'
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	#	curl_setopt($ch, CURLOPT_COOKIEJAR, $this->dir.'cookie.dat');
	#	curl_setopt($ch, CURLOPT_COOKIEFILE, $this->dir.'cookie.dat');
		curl_setopt($ch, CURLOPT_USERAGENT, $ua[array_rand($ua)]);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		# get temp
		$url = 'https://bukovel.com/cams/';
		curl_setopt($ch, CURLOPT_URL, $url);
		$cont = curl_exec($ch);
		if(preg_match_all('/data-cam-temp="([\-0-9\,]+)"/imsU', $cont, $out, PREG_PATTERN_ORDER)){
			#print_r($out); flush();
			$data['temp'] = array();
			foreach ($out[1] as $item){
				$data['temp'][] = floatval(preg_replace('/,/','.',$item));
			}
			sort($data['temp']);
			$t0 = $data['temp'][0];
			$t1 = $data['temp'][count($data['temp'])-1];
			$data['temp'] = ($t0 != $t1) ? $t0.' .. '.$t1 : $t0;
			$data['temp_upd'] = date("H:i d/m");
			#print_r($data); flush();
		}

		# get lift status
		echo $url = 'https://bukovel.com/ski/status'; flush();
		curl_setopt($ch, CURLOPT_URL, $url);
		$cont = curl_exec($ch);
		$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		echo ' ... '.$code."\n"; flush();

		if(preg_match_all('%<div\s+class="track-status-icon info-hover\s+status-([^\s]+)(?:\s[^"]+)?">\s*([0-9R]+)\s*<i>\s*([0-9/]*)\s*</i>%imsU', $cont, $out, PREG_SET_ORDER)){
			#print_r($out);
			$data['lift'] = array();
	 		foreach ($out as $item){
	 			$data['lift'][ $item[2] ] = ($item[1] == 'inactive') ? 'closed' : 'open';
	 			if($item[3]) $data['lift_change'][ $item[2] ] = $item[3];
	 		}
		}

		# get webcam images
		foreach($this->cams as $k=>$v){
			$uri = '?='.date("His").sprintf("%03d",rand(0,999));
			echo $v['url'].$uri; flush();
			curl_setopt($ch, CURLOPT_URL, $v['url'].$uri);
			$cont = curl_exec($ch);
			$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			echo ' ... '.$code."\n"; flush();
			if($code==200){
				$fname = $this->dir.$this->get_cam_path($k);
				@unlink($fname);
				file_put_contents($fname,$cont) or print_r(error_get_last());
				$this->make_thumb($fname);
				chmod($fname,0644);
				$data['lift_upd'] = date("H:i d/m");
			}
		}

		curl_close($ch);
		if(count($data)){
			$stname = $this->dir.'status.dat';
			echo file_put_contents($stname,serialize($data)) ? $stname." => OK " : print_r(error_get_last());
			chmod($stname,0644);
		}
	}

	function make_thumb($src, $dest='', $width=1000, $height=600){
		if(!$dest) $dest = $src;
		if(!file_exists($src)) return false;
		$size = getimagesize($src);
		if($size === false) return false;
		$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
		$icfunc = "imagecreatefrom" . $format;
		$ifunc = "image" . $format;
		if(!function_exists($icfunc)) return false;
		if(!function_exists($ifunc)) return false;
		if(($isrc = @$icfunc($src)) == '') return false;
		$idest = imagecreatetruecolor($width, $height);
		imagecopyresampled($idest, $isrc, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
		imagedestroy($isrc);
		if($ifunc == 'imagejpeg'){
			$res = $ifunc($idest, $dest, 90);
		}else{
			$res = $ifunc($idest, $dest);
		}
		imagedestroy($idest);
		echo $dest.' => '.($res ? 'OK' : 'Fail')."\n\n";
		return $res;
	}

	function draw_touch_text($im,$text,$x,$y,$color,$size=40){
		$font = $this->dir."s/sans.ttf";
		if($x<0){
			list(,,$w,) = imagettfbbox($size,0,$font,$text);
			$x = imagesx($im)-$w+$x;
		}
	#	imagettftext($im, $size, 0, $x+1, $y+1, $cb, $font, $text);
	#	file_put_contents('1.txt',implode("|",imagettfbbox($size,0,$font,$text))."\n".file_get_contents('1.txt'));
		imagettftext($im, $size, 0, $x, $y,$color, $font, $text);
	}

	function draw_touch(){
		$this->get_lift_status();

		header ("Content-type: image/png");
		$im = imagecreatefrompng($this->dir."s/touch.png")  or die("Cannot Initialize new GD image stream");
		$cr = imagecolorallocate($im, 250, 0, 0);
		$cg = imagecolorallocate($im,  4, 198, 125);
		$cw = imagecolorallocate($im, 0, 0, 0);
		$cb = imagecolorallocate($im, 0, 0, 250);

		$total = count($this->cams);
		$opened = 0;
		foreach($this->cams as $k=>$v){
			if($this->status['lift'][ $v['origin'] ] == 'open') $opened++;
		}

		$this->draw_touch_text($im,$this->status['temp'].'°',-7,47,$cb,13);
		$this->draw_touch_text($im,$opened,-77,100,($opened ? $cg : $cr),32);
		$this->draw_touch_text($im,' / '.$total,42,100,$cw,32);

		imagepng($im);
		imagedestroy($im);
	}

	function get_lift_status(){
		return $this->status = unserialize(file_get_contents($this->dir.'status.dat'));
	}

	function db_connect(){
		$db_user = isset($_SERVER['MYSQL_USER']) ? $_SERVER['MYSQL_USER'] : DB_USER;
		$db_pass = isset($_SERVER['MYSQL_PASS']) ? $_SERVER['MYSQL_PASS'] : DB_PASS;
		return $this->db = new mysqli(DB_HOST, $db_user, $db_pass, DB_NAME);
	}

	function db_close(){
		if($this->db) $this->db->close();
	}

	function db_stat(){
		$data = array();
		if($this->db_connect()){
			$dt = date("Y-m-d");
			$ip = $_SERVER['REMOTE_ADDR'];
			$ref = isset($_SERVER['HTTP_REFERER']) ? $this->db->real_escape_string($_SERVER['HTTP_REFERER']) : '';
			$this->db->query("INSERT INTO `stats_ip` (`date`, `ip`, `referer`) VALUES ('".$dt."',INET_ATON('".$ip."'),'".$ref."') ON DUPLICATE KEY UPDATE cnt = cnt + 1");
			$rrr = $this->db->query("SELECT sum(cnt) hits, count(ip) hosts  FROM `stats_ip` WHERE date = '".$dt."'");
			$data = $rrr->fetch_array(MYSQLI_ASSOC);
			$this->db_close();
		}
		return $data;
	}

	function db_init(){
		if($this->db_connect()) $this->db->query("
			CREATE TABLE IF NOT EXISTS `stats_ip` (
				`date` date NOT NULL,
				`ip` int UNSIGNED NOT NULL DEFAULT '0',
  				`cnt` int UNSIGNED NOT NULL DEFAULT '1'
			) ENGINE=MyISAM
		");
		$this->db_close();
	}

	function deploy($argv){
		$cont = file_get_contents(__FILE__);
		$cont = preg_replace("/define\('DB_USER', 'root'\);/is", "define('DB_USER', '".array_shift($argv)."');", $cont);
		$cont = preg_replace("/define\('DB_PASS', ''\);/is", "define('DB_PASS', '".array_shift($argv)."');", $cont);
		file_put_contents(__FILE__,$cont) or print_r(error_get_last());
	}

	function draw_cam_list(){
		$cam_list = '';
		foreach($this->cams as $k=>$v){
			$rnd = rand(10000,99999);
			$url = $this->get_cam_path($k);
			$url = file_exists($this->dir.$url) ? '/'.$url : '/s/empty.gif';
			$cam_list .= '<div class="cam '.$this->status['lift'][ $v['origin'] ].'">
				<a class="fancy" data-fancybox="gallery" data="'.$url.'" href="'.$url.'?'.$rnd.'" title="'.$k.'">
					<img title="Черга на нижній станції витягу №'.intval($k).'" alt="Черга на нижній станції витягу №'.$v['origin'].'" src="'.$url.'?'.$rnd.'">
					</a><i>'.intval($k).'</i>'.
#					($this->status['lift_change'][ $v['origin'] ] ? '<span>'.$this->status['lift_change'][ $v['origin'] ].'</span>' : '').
			'</div>';
		}

		return $cam_list;
	}

	function draw_page(){

		if(!isset($_SERVER['MYSQL_USER']) && ($_SERVER['SERVER_PORT'] != 443)){
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: https://".$_SERVER['SERVER_NAME']);
			return;
		}

		$stat = $this->db_stat();

		$this->get_lift_status();

		// <meta name="apple-mobile-web-app-capable" content="yes">
		// <meta name="apple-mobile-web-app-status-barstyle" content="black-translucent">
		header("Content-type: text/html; charset=UTF-8");

?>
<html>
<head>
<title>Контроль черг - Буковель - Bukovel</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--<meta name="description" itemprop="description" content="Контроль черг на витягах Г/К Буковель в режимі OnLine">-->
<meta name="application-name" content="Черги Bukovel">
<meta name="apple-mobile-web-app-title" content="Черги Bukovel">
<meta property="og:title" content="Контроль черг на витягах Г/К Буковель">
<meta property="og:image" content="<?='/'.$this->get_cam_path('02')?>">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<style>
	body { padding:0; margin:0; background-color:#f7f7f7; color:#b2b2b2; font-family:"Fira Sans", "Source Sans Pro", Helvetica, Arial, sans-serif; }
	main { margin:0; padding:10px; gap:10px; display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)) }
	main div { display:block; overflow:hidden; position:relative; padding:0; margin:0; border-radius:20px }
	main div a img { border-radius:20px; width:100%; transform: scale(1.1,1.1); opacity:0.9; vertical-align:0px; cursor:pointer; transition:transform 0.2s,opacity 0.2s }
	main div a:hover img { border-radius:20px; transform: scale(1.4,1.4); opacity:1 }
	main div i { position: absolute; bottom: 0; right: 0.2em; font-style: normal; font-size: 6em }
	main div.open i { color:#fff; -webkit-text-stroke:1px black; }
	main div.closed i { color:#ce1632; -webkit-text-stroke:1px white }
	header { display:flex; justify-content:space-between; border-radius:20px; background-color:#293146; height:2em; font-size:2em; font-weight:300; line-height:2em; margin:10px 10px 0 10px }
	header img { height:2em; border-top-left-radius:20px; border-bottom-left-radius:20px; }
	header span { white-space: nowrap; margin-right:15px }
	header h1 { margin:0 15px; font-size:0.92em; font-weight:lighter; text-align:left; flex-grow:10; /* white-space: nowrap; text-overflow:ellipsis; overflow:hidden */}
	header h1 u { text-decoration:none; color:#fff }
	footer { height:3em; font-size:0.8em; line-height:3em; text-align:right; margin:0 10px 10px 10px }
	@media only screen and (min-device-width: 320px) and (max-device-width: 568px) and (orientation: portrait) {
		i, .fancybox-title-over { font-size:4em }
		header { height:4em; font-size:2.7em; line-height:1em }
		header img, header span { height:4em }
		header span { font-size:2em;line-height:2em }
		header h1 {align-self:center; line-height:1.7em }
	}
	@media (max-width: 900px) {
		header h1 { font-size:0.8em; text-overflow:ellipsis; overflow:hidden }
	}
	@media (max-width: 800px) {
		header h1 { font-size:0.6em }
	}
	@media (max-width: 660px) {
		header h1 { font-size:0.6em; padding:0.5em 0; line-height:1.3em }
	}
	@media (max-width: 460px) {
		header h1 { font-size:0.5em; padding:0.8em 0 }
	}
</style>
<script type="text/javascript" src="/s/jquery.min.js"></script>
<script type="text/javascript" src="/s/jquery.fancybox.min.js"></script>
<link type="text/css" rel="stylesheet" href="/s/jquery.fancybox.css">
<link rel="shortcut icon" type="image/png" href="/favicon.png">
<script type="text/javascript">
var img_list = new Array();
var idx_list = 0;
var trefresh = 5;
function refreshImg(){
	var obj = img_list[idx_list];
	obj.href = $(obj).attr('data')+'?'+parseInt(Math.random()*100000);
	$(obj).find('img').attr('src',obj.href);
	if(++idx_list >= img_list.length) idx_list = 0;
	setTimeout("refreshImg()",1000*trefresh);
}
$(document).ready(function() {
	$('a.fancy').each(function(){img_list.push(this)});
	setTimeout("refreshImg();",1000*trefresh);
	$("a.fancy").fancybox({ buttons: [], clickContent: "close", mobile: { dblclickContent: "close" } });
});
</script>
</head>
<body>
<header>
	<a href="/"><img src="/s/bukovel.png"></a>
	<h1>Черги витягах Буковель станом на: <u><?=$this->status['lift_upd']?></u></h1>
	<span title="Оновлено: <?=$this->status['temp_upd']?>"><?=$this->status['temp']?>&deg;C</span>
</header>
<main><?=$this->draw_cam_list()?></main>
<footer title="Today: <?=intval($stat['hits'])?> / <?=intval($stat['hosts'])?>">Copyright &copy LV-Soft, 2015-<?=date('Y')?></footer>
</body>
</html>
<?php
 	}


}

new Bukovel();

?>