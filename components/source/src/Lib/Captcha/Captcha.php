<?php

namespace Lib\Captcha;

class Captcha {

    private $_ttfPath;
    private $_captConfig = [];
    private $_image;
    private $_font = 'en';
    private $_captchaPrefix = '';

    public function __construct($config = null) {
        $this->_ttfPath = SOURCES_PATH . 'Lib' . DS . 'Captcha' . DS . 'ttf' . DS;

        if ($config !== null && is_array($config)) {
            $this->setCaptConfig($config);
        }
    }

    /**
     * 设置验证码配置
     * @param $config
     */
    public function setCaptConfig($config) {
        $this->_captConfig = array_merge($this->_captConfig, $config);
    }

    /**
     * 设置
     * @param $prefix
     */
    public function setCaptchaKeyPrefix($prefix) {
        $this->_captchaPrefix = $prefix;
    }

    /**
     * 生成验证码
     */
    public function generate() {
        session_start();

        $width = $this->_captConfig['width'];
        $height = $this->_captConfig['height'];

        $this->_image = imagecreatetruecolor($width, $height);

        $color1 = imagecolorallocate($this->_image, mt_rand(0, 100), mt_rand(0,100), mt_rand(0,100));
        $color2 = imagecolorallocate($this->_image, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100));

        $this->_gradient($color1, $color2);

        $length = $this->_captConfig['length'];

        $captcha = $this->_getCaptchaText($length);
        $x = isset($this->_captConfig['xspace'])? rand($this->_captConfig['xspace'][0],$this->_captConfig['xspace'][1]) :rand(5,15);
        $colorR = mt_rand(150, 255);
        $colorG	= mt_rand(200, 255);
        $colorB	= mt_rand(200, 255);

        $fontColor = imagecolorallocate($this->_image, $colorR,$colorG,$colorB);

        for ($i = 0, $strlen = $length; $i < $strlen; $i++) {
            $angle = mt_rand(-40, 20);
            $size = isset($this->_captConfig['fontSize'])? rand($this->_captConfig['fontSize'][0],$this->_captConfig['fontSize'][1]) :rand(12,14);
            $font = $this->getFont();
            $char = mb_substr($captcha, $i, 1);
            $box = \imageftbbox($size, $angle, $font,$char);
            $y = $this->_captConfig['height'] / 2 + ($box[2] - $box[5]) / 4;
            imagefttext($this->_image, $size, $angle, $x, $y, $fontColor, $font, $char);
            $x += $box[2]+10;
        }

        $level = $this->_captConfig['level'];
        if ($level >1)
            $this->_line($colorR,$colorG,$colorB);
        if ($level >2)
            $this->_setWarping();
        if ($level >3)
            $this->_circle();

        $_SESSION[$this->_captchaPrefix . '_captcha_code'] = sha1(strtolower($captcha));

        header('Content-Type: image/png');
        imagepng($this->_image);
        imagedestroy($this->_image);
    }

    /**
     * 验证码验证
     * @param $captcha
     * @return bool
     */
    public function verifyCaptcha($captcha) {
        session_start();
        if (isset($_SESSION[$this->_captchaPrefix . '_captcha_code']) && sha1(strtolower($captcha)) == $_SESSION[$this->_captchaPrefix . '_captcha_code']) {
            unset($_SESSION[$this->_captchaPrefix . '_captcha_code']);
            return true;
        }

        return false;
    }

    /**
     * 获取字体
     * @return string
     */
    private function getFont() {
        if (isset($this->_captConfig['fonts'])) {
            $key = array_rand($this->_captConfig['fonts']);
            return $this->_ttfPath . $this->_captConfig['fonts'][$key] . '.ttf';
        } else {
            return $this->_ttfPath . $this->_font . '.ttf';
        }
    }

    /**
     * 快速获取验证码文本
     * @param $length
     * @return string
     */
    private function _getCaptchaText($length) {
        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    /**
     * 随机颜色
     * @param $color1
     * @param $color2
     */
    private function _gradient($color1, $color2) {

        $color1 = imagecolorsforindex($this->_image, $color1);
        $color2 = imagecolorsforindex($this->_image, $color2);
        $steps  = $this->_captConfig['width'];

        $r1 	= ($color1['red'] - $color2['red']) / $steps;
        $g1 	= ($color1['green'] - $color2['green']) / $steps;
        $b1 	= ($color1['blue'] - $color2['blue']) / $steps;

        $x1 	= & $i;
        $y1 	= 0;
        $x2 	= & $i;
        $y2 = $this->_captConfig['height'];

        for ($i = 0; $i <= $steps; $i++) {
            $r2 = $color1['red'] - floor($i * $r1);
            $g2 = $color1['green'] - floor($i * $g1);
            $b2 = $color1['blue'] - floor($i * $b1);
            $color = imagecolorallocate($this->_image, $r2, $g2, $b2);
            imageline($this->_image, $x1, $y1, $x2, $y2, $color);
        }
    }

    private function _line($colorR,$colorG,$colorB) {
        $A = mt_rand(4,8);  // 振幅
        $f = mt_rand(3,5);  // X轴方向偏移量
        $w = 0.05;

        $px1 = isset($this->_captConfig['xspace'])? rand($this->_captConfig['xspace'][0],$this->_captConfig['xspace'][1]) :rand(5,15);  // 曲线横坐标起始位置
        $px2 = round($this->_captConfig['width'] -$px1);  // 曲线横坐标结束位置
        for ($px=$px1; $px<=$px2; $px=$px+ 0.9) {
            if ($w!=0) {
                $py = $A * sin($w*$px + $f)+ $this->_captConfig['height']/2;  // y = Asin(ωx+φ)
                $i = (int) ((15 - 6)/4);
                while ($i > 0) {
                    imagesetpixel($this->_image, $px + $i, $py + $i, imagecolorallocate($this->_image, $colorR, $colorG, $colorB));  // 这里画像素点比imagettftext和imagestring性能要好很多
                    $i--;
                }
            }
        }
    }

    private function _setWarping() {
        $rgb		= array();
        $direct		= rand(-4,-2);
        $width 		= imagesx($this->_image);
        $height 	= imagesy($this->_image);

        for($j = 0;$j < $height;$j++)  {
            for($i = 0;$i < $width;$i++)  {
                $rgb[$i] = imagecolorat($this->_image, $i , $j);
            }

            for($i = 20;$i < $width;$i++) {
                $r = sin($j / $height * 2 * M_PI - M_PI * 0.7) * $direct;
                imagesetpixel($this->_image, $i+$r, $j, $rgb[$i]);
            }
        }
    }

    private function _circle() {
        for ($i = 0, $count = mt_rand(10, 14); $i < $count; $i++) { //随机圆圈
            $color 	= imagecolorallocatealpha($this->_image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(50, 120));
            $size 	= mt_rand(7, $this->_captConfig['height'] / 3);
            imagefilledellipse($this->_image, mt_rand(0, $this->_captConfig['width']), mt_rand(0,$this->_captConfig['height']), $size, $size, $color);
        }
    }
}