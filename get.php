<?php

/**
 * 获取favicon
 * @author    yyx
 * @date      2025年3月27日21:30:15
 * @link      https://log.pub
 * @version   1.0.0
 */
header('Content-Type: image/x-icon'); // 输出的是图标格式

$dir = __DIR__ . '/cache'; // 图标缓存目录
// 如果缓存目录不存在则创建
if (!is_dir($dir)) {
    mkdir($dir, 0755, true) or die('创建缓存目录失败！');
}

// 获取传入的 URL 参数
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

$save_fav_dir = $dir . "/" . md5($url) . ".ico"; // 图标保存的路径和名称（使用MD5避免文件名冲突）
// 调用缓存文件
if (file_exists($save_fav_dir)) { // 有缓存就直接输出缓存
    $file = file_get_contents($save_fav_dir);
    if ($file) {
        // writelog("file=".$file);
        die($file);
    }
}

// 也可以考虑其他常见的 favicon 路径
$faviconNames = [
    "/favicon.ico",
//    "/images/favicon.gif",
//    "images/favicon.png",
//    "/static/image/logo.png",
//    "/static/image/logo.png",
//    "/apple-touch-icon.png",
//    "/favicon-32x32.png",
//    "/favicon-16x16.png",
//    "/favicon.svg",
];

// 选择第一个存在的 favicon URL
//$finalFaviconName = $faviconNameStandard; // 默认使用标准路径
foreach ($faviconNames as $candidate) {
    // 这里选择不预先检查，直接尝试获取，可以根据需要优化为预先检查
    $finalFaviconName = $candidate;
    writelog(parseUrlComponents($url)['url_with_path']);
    // 尝试站点带一级目录下的favicon.ico文件
    getAndSaveFav(parseUrlComponents($url)['url_with_path'] . $finalFaviconName, $save_fav_dir);
    // 尝试站点根目录下的favicon.ico文件
    getAndSaveFav(parseUrlComponents($url)['base_url'] . $finalFaviconName, $save_fav_dir);
    //  break;
}
//从<link rel="icon" href="/static/image/logo.png">获取
getAndSaveFav(parseUrlComponents($url)['base_url'] . getFaviconUrl($url), $save_fav_dir);

//从<link rel="icon" href="/static/image/logo.png">获取,带一级目录
getAndSaveFav(parseUrlComponents($url)['url_with_path'] . "/" . getFaviconUrl($url), $save_fav_dir);

//从<link rel="icon" href="/static/image/logo.png">获取,直接url
getAndSaveFav(getFaviconUrl($url), $save_fav_dir);

//前面执行的后面就不执行了
//writelog($url . "/favicon.ico");
// 如果以上获取失败，尝试加载默认图标
$defaultIconPath = __DIR__ . '/null.ico';
if (file_exists($defaultIconPath)) {
    $file = file_get_contents($defaultIconPath);
    echoFav($save_fav_dir, $file);
} else {
    echoFav($save_fav_dir); // 如果默认图标也不存在，返回空内容
}

/**
 * 获取并保存favicon图标
 * @param $url 目标URL
 * @param $path 保存路径
 */
function getAndSaveFav($url, $path) {
    $timeout = 5; // 超时时间（秒）
    $connectTimeout = 3; // 连接超时时间（秒）

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36 Edg/100.0.1185.44");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 不验证SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // 不限制编码

    $file_content = curl_exec($ch);
    $file_info = curl_getinfo($ch);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);

    curl_close($ch);

    // 检查是否有 cURL 错误
    if ($curl_errno !== 0) {
        writelog("cURL 错误: " . $curl_error);
        return;
    }

    // 检查请求是否成功
    if ($file_info['http_code'] === 0) {
        writelog("请求失败，无法获取favicon图标。");
        return;
    }

    // 检查HTTP状态码
    $allowed_http_codes = [200, 304];
    if (!in_array($file_info['http_code'], $allowed_http_codes)) {
        writelog("请求返回的HTTP状态码不合法，无法获取favicon图标。");
        return;
    }

    $content_type = $file_info['content_type'] ?? '';

    // 检查是否为合法的favicon类型
    $allowed_favicon_types = [
        'image/x-icon',
        'image/vnd.microsoft.icon',
        'image/png',
        'image/gif',
        'image/jpeg',
        'image/jpg',
        'image/webp' // WebP格式的favicon
    ];

    if (in_array($content_type, $allowed_favicon_types)) {
        writelog("favicon图标已成功保存到:" . $path);
        echoFav($path, $file_content); // 保存并输出图标
    } else {
        writelog("获取到的内容类型不合法，无法保存favicon图标");
    }
}

