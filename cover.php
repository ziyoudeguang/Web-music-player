<?php
// 封面接口：按歌曲文件名返回封面图片
// 用法：cover.php?file=歌曲文件名.mp3
//
// 封面来源（按优先级）：
//   1. music/ 目录下的同名图片，例如 "歌名.mp3" 对应 "歌名.jpg"（也支持 png / webp / gif，扩展名请用小写）
//   2. 音频文件里内嵌的封面（需要 getid3 文件夹）
// 两者都没有时返回 404，前端会保留默认的渐变底色，不影响播放

// 输出的是二进制图片，不能让 PHP 的报错文字混进去
ini_set('display_errors', '0');

$music_dir = __DIR__ . '/music/';
$supported_formats = ['mp3', 'flac', 'wav', 'm4a', 'ogg'];
$image_exts = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif'
];

// 没有封面：返回 404，并让浏览器短暂缓存这个结果
function cover_not_found() {
    http_response_code(404);
    header('Cache-Control: public, max-age=300');
    exit;
}

// 通过文件头判断图片真实类型，只放行常见图片格式
function sniff_image_mime($data) {
    if (strncmp($data, "\xFF\xD8\xFF", 3) === 0) return 'image/jpeg';
    if (strncmp($data, "\x89PNG\r\n\x1a\n", 8) === 0) return 'image/png';
    if (strncmp($data, 'GIF8', 4) === 0) return 'image/gif';
    if (strlen($data) > 12 && substr($data, 0, 4) === 'RIFF' && substr($data, 8, 4) === 'WEBP') return 'image/webp';
    return null;
}

// ---------- 校验参数 ----------
$file = isset($_GET['file']) ? (string)$_GET['file'] : '';
if ($file === '' || strpos($file, "\0") !== false) cover_not_found();

$file = basename($file); // 只取文件名，防止用 ../ 访问 music/ 以外的文件
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, $supported_formats, true)) cover_not_found();

$path = $music_dir . $file;
if (!is_file($path)) cover_not_found();

// ---------- 1. 同名图片 ----------
$base = pathinfo($file, PATHINFO_FILENAME);
$sidecar_path = null;
$sidecar_mime = null;
foreach ($image_exts as $e => $mime) {
    $p = $music_dir . $base . '.' . $e;
    if (is_file($p)) {
        $sidecar_path = $p;
        $sidecar_mime = $mime;
        break;
    }
}

// ETag：音频文件或同名图片变化后，浏览器会自动拿到新封面
$etag_src = $file . '|' . filemtime($path) . '|' . filesize($path);
if ($sidecar_path) {
    $etag_src .= '|' . $sidecar_path . '|' . filemtime($sidecar_path) . '|' . filesize($sidecar_path);
}
$etag = '"' . md5($etag_src) . '"';

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=86400');
    http_response_code(304);
    exit;
}

if ($sidecar_path) {
    header('Content-Type: ' . $sidecar_mime);
    header('Content-Length: ' . filesize($sidecar_path));
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($sidecar_path);
    exit;
}

// ---------- 2. 音频文件内嵌封面 ----------
$getid3_file = __DIR__ . '/getid3/getid3.php';
if (!file_exists($getid3_file)) cover_not_found();
require_once $getid3_file;

$data = null;
try {
    $getID3 = new getID3;
    $info = $getID3->analyze($path);
    getid3_lib::CopyTagsToComments($info);

    // 有多张图片时，优先选"封面（正面）"，否则取第一张有数据的
    $best = null;
    if (!empty($info['comments']['picture']) && is_array($info['comments']['picture'])) {
        foreach ($info['comments']['picture'] as $pic) {
            if (empty($pic['data'])) continue;
            if ($best === null) $best = $pic;
            if (!empty($pic['picturetype']) && stripos($pic['picturetype'], 'front') !== false) {
                $best = $pic;
                break;
            }
        }
    }
    if ($best !== null) $data = $best['data'];
} catch (Exception $e) {
    cover_not_found();
}

if ($data === null || $data === '') cover_not_found();

$mime = sniff_image_mime($data);
if ($mime === null) cover_not_found();

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($data));
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $data;
