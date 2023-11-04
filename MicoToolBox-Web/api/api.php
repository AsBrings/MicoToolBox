<?php
if (isset($_GET['action']) && $_GET['action'] === 'qrlogin') {
    $url = "https://c4.lp.account.xiaomi.com/longPolling/loginUrl?sid=micoapi";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $UserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36";
    curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);

    $response = curl_exec($ch);
    curl_close($ch);

    $response = str_replace('&&&START&&&', '', $response);
    $jsonData = json_decode($response, true);

    if ($jsonData !== null && isset($jsonData["qr"]) && isset($jsonData["lp"])) {
        $qrUrl = $jsonData["qr"];
        $lpUrl = $jsonData["lp"];

        echo json_encode(['qrUrl' => $qrUrl, 'lpUrl' => $lpUrl]);
    } else {
        echo json_encode(['qrUrl' => null, 'lpUrl' => null]);
    }
}else{
    if(isset($_POST['action']) && $_POST['action'] === 'serviceToken'){
        $location = $_POST['location'];
        $nonce = $_POST['nonce'];
        $ssecurity = $_POST['ssecurity'];

        $tempStr = "nonce={$nonce}&{$ssecurity}";
        $hashStr = hash("sha1", $tempStr, true);
        $clientSign = base64_encode($hashStr);
//echo "clientsign=\n";
//echo $clientSign . "\n";

        $times = 20;
//$APP_UA = "APP/com.xiaomi.mico APPV/2.1.17 iosPassportSDK/3.4.1 iOS/13.5";
//$headers = array(
//    "User-Agent: $APP_UA",
//    "Accept-Language: zh-cn",
//    "Connection: keep-alive"
//);

        $clientSignEncoded = urlencode($clientSign);

        $url = $location . "&clientSign=" . $clientSignEncoded;
//echo $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
//curl_setopt($ch, CURLOPT_HTTPHEADER," ");//$headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $response = curl_exec($ch);

        if(curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($response, 0, $headerSize);
//    echo "Response Headers:\n$responseHeaders\n";
            curl_close($ch);

            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $responseHeaders, $cookieMatches);
            $cookies = [];

            foreach ($cookieMatches[1] as $cookie) {
                $cookieParts = [];
                parse_str($cookie, $cookieParts);
                $cookies += $cookieParts;
            }
        }

        echo urlencode($cookies['serviceToken']);
    }else{
        if(isset($_POST['action']) && $_POST['action'] === 'getdevices'){
            $userId=$_POST['userId'];
            $serviceToken=$_POST['serviceToken'];
                // 构建header字符串
            $header = array(rawurldecode("cookie:userId={$userId};serviceToken={$serviceToken}"));
            //echo "header:" . $header[0];//用于检查格式 人麻了属于是
            $url = "https://api.mina.mi.com/admin/v2/device_list?master=0&requestId=CdPhDBJMUwAhgxiUvOsKt0kwXThAvY";
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                echo 'cURL error: ' . curl_error($curl);
            }
            curl_close($curl);
            //echo 'resp:' . $response . 'end';//检查响应
            $jsondata=json_decode($response,true);
            if($jsondata['code'] == 0){
                $device = $jsondata['data'];
                $newArray =array() ;
                if(is_array($device)){
                //print_r($device);
                    foreach($device as $key => $value){
                     $tmpArray= array();
                     foreach($value as $k => $v){
                        if($k == "name"){
                            $tmpArray['name'] = $v;
                        }
                        if($k == "hardware"){
                            $tmpArray['hardware'] = $v;
                        }
                        if($k == "deviceID"){
                            $tmpArray['deviceID'] = $v;
                        }
                        if($k == "serialNumber"){
                            $tmpArray['serialNumber'] = $v;
                        }
                        if($k == "romVersion"){
                            $tmpArray['version'] = $v;
                        }
                        if($k == "presence"){
                            $tmpArray['Status'] = $v;
                        }
                    }
                    array_push($newArray,$tmpArray);
                    unset($tmpArray);
                }
            }
            $arr = json_encode($newArray,JSON_UNESCAPED_UNICODE);
            echo $arr;
        }
    }else{
        echo null;
    }
}
}
?>
