<?php
if (extension_loaded('gd')) {
    echo "GD library is available\n";
    echo "GD version: " . gd_info()['GD Version'] . "\n";
} else {
    echo "GD library is NOT available\n";
}
?> 