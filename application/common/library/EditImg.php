<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/26 0026
 * Time: 16:09
 */
namespace app\common\library;

class EditImg{
    public function editJpg($output, $img, $arr_img = '', $arr_text = '')
    {
        //$img = @imagecreatefromjpeg($source);
        if ($arr_img) {
            foreach ($arr_img as $k => $v) {
                if(substr($v['path'], -4)=='.jpg'){
                    $temp = imagecreatefromjpeg($v['path']);
                    imagecopy ( $img, $temp, $v['x'],$v['y'], 0, 0,$v['width'],$v['height']);
                }elseif (substr($v['path'], -4)=='.png') {
                    $temp = imagecreatefrompng($v['path']);
                    imagecopy ( $img, $temp, $v['x'],$v['y'], 0, 0,$v['width'],$v['height']);
                }
            }
        }
        if ($arr_text) {
            foreach ($arr_text as $k => $v) {
                $temp=imagettftext($img, $v['size'], 0, $v['x'], $v['y'], imagecolorallocate($img, $v['rgb'][0], $v['rgb'][1], $v['rgb'][2]), $v['font'], $v['text']);
                // $temp=imagettftext($img, 20, 0, 20,100, imagecolorallocate($img, 255,255,255), 'Arial.ttf', '123');
            }
        }
imageantialias($img, true);
        imagepng($img, $output);
        //imagedestroy($img);
    }
}