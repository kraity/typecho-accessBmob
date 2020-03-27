<?php
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection DuplicatedCode */
/** @noinspection PhpIncludeInspection */
include_once Bmob_Plugin::findDir('BmobObject.class.php');
include_once 'AccessBmob_UA.php';
include_once 'AccessBmob_Page.php';

class AccessBmob_Core
{
    protected $request;
    protected $response;

    public $ua;
    public $config;
    public $action;
    public $title;
    public $logs = array();
    public $referer = array();
    public $overview = array();
    public $bmobObj;

    public function __construct()
    {
        $this->request = Typecho_Request::getInstance();
        $this->response = Typecho_Response::getInstance();
        $this->ua = new AccessBmob_UA($this->request->getAgent());
        $this->bmobObj = new BmobObject("Access");
        switch ($this->request->get('action')) {
            case 'overview':
                $this->action = 'overview';
                $this->title = _t('访问概览');
                $this->parseOverview();
                $this->parseReferer();
                break;
            case 'logs':
            default:
                $this->action = 'logs';
                $this->title = _t('访问日志');
                $this->parseLogs();
                break;
        }
    }

    protected function parseReferer()
    {
        include_once Bmob_Plugin::findDir("BmobBql.class.php");

        $bmobBql = new BmobBql();
        $this->referer['url'] = $this->bmobArray(
            $bmobBql->query(array('bql' => "select DISTINCT entrypoint AS value, COUNT(1) as count from Access group by entrypoint limit=10"))
        );
        $this->referer['domain'] = $this->bmobArray(
            $bmobBql->query(array('bql' => "select DISTINCT entrypoint_domain AS value, COUNT(1) as count from Access group by entrypoint_domain limit=10"))
        );
        $this->referer = $this->htmlEncode($this->urlDecode($this->referer));
    }

    protected function parseOverview()
    {
        # 初始化统计数组
        foreach (array('ip', 'uv', 'pv') as $type) {
            foreach (array('today', 'yesterday') as $day) {
                $this->overview[$type][$day]['total'] = 0;
            }
        }

        include_once Bmob_Plugin::findDir("BmobBql.class.php");
        $bmobBql = new BmobBql();

        foreach (array('today' => date("Y-m-d"), 'yesterday' => date("Y-m-d", strtotime('-1 day'))) as $day => $time) {

            $start = strtotime(date("{$time} 00:00:00"));
            $end = strtotime(date("{$time} 23:59:59"));

            $this->overview['ip'][$day]['total'] = $bmobBql->query(array('bql' => "SELECT DISTINCT ip, COUNT(1) AS count FROM Access where time >= {$start} AND time <= {$end}"))->count;
            $this->overview['uv'][$day]['total'] = $bmobBql->query(array('bql' => "SELECT DISTINCT uv, COUNT(1) AS count FROM Access where time >= {$start} AND time <= {$end}"))->count;
            $this->overview['pv'][$day]['total'] = $bmobBql->query(array('bql' => "SELECT COUNT(1) AS count FROM Access where time >= {$start} AND time <= {$end}"))->count;
        }

        $this->overview['ip']['all']['total'] = $bmobBql->query(array('bql' => "SELECT DISTINCT ip,COUNT(1) AS count FROM Access"))->count;
        $this->overview['uv']['all']['total'] = $bmobBql->query(array('bql' => "SELECT DISTINCT uv,COUNT(1) AS count FROM Access"))->count;
        $this->overview['pv']['all']['total'] = $bmobBql->query(array('bql' => "SELECT COUNT(1) AS count FROM Access"))->count;

    }

    function bmobArray($object)
    {
        return json_decode(json_encode($object), true)["results"];
    }

    function bmobArrays($object)
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * 判断是否是管理员登录状态
     *
     * @access public
     * @return bool
     * @throws Typecho_Exception
     */
    public function isAdmin()
    {
        $hasLogin = Typecho_Widget::widget('Widget_User')->hasLogin();
        if (!$hasLogin) {
            return false;
        }
        return Typecho_Widget::widget('Widget_User')->pass('administrator', true);
    }

    public function deleteLogs($ids)
    {
        foreach ($ids as $id) {
            $this->bmobObj->delete($id);
        }
    }

    /**
     * 生成详细访问日志数据，提供给页面渲染使用
     *
     * @access public
     * @return void
     */
    protected function parseLogs()
    {
        $type = $this->request->get('type', 1);
        $filter = $this->request->get('filter', 'all');
        $pagenum = $this->request->get('page', 1);
        $offset = (max(intval($pagenum), 1) - 1) * $this->config->pageSize;

//        $query = $this->db->select()->from('table.access_log')
//            ->order('time', Typecho_Db::SORT_DESC)
//            ->offset($offset)->limit($this->config->pageSize);
//        $qcount = $this->db->select('count(1) AS count')->from('table.access_log');

        $where = array();
        switch ($type) {
            case 1:
                $where['robot'] = 0;
                break;
            case 2:
                $where['robot'] = 1;
                break;
            default:
                break;
        }
        switch ($filter) {
            case 'ip':
                $ip = $this->request->get('ip', '');
                $ip = bindec(decbin(ip2long($ip)));
                $where['ip'] = $ip;
                break;
            case 'post':
                $cid = $this->request->get('cid', '');
                $where['content_id'] = $cid;
                break;
            case 'path':
                $path = $this->request->get('path', '');
                $where['path'] = $path;
                break;
            default:
        }

        $list = $this->bmobObj->get("",
            array(
                count($where) > 0 ? 'where=' . json_encode($where) : "",
                'limit=20',
                'skip=' . $offset,
                'order=-time'
            )
        );

        $list = $this->bmobArray($list);
        foreach ($list as &$row) {
            $row["id"] = $row["objectId"];
            $ua = new AccessBmob_UA($row['ua']);
            if ($ua->isRobot()) {
                $name = $ua->getRobotID();
                $version = $ua->getRobotVersion();
            } else {
                $name = $ua->getBrowserName();
                $version = $ua->getBrowserVersion();
            }
            if ($name == '') {
                $row['display_name'] = _t('未知');
            } else if ($version == '') {
                $row['display_name'] = $name;
            } else {
                $row['display_name'] = $name . ' / ' . $version;
            }
        }
        $this->logs['list'] = $this->htmlEncode($this->urlDecode($list));

        $filter = $this->request->get('filter', 'all');
        $filterOptions = $this->request->get($filter);

        $filterArr = array(
            'filter' => $filter,
            $filter => $filterOptions
        );

        $page = new AccessBmob_Page(20, count($list), $pagenum, 10,
            array_merge($filterArr, array(
                'panel' => AccessBmob_Plugin::$panel,
                'action' => 'logs',
                'type' => $type,
            )));
        $this->logs['page'] = $page->show();
    }

