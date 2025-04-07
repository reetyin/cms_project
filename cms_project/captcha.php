<?php
class Captcha {
    private $width = 120;
    private $height = 40;
    private $length = 6;
    private $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    
    public function generateCode() {
        $code = '';
        $max = strlen($this->characters) - 1;
        
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $this->characters[mt_rand(0, $max)];
        }
        
        return $code;
    }
    
    public function createCaptchaImage($code) {
        $image = imagecreatetruecolor($this->width, $this->height);
        
        $background_color = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 20, 40, 100);
        $noise_color = imagecolorallocate($image, 100, 120, 180);
        
        // Fill background
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $background_color);
        
        // Add noise
        for ($i = 0; $i < ($this->width * $this->height) / 3; $i++) {
            imagefilledellipse($image, mt_rand(0, $this->width), mt_rand(0, $this->height), 1, 1, $noise_color);
        }
        
        // Add random lines
        for ($i = 0; $i < 5; $i++) {
            imageline($image, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $noise_color);
        }
        
        // Add the text
        $textbox = imagettfbbox(20, 0, realpath('fonts/arial.ttf'), $code);
        $x = ($this->width - $textbox[4]) / 2;
        $y = ($this->height - $textbox[5]) / 2;
        imagettftext($image, 20, 0, $x, $y, $text_color, realpath('fonts/arial.ttf'), $code);
        
        return $image;
    }
    
    public function outputCaptcha() {
        $code = $this->generateCode();
        
        // Store the code in the session for verification
        $_SESSION['captcha_code'] = $code;
        
        // Create and output the image
        $image = $this->createCaptchaImage($code);
        
        // Send appropriate headers
        header('Content-Type: image/jpeg');
        
        // Output the image
        imagejpeg($image);
        
        // Free up memory
        imagedestroy($image);
    }
    
    public function validateCaptcha($input) {
        return isset($_SESSION['captcha_code']) && $_SESSION['captcha_code'] === $input;
    }
} 