<?php
/**
 * AccessBmob 基于 Access 且使用 Bmob后端云 作数据库的访问统计插件
 * @package AccessBmob
 * @author 权那他
 * @version 1.0
 */

/** @noinspection PhpUndefinedFieldInspection */

class AccessBmob_Plugin implements Typecho_Plugin_Interface
{
    public static $panel = 'AccessBmob/page/console.php';

    // 激活插件

    /** @noinspection PhpIncludeInspection */
    public static function activate()
    {
        Helper::addPanel(1, self::$panel, _t('统计访问'), _t('统计访问控制台'), 'subscriber');
        Helper::addRoute("accessBmob_track", "/accessBmob/log/track", "AccessBmob_Action", 'markLog');
        Helper::addRoute("accessBmob_ip", "/accessBmob/ip.json", "AccessBmob_Action", 'ip');
        Helper::addRoute("accessBmob_delete_logs", "/accessBmob/log/delete.json", "AccessBmob_Action", 'deleteLogs');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('AccessBmob_Plugin', 'frontend');

        $all = Typecho_Plugin::export();
        $able = array_key_exists('Bmob', $all['activated']);
        if ($able) {
            include_once Bmob_Plugin::findDir("BmobObject.class.php");
            $bmobObj = new BmobObject("Access");
            $bmobObj->create(array("ua" => "Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)"));
        }

        return _t('插件已经激活，需先配置插件信息！' . ($able ? "" : "但是没有启用Bmob插件"));
    }

    // 禁用插件
    public static function deactivate()
    {
        Helper::removePanel(1, self::$panel);
        Helper::removeRoute("accessBmob_track");
        Helper::removeRoute("accessBmob_ip");
        Helper::removeRoute("accessBmob_delete_logs");
        return _t('插件已被禁用');
    }

    public static function frontend($archive)
    {
        $all = Typecho_Plugin::export();
        if (array_key_exists('Bmob', $all['activated'])) {
            $index = rtrim(Helper::options()->index, '/');
            $parsedArchive = AccessBmob_Plugin::staticParseArchive($archive);
            echo "<script type=\"text/javascript\">(function(w){var t=function(){var i=new Image();i.src='{$index}/accessBmob/log/track?u='+location.pathname+location.search+location.hash+'&cid={$parsedArchive['content_id']}&mid={$parsedArchive['meta_id']}&rand='+new Date().getTime()};t();var a={};a.track=t;w.Access=a})(this);</script>";
        }
    }

    /** @noinspection PhpUndefinedMethodInspection
     * @noinspection DuplicatedCode
     * @param $archive
     * @return array
     */
    public static function staticParseArchive($archive)
    {
        // 暂定首页的meta_id为0
        $content_id = null;
        $meta_id = null;
        if ($archive->is('index')) {
            $meta_id = 0;
        } elseif ($archive->is('post') || $archive->is('page')) {
            $content_id = $archive->cid;
        } elseif ($archive->is('tag')) {
            $meta_id = $archive->tags[0]['mid'];
        } elseif ($archive->is('category')) {
            $meta_id = $archive->categories[0]['mid'];
        }

        return array(
            'content_id' => $content_id,
            'meta_id' => $meta_id,
        );
    }

    // 插件配置面板
    public static function config(Typecho_Widget_Helper_Form $form)
    {

    }

    // 个人用户配置面板
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function form($action = NULL)
    {
    }
}
