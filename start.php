<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'lvsoft_bukovel');
define('DB_USER', 'root');
define('DB_PASS', '');

class Bukovel {

	protected $db, $dir, $file, $cams = array(
		'01' => array('origin'=>'1','url'=>'https://bukovel.com/media/delays/lift1r.jpg'),
#		'01' => array('origin'=>'1','url'=>'https://bukovel.com/media/cams_th/23_full.jpg'),
		'02' => array('origin'=>'2R','url'=>'https://bukovel.com/media/delays/lift2r.jpg'),
#		'2R'=> array('origin'=>'2','url'=>'https://bukovel.com/media/delays/lift2.jpg'),
		'05' => array('origin'=>'5', 'url'=>'https://bukovel.com/media/delays/lift5.jpg' ),
#		'06' => array('origin'=>'6', 'url'=>'https://bukovel.com/media/delays/lift6.jpg' ),
		'07' => array('origin'=>'7', 'url'=>'https://bukovel.com/media/delays/lift7.jpg' ),
#		'7R' => array('origin'=>'7R','url'=>'https://bukovel.com/media/delays/lift7r.jpg' ),
		'08' => array('origin'=>'8', 'url'=>'https://bukovel.com/media/delays/lift8.jpg' ),
		'09' => array('origin'=>'9', 'url'=>'https://bukovel.com/media/delays/lift3.jpg' ),
		'11'=> array('origin'=>'11','url'=>'https://bukovel.com/media/delays/lift11.jpg'),
		'12'=> array('origin'=>'12','url'=>'https://bukovel.com/media/delays/lift12.jpg'),
		'13'=> array('origin'=>'13','url'=>'https://bukovel.com/media/delays/lift13.jpg'),
		'14'=> array('origin'=>'14','url'=>'https://bukovel.com/media/delays/lift14.jpg'),
		'15'=> array('origin'=>'15','url'=>'https://bukovel.com/media/delays/lift15.jpg'),
#		'15'=> array('origin'=>'15','url'=>'https://bukovel.com/media/cams_th/17_full.jpg'),
		'16'=> array('origin'=>'16','url'=>'https://bukovel.com/media/delays/lift16.jpg'),
		'17'=> array('origin'=>'17','url'=>'https://bukovel.com/media/delays/lift17.jpg'),
#		'17'=> array('origin'=>'16','url'=>'https://bukovel.com/media/cams_th/19_full.jpg'),
		'22'=> array('origin'=>'22','url'=>'https://bukovel.com/media/delays/lift22.jpg'),
	);

