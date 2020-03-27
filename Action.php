<?php
include_once 'AccessBmob_Core.php';

class AccessBmob_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $access;

    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);
        $this->access = new AccessBmob_Core();
    }

    public function execute()
    {
    }

    public function action()
    {
    }

    public function markLog()
    {
        $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAQUAP8ALAAAAAABAAEAAAICRAEAOw==');
        $this->response->setContentType('image/gif');
        $this->access->writeLogs(null, $this->request->u, $this->request->cid, $this->request->mid);
        echo $image;
    }

    /** @noinspection DuplicatedCode
     * @noinspection PhpUndefinedVariableInspection
     */
    public function ip()
    {
        $ip = $this->request->get('ip');
        try {
            $http = Typecho_Http_Client::get();
            $result = $http->send('https://tools.keycdn.com/geo.json?host=' . $ip);
            $result = Json::decode($result, true);
            if ($result['status'] == 'success') {
                $response = array(
                    'code' => 0,
                    'data' => $result['data']['geo']['country_name'] . ' ' . $result['data']['geo']['city'],
                );
            }
        } catch (Exception $e) {
            $response = array(
                'code' => 100,
                'data' => '很抱歉，ipip.net查询无结果，同时你的服务器无法连接fallback接口(tools.keycdn.com)',
            );
        }
        $this->response->throwJson($response);
    }

    /** @noinspection DuplicatedCode */
    public function deleteLogs()
    {
        try {
            $this->checkAuth();
            $data = @file_get_contents('php://input');
            $data = Json::decode($data, true);
            if (!is_array($data)) {
                throw new Exception('params invalid');
            }
            $this->access->deleteLogs($data);
            $response = array(
                'code' => 0,
            );

        } catch (Exception $e) {
            $response = array(
                'code' => 100,
                'data' => $e->getMessage(),
            );
        }

        $this->response->throwJson($response);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    protected function checkAuth()
    {
        if (!$this->access->isAdmin()) {
            throw new Exception('Access Denied');
        }
    }

}
