<?php
/**
 * 图片处理辅助函数
 */

/**
 * 调整图片大小并保存
 * 
 * @param string $source_path 源图片路径
 * @param string $target_path 目标图片路径
 * @param int $max_width 最大宽度
 * @param int $max_height 最大高度
 * @param int $quality 图片质量 (1-100)
 * @return bool 是否成功
 */
function resize_image($source_path, $target_path, $max_width, $max_height, $quality = 80) {
    // 启用错误报告
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<pre>";
    echo "开始调整图片大小...\n";
    echo "源文件路径: $source_path\n";
    echo "目标文件路径: $target_path\n";
    echo "最大宽度: $max_width\n";
    echo "最大高度: $max_height\n";
    echo "质量: $quality\n";
    
    // 检查源文件是否存在
    if (!file_exists($source_path)) {
        echo "错误：源文件不存在\n";
        echo "</pre>";
        return false;
    }
    
    // 检查文件权限
    if (!is_readable($source_path)) {
        echo "错误：源文件不可读\n";
        echo "</pre>";
        return false;
    }
    
    // 检查目标目录是否存在且可写
    $target_dir = dirname($target_path);
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            echo "错误：无法创建目标目录\n";
            echo "</pre>";
            return false;
        }
    }
    
    if (!is_writable($target_dir)) {
        echo "错误：目标目录不可写\n";
        echo "</pre>";
        return false;
    }
    
    // 获取图片信息
    $image_info = getimagesize($source_path);
    if ($image_info === false) {
        echo "错误：无法获取图片信息\n";
        echo "</pre>";
        return false;
    }
    
    echo "图片信息: ";
    print_r($image_info);
    
    // 获取原始尺寸
    $original_width = $image_info[0];
    $original_height = $image_info[1];
    echo "原始尺寸: {$original_width}x{$original_height}\n";
    
    // 计算新尺寸
    $ratio = min($max_width / $original_width, $max_height / $original_height);
    if ($ratio >= 1) {
        echo "图片已经小于最大尺寸，无需调整\n";
        // 如果图片已经小于最大尺寸，直接复制
        if (!copy($source_path, $target_path)) {
            echo "错误：复制文件失败\n";
            echo "</pre>";
            return false;
        }
        echo "文件已复制到目标位置\n";
        echo "</pre>";
        return true;
    }
    
    $new_width = round($original_width * $ratio);
    $new_height = round($original_height * $ratio);
    echo "新尺寸: {$new_width}x{$new_height}\n";
    
    // 创建新图片
    $new_image = imagecreatetruecolor($new_width, $new_height);
    if ($new_image === false) {
        echo "错误：无法创建新图片\n";
        echo "</pre>";
        return false;
    }
    
    // 根据图片类型处理
    switch ($image_info[2]) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source_path);
            // 保持PNG透明度
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source_path);
            break;
        default:
            echo "错误：不支持的图片类型\n";
            echo "</pre>";
            return false;
    }
    
    if ($source_image === false) {
        echo "错误：无法加载源图片\n";
        echo "</pre>";
        return false;
    }
    
    // 调整图片大小
    if (!imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height)) {
        echo "错误：调整图片大小失败\n";
        echo "</pre>";
        return false;
    }
    
    // 保存图片
    $success = false;
    switch ($image_info[2]) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($new_image, $target_path, $quality);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($new_image, $target_path, round($quality / 10));
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($new_image, $target_path);
            break;
    }
    
    // 清理资源
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    if (!$success) {
        echo "错误：保存图片失败\n";
        echo "</pre>";
        return false;
    }
    
    echo "图片调整大小成功\n";
    echo "</pre>";
    return true;
}

/**
 * 处理上传的图片
 * 
 * @param array $file $_FILES数组中的文件信息
 * @param string $target_dir 目标目录
 * @param int $max_width 最大宽度
 * @param int $max_height 最大高度
 * @param int $quality 图片质量
 * @return array 处理结果，包含成功状态、文件名和错误信息
 */
function process_uploaded_image($file, $target_dir, $max_width = 800, $max_height = 600, $quality = 80) {
    // 开启错误报告，方便调试
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<pre>开始处理上传的图片...</pre>";
    
    $result = [
        'success' => false,
        'filename' => '',
        'error' => ''
    ];
    
    // 检查文件是否上传成功
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = '文件上传失败，错误代码: ' . $file['error'];
        echo "<pre>" . $result['error'] . "</pre>";
        return $result;
    }
    
    // 检查文件类型
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        $result['error'] = '不支持的文件类型，只允许JPG、PNG、GIF和WebP格式';
        echo "<pre>" . $result['error'] . "</pre>";
        return $result;
    }
    
    // 生成唯一文件名
    $original_name = $file['name'];
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $original_name);
    
    echo "<pre>生成的文件名: $filename</pre>";
    
    // 确保目标目录存在
    if (!file_exists($target_dir)) {
        echo "<pre>创建目标目录: $target_dir</pre>";
        mkdir($target_dir, 0777, true);
    }
    
    // 临时文件路径
    $temp_path = $file['tmp_name'];
    // 目标文件路径
    $target_path = $target_dir . $filename;
    
    echo "<pre>临时文件路径: $temp_path</pre>";
    echo "<pre>目标文件路径: $target_path</pre>";
    
    // 调整图片大小并保存
    if (resize_image($temp_path, $target_path, $max_width, $max_height, $quality)) {
        $result['success'] = true;
        $result['filename'] = $filename;
        echo "<pre>图片处理成功</pre>";
    } else {
        $result['error'] = '图片处理失败';
        echo "<pre>" . $result['error'] . "</pre>";
    }
    
    return $result;
}

/**
 * 处理多文件上传
 * 
 * @param array $files $_FILES数组中的多文件信息
 * @param string $target_dir 目标目录
 * @param int $max_width 最大宽度
 * @param int $max_height 最大高度
 * @param int $quality 图片质量
 * @return array 处理结果，包含成功状态、文件名和错误信息
 */
function process_multiple_images($files, $target_dir, $max_width = 800, $max_height = 600, $quality = 80) {
    // 开启错误报告，方便调试
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<pre>开始处理多文件上传...</pre>";
    
    $result = [
        'success' => false,
        'filename' => '',
        'error' => ''
    ];
    
    // 检查是否有文件上传
    if (empty($files['name'][0])) {
        $result['error'] = '没有选择文件';
        echo "<pre>" . $result['error'] . "</pre>";
        return $result;
    }
    
    echo "<pre>文件信息: ";
    print_r($files);
    echo "</pre>";
    
    // 构建单个文件数组
    $file = [
        'name' => $files['name'][0],
        'type' => $files['type'][0],
        'tmp_name' => $files['tmp_name'][0],
        'error' => $files['error'][0],
        'size' => $files['size'][0]
    ];
    
    echo "<pre>构建的单个文件数组: ";
    print_r($file);
    echo "</pre>";
    
    // 调用单文件处理函数
    return process_uploaded_image($file, $target_dir, $max_width, $max_height, $quality);
}
?> 