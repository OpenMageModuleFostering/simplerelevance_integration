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
     * @param string $name Site name
     * @return SimpleRelevance_Integration_Model_Api
     */
    public function setUpAPI($key, $name)
    {
        $this->api_key = $key;
        $this->site_name = $name;
        $this->apiUrl = "https://api.simplerelevance.com/api/v{$this->version}/";
        return $this;
    }

    /**
     * Action: POST
     * Post purchases.
     * @param array $data Data to POST.
     * @param bool $batch Whether to run in batch mode.
     * @return bool|mixed API call result or false on error.
     */
    public function postPurchases($data = array(), $batch = false)
    {
        if ($batch) {
            $post_data = array('batch' => $data, 'async' => $this->async);
            $data_json = json_encode($post_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        }
        else {
            $data_json = json_encode($data, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        }
        return $this->_callServer('actions', 'post', $data_json);
    }

    /**
     * Action: POST
     * Post items.
     * @param array $itemData Data to POST.
     * @param bool $batch Whether to run in batch mode.
     * @return bool|mixed API call result or false on error.
     */
    public function postItems($itemData = array(), $batch = false)
    {
        $data = array();
        $data['async'] = $this->async;
        $data['api_key'] = $this->api_key;
        $data['site_name'] = $this->site_name;

        if ($batch) {
            $data['batch'] = array();
            foreach ($itemData as $item) {
                $data['batch'][] = $item;
            }
            $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        } else {
            $data['item_name'] = $itemData['item_name'];
            $data['item_id'] = $itemData['item_id'];
            $data['data_dict'] = $itemData['data_dict'];
            $data_json = json_encode($data, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        }

        return $this->_callServer('items', 'post', $data_json);
    }

    /**
     * Action: POST
     * Post users.
     * @param array $userData Data to POST.
     * @param bool $batch Whether to run in batch mode.
     * @return bool|mixed API call result or false on error.
     */
    public function postUsers($userData = array(), $batch = false)
    {
        $data = array();
        $data['async'] = $this->async;
        $data['api_key'] = $this->api_key;
        $data['site_name'] = $this->site_name;

        if ($batch) {
            $data['batch'] = array();
            foreach ($userData as $user) {
                $data['batch'][] = $user;
            }
            $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        } else {
            $data['email'] = $userData['email'];
            $data['user_id'] = $userData['user_id'];
            $data_json = json_encode($data, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        }

        return $this->_callServer('users', 'post', $data_json);
    }

    /**
     * Main API call function.
     *
     * @param string $method API method to call, i.e. 'actions'
     * @param string $call API HTTP method, i.e. 'post'
     * @param array $payload Optional payload to send to the API
     * @return bool|mixed Returns boolean false or on success, the API result
     */
    protected function _callServer($method, $call, $payload = array())
    {
        try {
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
            curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true); // follow redirects
            curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, 5); // number of seconds to wait while trying to connect
            curl_setopt($curlSession, CURLOPT_TIMEOUT, 5); // max number of seconds to allow cURL functions to execute before timing out and giving up
            curl_setopt($curlSession, CURLOPT_MAXREDIRS, 5); // follow up to 5 redirects
            curl_setopt($curlSession, CURLOPT_USERAGENT, Mage::helper('simple_relevance')->getUserAgent());
            curl_setopt($curlSession, CURLOPT_URL, $url);
            curl_setopt($curlSession, CURLOPT_HEADER, false); // don't include the header in the output
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

        catch (Exception $e) {
            return false; // catch-all
        }
    }

    /**
     * Log text to debug log file
     *
     * @param mixed $text Text to log
     * @return void
     */
    public function _log($text)
    {
        if (!Mage::getStoreConfigFlag('simple_relevance/general/debug')) {
            return; // if the debug flag is false in the config, do nothing
        }

        Mage::log($text, null, 'SimpleRelevance_Integration.log');
    }

}
?>
