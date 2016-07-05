<?php

/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 2016/7/5
 * Time: 2:45
 */

class ThinPicture_HtmlRenderer extends CommonMark_HtmlRenderer
{
    private $width = null;
    private $height = null;

    public function renderInline(CommonMark_Element_InlineElementInterface $inline) {
        if ($inline->getType() == CommonMark_Element_InlineElement::TYPE_SOFTBREAK) {
            $inline->setType(CommonMark_Element_InlineElement::TYPE_HARDBREAK);
        }
        if ($inline->getType() != CommonMark_Element_InlineElement::TYPE_IMAGE) {
            return parent::renderInline($inline);
        }

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        $orig = $this->escape($inline->getAttribute('destination'), true);
        $origHash = md5($orig);
        $sql = "SELECT * FROM `{$prefix}thinpicture` WHERE `hash` = '$origHash'";
        $thin = $db->fetchRow($sql);

        if ($this->width == null) $this->width = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->width;
        if ($this->height == null)  $this->height = Typecho_Widget::widget('Widget_Options')->plugin('ThinPicture')->height;

        if ($thin && ($this->width && $thin['thin_width'] <= $this->width) && ($this->height && $thin['thin_height'] <= $this->height)) {
            $attrs['src'] = $thin['thin_url'];
        } else {
            if ($thin) {
                $db->query("DELETE FROM {$prefix}thinpicture WHERE `hash` = '$origHash'");
            }
            $attrs['src'] = 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png';
            $attrs['src'] = Typecho_Router::url('ThinPicture') . '?orig=' . urlencode($orig);
        }
        $attrs['alt'] = $this->escape($this->renderInlines($inline->getAttribute('label')));
        if ($title = $inline->getAttribute('title')) {
            $attrs['title'] = $this->escape($title, true);
        }

        $linkAttrs = [
            'href' => $orig,
            'title' => '点击查看大图',
            'target' => '_blank'
        ];

        $img = $this->inTags('img', $attrs, '', true);
        return $this->inTags('a', $linkAttrs, $img);
    }
}