	function __construct(){
		#setlocale(LC_TIME, 'uk_UA');
		$this->dir  = dirname(__FILE__).'/';
		$this->file = 'status.json';
		# running form command line
		global $argv;
		if(!isset($_SERVER['SERVER_NAME'])){
			array_shift($argv);
			$cli_mode = array_shift($argv);
		}
		if(isset($_SERVER['SERVER_NAME']) && isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'touch')){
			$this->draw_touch();
		}elseif(isset($_SERVER['SERVER_NAME'])){
			$this->draw_page();
		}elseif($cli_mode == 'init'){
			$this->db_init();
		}elseif($cli_mode == 'deploy'){
			$this->deploy($argv);
		}else{
			$this->cron_update();
		}
	}

	function cron_update(){
		$data = $this->get_lift_status();
		$ua = array (
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 YaBrowser/17.10.0.2017 Yowser/2.5 Safari/537.36',
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063',
			'Mozilla/5.0 (Windows NT 6.2; WOW64)',
			'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A432 Safari/604.1',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8',
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.124 Safari/537.36',
			'Opera/9.61 (Windows NT 5.1; U; ru)',
			'Mozilla/5.0 (Linux; Android 7.0; MI 5 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 YaBrowser/17.9.0.523.00 Mobile Safari/537.36',
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36'
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
#		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->dir.'cookie.dat');
#		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->dir.'cookie.dat');
		curl_setopt($ch, CURLOPT_USERAGENT, $ua[array_rand($ua)]);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		# get temp
		echo $url = 'https://bukovel.com/api/v2/web-cams?offset=0&per_page=1000'; flush();
		curl_setopt($ch, CURLOPT_URL, $url);
		$cont = curl_exec($ch);
		$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		echo $this->print_code($url, $code)."\n";
		$out = json_decode($cont, 1);

		if(is_array($out['data'])){
			$data['temp'] = array();
			$t0 = $t1 = '';
			foreach ($out['data'] as $item){
				$t = floatval($item['meteo']['temp']);
				if(($t > -40) && ($t<40)) $data['temp'][] = $t;
			}
			if(count($data['temp'])){
				sort($data['temp']);
				$t0 = $data['temp'][0];
				$t1 = $data['temp'][count($data['temp'])-1];
			}
			$data['temp_upd'] = date("H:i d/m");
			#$data['temp_upd'] = strftime("%e %B %H:%M");
			$data['temp'] = "$t0" && "$t1" && abs($t1-$t0)>2 ? $t0.' .. '.$t1 : $t0;
			if(!strlen($data['temp'])) $data['temp'] = '-.-';
		}

		# get lift status
		echo $url = 'https://bukovel.com/api/v2/status-lifts-trails'; flush();
		curl_setopt($ch, CURLOPT_URL, $url);
		$cont = curl_exec($ch);
		$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		echo $this->print_code($url, $code)."\n";
		$out = json_decode($cont, 1);

		if(is_array($out['data']['lifts'])){
			#print_r($out['data']['lifts']);
	 		foreach ($out['data']['lifts'] as $item){
	 			$data['lift'][ $item['title'] ] = $item['status'] == 'OPEN' ? 'open' : 'closed';
	 			if($item['startDate'] || $item['stopDate']){
					$tmp = $item['startDate'] ? $item['startDate'] : $item['stopDate'];
					$tmp = explode('-',$tmp);
					$data['lift_change'][ $item['title'] ] = $tmp[2].'/'.$tmp[1];
				}
	 		}
		}
		#print_r($data);

		# get webcam images
		foreach($this->cams as $k=>$v){
			$uri = '?v='.date("dmyHi").sprintf("%02d",rand(0,99));
			echo $v['url'].$uri; flush();
			curl_setopt($ch, CURLOPT_URL, $v['url'].$uri);
			$cont = curl_exec($ch);
			$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			echo $this->print_code($v['url'].$uri, $code);
			if($code == 200){
				$fname = $this->dir.$this->get_cam_path($k);
				@unlink($fname);
				file_put_contents($fname,$cont) or print_r(error_get_last());
				$this->make_thumb($fname);
				chmod($fname,0644);
				$data['lift_upd'] = date("H:i d/m");
				#$data['lift_upd'] = strftime("%e %B %H:%M");
			}
		}

		curl_close($ch);

		if(count($data)){
			$stname = $this->dir.$this->file;
			echo file_put_contents($stname, json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE))
				? $stname.$this->print_code($stname)
				: print_r(error_get_last());
			chmod($stname, 0644);
		}
		echo "\n";
	}

	function print_code($str, $code='OK', $symbol='.', $limit=100){
		flush();
		return ' '.str_pad(' [ '.$code.' ]', $limit - strlen($str), $symbol, STR_PAD_LEFT)."\n";
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
		echo $dest.$this->print_code($dest, $res ? 'OK' : 'ERR', ' ')."\n";
		return $res;
	}

	function draw_touch_text($im,$text,$x,$y,$color,$size=40){
		$font = $this->dir."s/sans.ttf";
		if($x<0){
			list(,,$w,) = imagettfbbox($size,0,$font,$text);
			$x = imagesx($im)-$w+$x;
		}
#		imagettftext($im, $size, 0, $x+1, $y+1, $cb, $font, $text);
#		file_put_contents('1.txt',implode("|",imagettfbbox($size,0,$font,$text))."\n".file_get_contents('1.txt'));
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
			$this->db->query("INSERT INTO `stats_ip` (`date`, `ip`, `referer`) VALUES ('".$dt."',INET_ATON('".$ip."'),'".$ref."') ON DUPLICATE KEY UPDATE cnt = cnt + 1") or die($this->db->error);
			$drow = $this->db->query("SELECT sum(cnt) hits, count(ip) hosts  FROM `stats_ip` WHERE date = '".$dt."'") or die($this->db->error);
			$data = $drow->fetch_array(MYSQLI_ASSOC);
			$this->db_close();
		}else{
		}
		return $data;
	}

	function db_init(){
		if($this->db_connect()) $this->db->query("
			CREATE TABLE IF NOT EXISTS `stats_ip` (
				`date` DATE NOT NULL,
				`ip` INT UNSIGNED NOT NULL DEFAULT '0',
				`cnt` INT UNSIGNED NOT NULL DEFAULT '1',
				`referer` VARCHAR(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
			) ENGINE=MyISAM;
		");
		$this->db_close();
	}

	function deploy($argv){
		$cont = file_get_contents(__FILE__);
		$cont = preg_replace("/define\('DB_USER', 'root'\);/is", "define('DB_USER', '".array_shift($argv)."');", $cont);
		$cont = preg_replace("/define\('DB_PASS', ''\);/is", "define('DB_PASS', '".array_shift($argv)."');", $cont);
		file_put_contents(__FILE__,$cont) or print_r(error_get_last());
	}

	function get_cam_path($k){
		return 's/'.$k.'.jpg';
	}

	function get_cam_status($cam_num) {
		return array_key_exists($cam_num, $this->status['lift'])
			? ' '.$this->status['lift'][$cam_num]
			: '';
	}

	function get_lift_status(){
		$path = $this->dir.$this->file;
		$cont = file_exists($path) ? file_get_contents($path) : '{}';
		$data = json_decode($cont,1);
		if(!array_key_exists('lift', $data)){
			$data['lift'] = $data['lift_change'] = array();
			$data['lift_upd'] = $data['temp_upd'] = $data['temp'] = '';
		}
		return $this->status = $data;
	}

	function draw_cam_list(){
		#print_r($this->status);
		$cam_list = '';
		foreach($this->cams as $k=>$v){
			$rnd = rand(10000,99999);
			$url = $this->get_cam_path($k);
			$url = '/'.(file_exists($this->dir.$url) ? $url : 's/empty.gif');
			$status = $this->get_cam_status($v['origin']);
			$cam_list .= '<div class="cam'.$status.'">
				<a class="fancy" data-fancybox="gallery" data-caption="Черга на нижній станції витягу №'.intval($k).'" data="'.$url.'" href="'.$url.'?'.$rnd.'" title="'.$k.'">
					<img title="Черга на нижній станції витягу №'.intval($k).'" alt="Черга на нижній станції витягу №'.$v['origin'].'" src="'.$url.'?'.$rnd.'">
					</a><i>'.intval($k).'</i>'.
					(isset($this->status['lift_change'][ $v['origin'] ]) && ($status != ' open')
						? '<u>'.$this->status['lift_change'][ $v['origin'] ].'</u>'
						: ''
					).
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
<!-- <meta name="description" itemprop="description" content="Контроль черг на витягах Г/К Буковель в режимі OnLine"> -->
<meta name="application-name" content="Черги Bukovel">
<meta name="apple-mobile-web-app-title" content="Черги Bukovel">
<meta property="og:title" content="Контроль черг на витягах Г/К Буковель">
<meta property="og:image" content="<?='/'.$this->get_cam_path('02')?>">
<script type="text/javascript" src="/s/fancybox.umd.js"></script>
<script type="text/javascript" src="/s/script.js?v=3"></script>
<link type="text/css" rel="stylesheet" href="/s/fancybox.css">
<link type="text/css" rel="stylesheet" href="/s/style.css?v=3">
<link rel="shortcut icon" type="image/png" href="/favicon.png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
</head>
<body>
<header>
	<a href="/"><img src="/s/bukovel.png"></a>
	<h1>Черги витягах Буковель станом на: <u><?=$this->status['lift_upd']?></u></h1>
	<span title="Оновлено: <?=$this->status['temp_upd']?>"><?=$this->status['temp']?>&deg;C</span>
</header>
<main><?=$this->draw_cam_list()?></main>
<footer>
	By <a href="https://github.com/ilataniuk/bukovel" target="_blank">LV-Soft</a>,
	<span title="Today: <?=intval($stat['hits'])?> / <?=intval($stat['hosts'])?>">2015-<?=date('Y')?></span>
</footer>
</body>
</html>
<?php
	}
}

new Bukovel();

?>
