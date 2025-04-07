<?php
session_start();

// 检查GD库是否已安装
if (!extension_loaded('gd')) {
    die('PHP GD library is not installed');
}

// 生成随机字符串
function generateRandomString($length = 6) {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // 去掉容易混淆的字符
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// 设置图片参数
$width = 120;
$height = 40;
$font_size = 20;

// 创建图片
$image = imagecreatetruecolor($width, $height);

// 设置颜色
$bg_color = imagecolorallocate($image, 245, 245, 245);       // 浅灰背景
$text_color = imagecolorallocate($image, 33, 37, 41);        // 深色文字
$noise_color = imagecolorallocate($image, 150, 150, 150);    // 灰色噪点

// 填充背景
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// 添加随机噪点
for($i = 0; $i < ($width * $height) / 10; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// 添加随机线条
for($i = 0; $i < 5; $i++) {
    imageline($image, 
        rand(0, $width/2), rand(0, $height), 
        rand($width/2, $width), rand(0, $height), 
        $noise_color);
}

// 生成验证码文本
$captcha_text = generateRandomString(5);  // 5个字符
$_SESSION['captcha'] = $captcha_text;

// 计算文字位置
$text_box = imagettfbbox($font_size, 0, __DIR__ . '/arial.ttf', $captcha_text);
$text_width = abs($text_box[4] - $text_box[0]);
$text_height = abs($text_box[5] - $text_box[1]);
$x = ($width - $text_width) / 2;
$y = ($height + $text_height) / 2;

// 在图片上写入文本（使用内置字体，因为不是所有系统都有 TTF 字体）
$font = 5; // 使用内置字体
$x = 15;
for($i = 0; $i < strlen($captcha_text); $i++) {
    imagestring($image, $font, $x + ($i * 20), ($height - 15) / 2, 
                $captcha_text[$i], $text_color);
}

// 设置正确的头部
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 输出图片
imagepng($image);
imagedestroy($image);
?> 