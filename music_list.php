<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 音乐目录路径
$music_dir = __DIR__ . '/music/';

// 自动识别当前访问的协议 + 域名 + 脚本所在目录，
// 这样无论换域名、加端口、本地调试，链接都能自动跟着变，不用手动写死
//
// 注意：如果服务器前面有反向代理/CDN（Nginx反代、宝塔、Cloudflare等），
// $_SERVER['HTTPS'] 经常不会被正确传递，需要额外判断代理转发过来的协议头，
// 否则会误判成 http，导致页面（https）加载了 http 的音乐链接，
// 浏览器就会提示"连接并非完全安全"（混合内容 Mixed Content）
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $is_https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // 当前脚本所在目录（如 /）
$base_url = $protocol . '://' . $host . $base_dir;

// 检查目录是否存在
if (!is_dir($music_dir)) {
    echo json_encode(['error' => '音乐目录不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- 读取音频标签（艺术家）----------
// 依赖 getID3 库：把 getid3 文件夹放在本文件同级目录即可
// 如果没放，会自动退回到"从文件名解析 / 未知艺术家"，不影响歌单正常使用
$getID3 = null;
$getid3_file = __DIR__ . '/getid3/getid3.php';
if (file_exists($getid3_file)) {
    require_once $getid3_file;
    $getID3 = new getID3;
    $getID3->option_tags_images = false; // 歌单里不提取封面图片数据，加快速度
}

// 扫描目录获取所有文件
$files = scandir($music_dir);

// 支持的音频格式
$supported_formats = ['mp3', 'flac', 'wav', 'm4a', 'ogg'];

$music_list = [];

foreach ($files as $file) {
    // 跳过 . 和 ..
    if ($file == '.' || $file == '..') continue;

    $file_path = $music_dir . $file;

    // 只处理文件，不处理子目录
    if (!is_file($file_path)) continue;

    // 获取文件扩展名
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // 检查是否是支持的音频格式
    if (in_array($ext, $supported_formats)) {
        // 获取文件名（不含扩展名）
        $filename = pathinfo($file, PATHINFO_FILENAME);

        // 构建 URL（注意 URL 编码），自动跟随当前域名，不再写死
        $url = $base_url . '/music/' . rawurlencode($file);

        // 读取艺术家标签
        $artist = '';
        if ($getID3) {
            try {
                $info = $getID3->analyze($file_path);
                getid3_lib::CopyTagsToComments($info);
                if (!empty($info['comments']['artist'][0])) {
                    $artist = trim($info['comments']['artist'][0]);
                }
            } catch (Exception $e) {
                // 解析失败就当没有标签
            }
        }

        // 标签里没有艺术家时，尝试从文件名 "歌名-歌手" 里拆出歌手
        if ($artist === '' && strpos($filename, '-') !== false) {
            $artist = trim(substr($filename, strpos($filename, '-') + 1));
        }

        $music_list[] = [
            'url' => $url,
            'title' => $filename,
            'artist' => $artist !== '' ? $artist : '未知艺术家',
            'format' => strtoupper($ext),
            'filename' => $file,
            // 封面由 cover.php 按需输出，没有封面时返回 404，前端保留默认底色
            'cover' => $base_url . '/cover.php?file=' . rawurlencode($file)
        ];
    }
}

// 按文件名排序
usort($music_list, function($a, $b) {
    return strcoll($a['title'], $b['title']);
});

echo json_encode([
    'total' => count($music_list),
    'music' => $music_list
], JSON_UNESCAPED_UNICODE);
?>
