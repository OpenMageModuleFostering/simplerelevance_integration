<?php

/**
 * Simple Relevance API wrapper
 *
 * @category   SimpleRelevance
 * @package    SimpleRelevance_Integration
 */
class SimpleRelevance_Integration_Model_Api
{
    /**
     * API version number
     *
     * @var string
     */
    public $version = '3';

    /**
     * Error Message storage
     *
     * @var string
     */
    public $errorMessage;

    /**
     * Error Code storage
     *
     * @var integer
     */
    public $errorCode;

    /**
     * User API key
     *
     * @var string SimpleRelevance API key
     */
    public $api_key;

    /**
     * User site_name
     *
     * @var string SimpleRelevance site name
     */
    public $site_name;

    /**
     * This decides whether the request will wait for completion or start a task and return immediately.
     * Can be 0 (wait) or 1 (asynchronous)
     *
     * @var int
     */
    public $async = 1;

    /**
     * STS API URL
     *
     * @var string
     */
    public $apiUrl;

    /**
     * Setup data
     *
     * @param array $api_sitename_arr An array of [API key, site name]
     */
    function __construct($api_sitename_arr)
    {
        if ($api_sitename_arr[0] && $api_sitename_arr[1]) {
            $this->setUpAPI($api_sitename_arr[0], $api_sitename_arr[1]);
        }
    }

    /**
     * API key setter
     *
     * @param string $key API Key
     * @return SimpleRelevance_Integration_Model_Api
     */
    public function setUpAPI($key, $name)
    {
        $this->api_key = $key;
        $this->site_name = $name;
        $this->apiUrl = "https://www.simplerelevance.com/api/v{$this->version}/";
        return $this;
    }

    /**
     * Action: POST
     * Post purchases.
     */
    public function postPurchases($data = array())
    {
        $data_json = json_encode($data, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        return $this->_callServer('actions', 'post', $data_json);
    }

    /**
     * Action: POST
     * Post items.
     */
    public function postItems($itemData = array())
    {
        $data = array();
        $data['async'] = $this->async;
        $data['api_key'] = $this->api_key;
        $data['site_name'] = $this->site_name;
        $data['item_name'] = $itemData['item_name'];
        $data['item_id'] = $itemData['item_id'];
        $data['data_dict'] = $itemData['data_dict'];

        $data_json = json_encode($data, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        return $this->_callServer('items', 'post', $data_json);
    }

    /**
     * Action: POST
     * Post users.
     */
    public function postUsers($userData = array())
    {
        $data = array();
        $data['async'] = $this->async;
        $data['api_key'] = $this->api_key;
        $data['site_name'] = $this->site_name;
        $data['email'] = $userData['email'];
        $data['user_id'] = $userData['user_id'];

        $data_json = json_encode($data, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        return $this->_callServer('users', 'post', $data_json);
    }

    /**
     * Connect to the server and call the requested methods
     *
     * @param string $method
     * @param array OPTIONAL $payload
     * @return object|false
     */
    protected function _callServer($method, $call, $payload = array())
    {
        $this->errorMessage = null;
        $this->errorCode    = null;
        $url = $this->apiUrl . $method . '/';

        $curlSession = curl_init();

        $auth_token = base64_encode($this->site_name . ':' . $this->api_key);
        $headers = array('Authorization: Basic ' . $auth_token, 'Content-Type: application/json');

        if ($call == 'post') {
            curl_setopt($curlSession, CURLOPT_POST, true);
            curl_setopt($curlSession, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($curlSession, CURLOPT_FRESH_CONNECT, true);
        }

        curl_setopt($curlSession, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 45);
        curl_setopt($curlSession, CURLOPT_MAXREDIRS, 5);
        curl_setopt($curlSession, CURLOPT_USERAGENT, Mage::helper('simple_relevance')->getUserAgent());
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_HEADER, false);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curlSession);
        $this->_log($result);

        if (!$result) {
            $errstr = curl_error($curlSession);
            $errno = curl_errno($curlSession);
            $this->errorMessage = "Could not connect (ERR $errno: $errstr)";
            $this->errorCode = "-99";
            return false;
        }

        // Check that a connection was made
        if (curl_error($curlSession)) {
            $this->errorMessage = curl_error($curlSession);
            $this->errorCode    = "-99";
            return false;
        }

        $httpCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);

        curl_close($curlSession);

        if ($httpCode != 200 && $httpCode != 201) {
            Mage::log($result, null, 'SimpleRelevance_Errors.log');
            Mage::log($httpCode, null, 'SimpleRelevance_Errors.log');

            $this->errorMessage = $result;
            $this->errorCode    = "-99";
            return false;
        }

        return $result;
    }

    /**
     * Log data to debug log file
     *
     * @param mixed $data
     * @return void
     */
    public function _log($text)
    {
        if (!Mage::getStoreConfigFlag('simple_relevance/general/debug')) {
            return;
        }

        Mage::log($text, null, 'SimpleRelevance_Integration.log');
    }

}

?>
