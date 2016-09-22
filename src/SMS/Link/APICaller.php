<?php
namespace DC\SMS\Link;


class APICaller {
    /**
     * @var Configuration
     */
    private $configuration;

    function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $data
     * @param $endpoint
     * @throws GatewayException
     * @return string
     */
    public function call($data, $endpoint) {

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("{$this->configuration->username}:{$this->configuration->password}"),
            'Length: ' . count($data)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new GatewayException(curl_error($ch), curl_errno($ch));
        }

        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200) {
            throw new GatewayException("Received HTTP error code SMS Gateway: " . $result, $info['http_code']);
        }
        curl_close($ch);

        return $result;
    }
} 