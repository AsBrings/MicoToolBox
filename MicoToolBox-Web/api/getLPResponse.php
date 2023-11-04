<?php
// 检查是否存在GET 参数
    $lpUrl = $_GET['lpUrl'];

    // 使用 cURL 发起 GET 请求
    $ch = curl_init($lpUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//写xml
    curl_setopt($ch, CURLOPT_TIMEOUT,60);//超时
//    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36");
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
//    $response = str_replace('&&&START&&&', '', $response); // 傻逼解析不好用
    // 判断响应是否为200 OK
    if ($httpCode == 200) {
//        echo "start";//用于判断response起始位置
//        echo $response;

//手动解析（
        preg_match('/nonce":(.*?),"/', $response, $matches);
        $nonce=$matches[1];
        preg_match('/ssecurity":"(.*?)","/', $response, $matches);
        $ssecurity=$matches[1];
        preg_match('/location":"(.*?)","/', $response, $matches);
        $location=$matches[1];
        preg_match('/userId":(.*?),"/', $response, $matches);
        $userId=$matches[1];
        preg_match('/cUserId":"(.*?)","/', $response, $matches);
        $cUserId=$matches[1];
        echo json_encode(['nonce' => $nonce, 'ssecurity' => $ssecurity, 'locationurl' => $location, 'userId' => $userId, 'cUserId' => $cUserId]);

//        echo "end";//用于判断response结束位置
    } else {
        //header("HTTP/1.1 403 Forbidden");//让前端识别状态
        echo json_encode(['code' => 403]);
    }
?>