    protected function htmlEncode($data, $valuesOnly = true, $charset = 'UTF-8')
    {
        if (is_array($data)) {
            $d = array();
            foreach ($data as $key => $value) {
                if (!$valuesOnly) {
                    $key = $this->htmlEncode($key, $valuesOnly, $charset);
                }
                $d[$key] = $this->htmlEncode($value, $valuesOnly, $charset);
            }
            $data = $d;
        } elseif (is_string($data)) {
            $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
        }
        return $data;
    }

    protected function urlDecode($data, $valuesOnly = true)
    {
        if (is_array($data)) {
            $d = array();
            foreach ($data as $key => $value) {
                if (!$valuesOnly) {
                    $key = $this->urlDecode($key, $valuesOnly);
                }
                $d[$key] = $this->urlDecode($value, $valuesOnly);
            }
            $data = $d;
        } elseif (is_string($data)) {
            $data = urldecode($data);
        }
        return $data;
    }

    /**
     * 获取首次进入网站时的来源
     *
     * @access public
     * @return string
     */
    public function getEntryPoint()
    {
        $entrypoint = $this->request->getReferer();
        if ($entrypoint == null) {
            $entrypoint = Typecho_Cookie::get('__typecho_access_entrypoint');
        }
        if (parse_url($entrypoint, PHP_URL_HOST) == parse_url(Helper::options()->siteUrl, PHP_URL_HOST)) {
            $entrypoint = null;
        }
        if ($entrypoint != null) {
            Typecho_Cookie::set('__typecho_access_entrypoint', $entrypoint);
        }
        return $entrypoint;
    }

    /**
     * 记录当前访问
     *
     * @access public
     * @return void
     */
    public function writeLogs($archive = null, $url = null, $content_id = null, $meta_id = null)
    {
        if ($url == null) {
            $url = $this->request->getServer('REQUEST_URI');
        }
        $ip = $this->request->getIp();
        if ($ip == null) {
            $ip = '0.0.0.0';
        }
        $ip = bindec(decbin(ip2long($ip)));

        $entrypoint = $this->getEntryPoint();
        $referer = $this->request->getReferer();
        $time = Helper::options()->gmtTime + (Helper::options()->timezone - Helper::options()->serverTimezone);

        if ($archive != null) {
            $parsedArchive = $this->parseArchive($archive);
            $content_id = $parsedArchive['content_id'];
            $meta_id = $parsedArchive['meta_id'];
        } else {
            $content_id = is_numeric($content_id) ? $content_id : null;
            $meta_id = is_numeric($meta_id) ? $meta_id : null;
        }

        $rows = array(
            'ua' => $this->ua->getUA(),
            'browser_id' => $this->ua->getBrowserID(),
            'browser_version' => $this->ua->getBrowserVersion(),
            'os_id' => $this->ua->getOSID(),
            'os_version' => $this->ua->getOSVersion(),
            'url' => $url,
            'path' => parse_url($url, PHP_URL_PATH),
            'query_string' => parse_url($url, PHP_URL_QUERY),
            'ip' => $ip,
            'referer' => $referer,
            'referer_domain' => parse_url($referer, PHP_URL_HOST),
            'entrypoint' => $entrypoint,
            'entrypoint_domain' => parse_url($entrypoint, PHP_URL_HOST),
            'time' => $time,
            'content_id' => $content_id,
            'meta_id' => $meta_id,
            'robot' => $this->ua->isRobot() ? 1 : 0,
            'robot_id' => $this->ua->getRobotID(),
            'robot_version' => $this->ua->getRobotVersion(),
        );

        try {
            $this->bmobObj->create($rows);
        } catch (Exception $e) {

        }
    }

    /**
     * 解析archive对象
     *
     * @access public
     * @return array
     * @noinspection PhpUndefinedMethodInspection
     */
    public function parseArchive($archive)
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

    public function long2ip($long)
    {
        if ($long < 0 || $long > 4294967295) return false;
        $ip = "";
        for ($i = 3; $i >= 0; $i--) {
            $ip .= (int)($long / pow(256, $i));
            $long -= (int)($long / pow(256, $i)) * pow(256, $i);
            if ($i > 0) $ip .= ".";
        }
        return $ip;
    }

}
