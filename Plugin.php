<?php
/**
 * ThinPicture
 *
 * @package ThinPicture
 * @author Nicholas
 * @version 0.0.1
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class ThinPicture_Plugin implements Typecho_Plugin_Interface
{

    /**
     * 启用插件方法,如果启用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        self::install();
        Typecho_Plugin::factory('Widget_Abstract_Contents')->markdown = array('ThinPicture_Plugin', 'markdown');
        Helper::addRoute('ThinPicture', '/thinpicture', 'ThinPicture_Process', 'action');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute('ThinPicture');
    }

    /**
     * 获取插件配置面板
     *
     * @static
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $width = new Typecho_Widget_Helper_Form_Element_Text('width', NULL, '1280', '超过此宽度的图片将被压缩');
        $form->addInput($width);
        $height = new Typecho_Widget_Helper_Form_Element_Text('height', NULL, '2048', '超过此高度的图片将被压缩');
        $form->addInput($height);
        $ossExternal = new Typecho_Widget_Helper_Form_Element_Text('ossExternal', NULL, '', 'OSS访问域名');
        $form->addInput($ossExternal);
        $ossInternal = new Typecho_Widget_Helper_Form_Element_Text('ossInternal', NULL, '', 'OSS上传域名');
        $form->addInput($ossInternal);
        $bucket = new Typecho_Widget_Helper_Form_Element_Text('bucket', NULL, '', 'Bucket');
        $form->addInput($bucket);
        $accessKeyID = new Typecho_Widget_Helper_Form_Element_Text('accessKeyID', NULL, '', 'Access Key ID');
        $form->addInput($accessKeyID);
        $accessKeySecret = new Typecho_Widget_Helper_Form_Element_Text('accessKeySecret', NULL, '', 'Access Key Secret');
        $form->addInput($accessKeySecret);
        $prefix = new Typecho_Widget_Helper_Form_Element_Text('prefix', NULL, '', '小图存放路径');
        $form->addInput($prefix);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function install() {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        $scripts = file_get_contents(__DIR__.'/'.$type.'.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho_Db::WRITE);
                }
            }
            return 'Success';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && (1050 == $code || '42S01' == $code)) ||
                'SQLite' == $type && ('HY000' == $code || 1 == $code)) {
                try {
                    $script = 'SELECT `hash`, `thin_url`, `thin_width`, `thin_height`, `create_time` FROM `' . $prefix . 'thinpicture`';
                    $db->query($script);
                    return 'Success with exist table.';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if (('Mysql' == $type && 1054 == $code) ||
                        ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                        return self::upgrade();
                    }
                    throw new Typecho_Plugin_Exception('Error when detecting table. Code: '.$code);
                }
            }
            throw new Typecho_Plugin_Exception('Error when creating table. Code: '.$code);
        }
    }

    public static function upgrade() {

    }
    
    public static function markdown($text) {
        return ThinPicture_Markdown::convert($text);
    }
}