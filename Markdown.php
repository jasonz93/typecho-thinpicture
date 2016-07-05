<?php
/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 2016/7/5
 * Time: 2:53
 */
class ThinPicture_Markdown extends Markdown {
    public static function convert($text)
    {
        static $docParser, $renderer;

        if (empty($docParser)) {
            $docParser = new CommonMark_DocParser();
        }


        if (empty($renderer)) {
            $renderer = new ThinPicture_HtmlRenderer();
        }

        $doc = $docParser->parse($text);
        return $renderer->render($doc);
    }
}