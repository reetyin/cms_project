<?php
// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>GD库函数测试</h1>";

// 检查GD库是否可用
if (extension_loaded('gd')) {
    echo "<p>GD库已加载</p>";
    
    // 获取GD库信息
    $info = gd_info();
    echo "<h2>GD库信息:</h2>";
    echo "<pre>";
    print_r($info);
    echo "</pre>";
    
    // 测试创建图片
    echo "<h2>测试创建图片:</h2>";
    
    // 创建一个简单的图片
    $width = 100;
    $height = 100;
    $image = imagecreatetruecolor($width, $height);
    
    if ($image) {
        echo "<p>成功创建图片</p>";
        
        // 填充颜色
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $red);
        
        // 保存图片
        $filename = 'test_image.png';
        if (imagepng($image, $filename)) {
            echo "<p>成功保存图片到 $filename</p>";
            echo "<img src='$filename' alt='测试图片'>";
        } else {
            echo "<p>保存图片失败</p>";
        }
        
        // 释放内存
        imagedestroy($image);
    } else {
        echo "<p>创建图片失败</p>";
    }
    
    // 测试调整图片大小
    echo "<h2>测试调整图片大小:</h2>";
    
    if (file_exists($filename)) {
        // 获取原始图片信息
        $image_info = getimagesize($filename);
        echo "<p>原始图片尺寸: {$image_info[0]} x {$image_info[1]}</p>";
        
        // 创建新图片
        $new_width = 50;
        $new_height = 50;
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        if ($new_image) {
            // 加载原始图片
            $source_image = imagecreatefrompng($filename);
            
            if ($source_image) {
                // 调整大小
                if (imagecopyresampled(
                    $new_image, $source_image,
                    0, 0, 0, 0,
                    $new_width, $new_height,
                    $image_info[0], $image_info[1]
                )) {
                    echo "<p>成功调整图片大小</p>";
                    
                    // 保存新图片
                    $new_filename = 'test_image_resized.png';
                    if (imagepng($new_image, $new_filename)) {
                        echo "<p>成功保存调整后的图片到 $new_filename</p>";
                        echo "<img src='$new_filename' alt='调整后的图片'>";
                    } else {
                        echo "<p>保存调整后的图片失败</p>";
                    }
                } else {
                    echo "<p>调整图片大小失败</p>";
                }
                
                // 释放内存
                imagedestroy($source_image);
            } else {
                echo "<p>加载原始图片失败</p>";
            }
            
            // 释放内存
            imagedestroy($new_image);
        } else {
            echo "<p>创建新图片失败</p>";
        }
    } else {
        echo "<p>测试图片不存在</p>";
    }
} else {
    echo "<p>GD库未加载</p>";
}
?> 