/**
 * 输出最终的favicon图标
 * @param $path 图标保存路径
 * @param $file 图标文件内容（可选）
 */
function echoFav($path = '', $file = '') {
    if (empty($file)) {
        $file = "null.ico"; // 默认的图标
        $defaultIconPath = __DIR__ . '/' . $file;
        if (file_exists($defaultIconPath)) {
            $file = file_get_contents($defaultIconPath);
        } else {
            $file = ''; // 如果默认图标不存在，返回空内容
        }
    }

    if (!empty($path)) {
        file_put_contents($path, $file); // 保存文件
    }

    header('Content-Type: image/x-icon');
    die($file);
}

function getIP() {
    return isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : (isset($_SERVER["HTTP_X_REAL_IP"]) ? $_SERVER["HTTP_X_REAL_IP"] : $_SERVER["REMOTE_ADDR"]);
}

function writelog($loginfo) {
    $file_directory = 'log/'; // 要判断或创建的文件夹路径
//判断文件路径是否存在，不存在则创建
    if (!is_dir($file_directory)) {
        mkdir($file_directory, 0777, true);
    }
    $file = 'log/log_' . date('y-m-d') . '.log';
    //当前链接
    $url = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
    if (!is_file($file)) {
        file_put_contents($file, '', FILE_APPEND | LOCK_EX); //如果文件不存在，则创建一个新文件。
    }
    $contents = "nav_log" . "-" . date("Y-m-d H:i:s") . "|" . getIP() . "|" . $url . "|" . $loginfo . "\r\n";
    file_put_contents($file, urldecode($contents), FILE_APPEND);
}

//php 获取字符串url中的域名或者ip+端口，如果端口后面有目录，加上一级目录
function parseUrlComponents($url) {
    // 解析URL
    $parsed = parse_url($url);

    // 构建基础URL（协议+域名/IP+端口）
    $baseUrl = $parsed['scheme'] . '://';
    $baseUrl .= isset($parsed['host']) ? $parsed['host'] : '';
    $baseUrl .= isset($parsed['port']) ? ':' . $parsed['port'] : '';

    // 初始化带路径的URL
    $urlWithPath = $baseUrl;
    $firstDir = '';

    // 处理路径部分
    if (isset($parsed['path'])) {
        $path = $parsed['path'];

        // 移除文件名部分（如果有）
        if (preg_match('#^(.*?)(/[^/]+?\.[^/]+)?$#', $path, $matches)) {
            $path = $matches[1];
        }

        // 标准化路径（处理多个斜杠）
        $path = preg_replace('#//+#', '/', $path);
        $parts = explode('/', trim($path, '/'));

        if (!empty($parts[0])) {
            $firstDir = $parts[0];
            $urlWithPath .= '/' . $firstDir;
        }
    }

    return [
        'full_url' => $url, // 原始完整URL
        'base_url' => $baseUrl, // 协议+域名/IP+端口（无路径）
        'url_with_path' => $urlWithPath, // 协议+域名/IP+端口+一级目录
        'first_dir' => $firstDir       // 第一级目录名（若无则为空字符串）
    ];
}

//获取网页中的href
//opkg install php8-mod-dom_8.2.8-1_armv7-2.6.ipk
//opkg install php8-mod-filter_8.2.8-1_armv7-2.6.ipk 
function getFaviconUrl($url) {
    // 初始化 cURL
    $ch = curl_init();

    // 设置 cURL 选项
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36 Edg/100.0.1185.44");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 不验证SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // 执行 cURL 请求并获取网页内容
    $html = curl_exec($ch);

    // 检查是否有错误发生
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        return false;
    }

    // 关闭 cURL 资源
    curl_close($ch);

    // 创建 DOMDocument 对象
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // 使用 @ 抑制警告
    // 创建 XPath 对象
    $xpath = new DOMXPath($dom);

    // 查找符合条件的 <link> 标签
    $links = $xpath->query('//link[contains(@rel, "icon") or contains(@rel, "shortcut icon")]');

    // 返回第一个匹配的 href 值
    if ($links->length > 0) {
        $href = $links->item(0)->getAttribute('href');

        // Parse the original URL to get its components
        $parsedUrl = parse_url($url);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        // Check if the href is already a full URL
        if (filter_var($href, FILTER_VALIDATE_URL)) {
            return $href;
        }

        // Handle relative paths
        if (strpos($href, '../') === 0 || strpos($href, './') === 0 || strpos($href, '/') !== 0) {
            // Remove any ../ or ./ prefixes
            $href = ltrim(preg_replace('#^(\.\./|\./)+#', '', $href), '/');
            return '/' . $href;
        }

        return $href;
    }

    // 如果没有找到匹配的 <link> 标签，返回 false
    return false;
}
