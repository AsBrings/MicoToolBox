<?php
function serviceLoginAuth2($user, $pass) {
	$data = "_json=true&sid=micoapi&user=$user&hash=$pass";
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, "https://account.xiaomi.com/pass/serviceLoginAuth2"); 
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: deviceId=XXXXXXXXXXXXXXXX; sdkVersion=iOS-3.2.7', 'content-type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS , $data);
	$output = curl_exec($ch); 
	$outhead=curl_getinfo($ch);
	curl_close($ch);      
	preg_match('/location":"(.*?)"/', $output, $matches);
	if (!isset($matches[1])) {
		echo $output;
		return '';	
	}
	$result['location'] = $matches[1];
	preg_match('/ssecurity":"(.*?)"/', $output, $matches);
	$result['ssecurity'] = $matches[1];
	preg_match('/nonce":(.*?),/', $output, $matches);
	$result['nonce'] = $matches[1];
	preg_match('/cUserId":"(.*?)"/', $output, $matches);
	$result['cUserId'] = $matches[1];
	return $result;

}

function login_miai($user, $pass) {
    $session = serviceLoginAuth2($user, $pass);
		//print_r($session);
	$clientSign = getsign($session['nonce'], $session['ssecurity']);
		//if ($clientSign == '') break;
		//print_r($session['location']);
	$url=$session['location'];
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url."&clientSign=".urlencode($clientSign)); 
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: MISoundBox/1.4.0 iosPassportSDK/iOS-3.2.7 iOS/11.2.5','Accept-Language: zh-cn','Connection: keep-alive'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch); 
	$outhead=curl_getinfo($ch);
	curl_close($ch);      
	preg_match('/serviceToken=(.*?);/', $output, $matches, PREG_OFFSET_CAPTURE);
	if (!isset($matches[1])) {
		return '';	
	}
	$res['serviceToken'] = $matches[1][0];
	preg_match('/userId=(.*?);/', $output, $matches, PREG_OFFSET_CAPTURE);
	$res['userId'] = $matches[1][0];
	preg_match('/cUserId":"(.*?);/', $output, $matches, PREG_OFFSET_CAPTURE);
	$res['cUserId'] = $session['cUserId'];
	echo json_encode(['userId' => $res['userId'], 'serviceToken' => $res['serviceToken'], 'cUserId' => $res['cUserId']]);
}

function getsign($nonce, $secrity) {
	#逆向apk获取
	$str = "nonce={$nonce}&".$secrity;
	$sha1 =  sha1($str, true);
	return base64_encode($sha1);
}

function push_firmware($get_arr){
	if(!is_array($get_arr)){
		echo "参数错误";
		return ;
	}
	$token = str_replace('%2F', '/', $get_arr['serviceToken']);
	$headers = array(
    'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
    'Connection: keep-alive',
    "Cookie: userId=" . $get_arr['userId'] . ";cUserId=" . $get_arr['cUserId'] . ";deviceId=" . $get_arr['deviceId'] . ";sn=" . $get_arr['sn'] . ";serviceToken=" . $token,
    'Accept-Language: zh-cn',
    'User-Agent: MiSoundBox/2.0.41 CFNetwork/978.0.7 Darwin/18.5.0',
    'Pragma: no-cache',
    'Cache-Control: no-cache'
    );
    //print_r($headers);
    $bodydata = array(
    'checksum' => "{$get_arr['hash']}",
    'deviceId' => "{$get_arr['deviceId']}",
    'extra' => "{$get_arr['extra']}",
    'hardware' => "{$get_arr['hardware']}",
    'requestId' => "EIaICW2rWWhmwD515YuG2OonkkYcA1",
    'url' => "{$get_arr['link']}",
    'version' => "{$get_arr['version']}"
    );
    //print_r($bodydata);
    $body = http_build_query($bodydata);
	if(!$body){
		echo "请重新登录";
		return ;
	}
	//print_r($body);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.mina.mi.com/remote/ota/v2');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    echo $response . "\n";
}

$action =$_GET['action'];
switch($action) {
    case 'login':
        login_miai($_GET["userId"], $_GET["password"]);
        break;
    case 'push':
        //print_r($_GET);
        push_firmware($_GET);
        break;
}
?>