<?php
class Card {
	static $server_url = 'http://safe.phpcms.cn/index.php';

	/**
	 * 到远程服务器上去取KEY
	 */
	public static function getKey() {
		
		return self::_get_data('?op=key&release='.self::getRelease());
	}

	public static function getRelease() {
		return C('pc_release');
	}
	
	/**
	 * 卡片的显示地址
	 * @param $sn 口令卡编号
	 */
	public static function getPic($sn) {
		$key = self::getKey();
		return self::$server_url.'?op=card&release='.self::getRelease().'&key='.urlencode($key).'&code='.urlencode(self::sysAuth("sn=$sn", 'ENCODE', $key));
	}
	
	/**
	 * 申请新的卡片
	 * @return 返回卡片的sn
	 */
	public static function creatCard() {
		$key = self::getKey();
		return self::_get_data('?op=creat_card&release='.self::getRelease().'&key='.urlencode($key));
	}
	
	/**
	 * 解除口令卡绑定
	 * @param string $sn 口令卡编号
	 */
	public static function removeCard($sn) {
		$key = self::getKey();
		return self::_get_data('?op=remove&release='.self::getRelease().'&key='.urlencode($key).'&code='.urlencode(self::sysAuth("sn=$sn", 'ENCODE', $key)));
	}
	
	/**
	 * 请求口令验证码
	 * @param string $sn 口令卡编号
	 */
	public static function autheRand($sn) {
		$key = self::getKey();
		$data = self::_get_data('?op=authe_request&release='.self::getRelease().'&key='.urlencode($key).'&code='.urlencode(self::sysAuth("sn=$sn", 'ENCODE', $key)));
		return array('rand'=>$data,'url'=>self::$server_url.'?op=show_rand&release='.self::getRelease().'&key='.urlencode($key).'&code='.urlencode(self::sysAuth("rand=$data", 'ENCODE', $key)));
	}
	
	/**
	 * 验证动态口令
	 * @param string $sn     口令卡编号
	 * @param string $code   用户输入口令
	 * @param string $rand   随机码
	 */
	public static function verification($sn, $code, $rand) {
		$key = self::getKey();
		return self::_get_data('?op=verification&release='.self::getRelease().'&key='.urlencode($key).'&code='.urlencode(self::sysAuth("sn=$sn&code=$code&rand=$rand", 'ENCODE', $key)), 'index.php?m=Admin&c=Index&a=publicCard');
	} 
	
	/**
	 * 请求远程数据
	 * @param string $url       需要请求的地址。
	 * @param string $backurl   返回地址
	 */
	private static function _get_data($url, $backurl = '') {
		if ($data = @file_get_contents(self::$server_url.$url)) {
			$data = json_decode($data, true);
			
			//如果系统是GBK的系统，把UTF8转码为GBK
			if (C('charset') == 'gbk') {
				$data =  array_iconv($data, 'utf-8', 'gbk');
			}
			
			if ($data['status'] != 1) {
				showmessage($data['msg'], $backurl);
			} else {
				return $data['msg'];
			}
		} else {
			showmessage(L('your_server_it_may_not_have_access_to').self::$server_url.L('_please_check_the_server_configuration'));
		}
	}

	private function sysAuth($txt, $operation = 'ENCODE', $key = '') {
		$key	= $key ? $key : 'oqjtioxiWRWKLEQJLKj';
		$txt	= $operation == 'ENCODE' ? (string)$txt : base64_decode($txt);
		$len	= strlen($key);
		$code	= '';
		for($i=0; $i<strlen($txt); $i++){
			$k		= $i % $len;
			$code  .= $txt[$i] ^ $key[$k];
		}
		$code = $operation == 'DECODE' ? $code : base64_encode($code);
	return $code;
}
}