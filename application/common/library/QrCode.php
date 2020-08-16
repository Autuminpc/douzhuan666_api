<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/26 0026
 * Time: 13:08
 */
namespace app\common\library;

use Endroid\QrCode\QrCode as QrCodeLib;

class QrCode{

    /**
     * TODO 生成二维码
     * @author wangzw
     * @date 2018-01-26
     * @param string $url
     * @param int $size
     * @param string $label
     * @return string
     */
    public function build($url = '', $size = 300, $label = ''){
        $qrCode=new QrCodeLib();
        $qrCode->setText($url)
            ->setSize($size)//大小
            ->setLabelFontPath(VENDOR_PATH.'endroid/qrcode/assets/noto_sans.otf')
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel($label)
            ->setLabelFontSize(16);

        return $qrCode->getDataUri();
    }

    /**
     * TODO 生成二维码 输出文件
     * @author wangzw
     * @date 2018-01-26
     * @param string $url
     * @param int $size
     * @param string $label
     * @return string
     */
    public function savefile($url = '',$path='qrcode.png', $size = 300, $label = ''){
        $qrCode=new QrCodeLib();
        $qrCode->setText($url)
            ->setSize($size)//大小
            ->setLabelFontPath(VENDOR_PATH.'endroid/qrcode/assets/noto_sans.otf')
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel($label)
            ->setLabelFontSize(16);

        return $qrCode->save($path);
    }

    /**
     * TODO 生成带logo二维码 输出文件
     * @author wangzw
     * @date 2018-01-26
     * @param string $url
     * @param int $size
     * @param string $label
     * @return string
     */
    public function makeLogoQr($url = '',$path='qrcode_logo.png',$label='')
    {
        $logo = './qrlogo.png';//需要显示在二维码中的Logo图像
        $qr_temp_path='./qr_temp.png';

        $this->savefile($url,$qr_temp_path,'324',$label);

        $qr_temp = imagecreatefromstring ( file_get_contents ( $qr_temp_path ) );
        $logo = imagecreatefromstring ( file_get_contents ( $logo ) );
        $QR_width = imagesx ( $qr_temp );
        $QR_height = imagesy ( $qr_temp );
        $logo_width = imagesx ( $logo );
        $logo_height = imagesy ( $logo );
        $logo_qr_width = $QR_width / 4;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        imagecopyresampled ( $qr_temp, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height );
        imagepng ( $qr_temp, $path );
        unlink($qr_temp_path);
    }
}