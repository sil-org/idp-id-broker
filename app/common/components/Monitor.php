<?php

namespace common\components;

use GuzzleHttp\Client;
use yii\base\Component;

class Monitor extends Component
{
    /**
     * @var null|string the URL to request for a heartbeat check
     */
    public $heartbeatUrl = '';

    /**
     * @var null|string the HTTP request method (verb) to use for a heartbeat check
     */
    public $heartbeatMethod = '';

    public function Heartbeat()
    {
        if (empty($this->heartbeatUrl)) {
            return;
        }

        $client = new Client();

        $method = 'POST';
        if (!empty($this->heartbeatMethod)) {
            $method = $this->heartbeatMethod;
        }

        try {
            $client->request($method, $this->heartbeatUrl);
        } catch (\Throwable $e) {
            \Yii::error('heartbeat error: ' . $e->getMessage());
        }
    }
}
