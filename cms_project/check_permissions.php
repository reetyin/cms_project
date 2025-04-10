<?php
// 创建这个临时文件来检查权限
$upload_dir = 'uploads/schools/';
echo "Upload directory exists: " . (is_dir($upload_dir) ? 'Yes' : 'No') . "\n";
echo "Upload directory writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "\n";
echo "Upload directory permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "\n"; 