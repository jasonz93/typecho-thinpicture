<?php
/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 2016/7/5
 * Time: 11:34
 */

require_once 'aliyun-oss-php-sdk-2.0.7.phar';

class ThinPicture_Process implements Widget_Interface_Do 
{

    public function execute() {

    }

    /**
     * 接口需要实现的入口函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $orig = $_GET['orig'];
        if (!$orig) return;

        $ch = curl_init($orig);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);

        if (!$data) {
            header('HTTP/1.0 404 Not Found');
            return;
        }
//        echo $data;
        $info = getimagesizefromstring($data);
        $width = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->width;
        $height = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->height;
        if (($width && $info[0] > $width) || ($height && $info[1] > $height)) {
            $resized = $this->resize($data, $info[0], $info[1], $width, $height);
            $this->store($orig, $resized);
            return;
        } else {
            $this->store($orig, null, $info);
        }

        header('Content-Type: '.$info['mime']);
        echo $data;
    }

    public function resize($srcData, $srcWidth, $srcHeight, $width, $height) {
        $src = imagecreatefromstring($srcData);
        $ratio = $srcWidth / $srcHeight;
        if ($srcWidth > $width) {
            $dstWidth = $width;
            $dstHeight = $width / $ratio;
        } else {
            $dstWidth = $height * $ratio;
            $dstHeight = $height;
        }
        if ($dstHeight > $height) {
            $dstWidth = $height * $ratio;
            $dstHeight = $height;
        }
        $dst = imagecreatetruecolor($dstWidth, $dstHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

        header('Content-Type: image/jpeg');
        imagejpeg($dst);
        imagedestroy($src);
        imagedestroy($dst);

        return ob_get_contents();
    }

    public function store($orig, $resized = null, $thinInfo = null) {
        if ($resized) {
            $ossExternal = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->ossExternal;
            $ossExternal = rtrim($ossExternal, ' /');
            $ossInternal = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->ossInternal;
            $bucket = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->bucket;
            $accessKeyID = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->accessKeyID;
            $accessKeySecret = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->accessKeySecret;
            $prefix = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->prefix;
            $prefix = trim($prefix, ' /');

            $filename = time() . uniqid();

            $ossClient = new \OSS\OssClient($accessKeyID, $accessKeySecret, $ossInternal);
            $ossClient->putObject($bucket, "$prefix/$filename", $resized);
            $thinUrl = "http://$ossExternal/$prefix/$filename";
            $thinInfo = getimagesizefromstring($resized);
        } else {
            $thinUrl = $orig;
        }

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $hash = md5($orig);
        $query = $db->insert($prefix.'thinpicture')
            ->expression('hash', "'$hash'")
            ->expression('thin_url', "'$thinUrl'", false)
            ->expression('thin_width', $thinInfo[0])
            ->expression('thin_height', $thinInfo[1])
            ->expression('create_time', time());
        $db->query($query);
    }
}