<?php
namespace DC\SMS\Link;

class Gateway implements \DC\SMS\GatewayInterface {

    /**
     * @var Configuration
     */
    private $configuration;

    private $apiCaller;

    function __construct(Configuration $configuration, APICaller $apiCaller = null) {
        if (!is_array($configuration->endpoint)) {
            $configuration->endpoint = [$configuration->endpoint];
        }
        $this->configuration = $configuration;
        $this->apiCaller = isset($apiCaller) ? $apiCaller : new APICaller($configuration);
    }

    private function call(array $dataArray) {
//        print_r(json_encode($dataArray));die();
        $exception = null;
        foreach ($this->configuration->endpoint as $endpoint) {
            try {
                $json = json_encode($dataArray);
                $result = $this->apiCaller->call($json, $endpoint);
                return $result;
            } catch (GatewayException $e) {
                $exception = $e;
            }
        }
        throw new GatewayException("Could not post message after trying all endpoints. Latest exception as inner exception.", 0, $exception);
    }

    /**
     * @param \DC\SMS\TextMessageInterface $message
     * @return \DC\SMS\MessageReceiptInterface|void
     * @throws \DC\SMS\Link\GatewayException
     */
    function sendMessage(\DC\SMS\TextMessageInterface $message) {
        if ($message->getSender() == null) {
            $message->setSender($this->configuration->defaultSender);
        }

        $destination = $message->getReceiver();
        if (strpos($destination, '+') !== 0) {
            // Gateway requires destination MSISDN to start with a + sign
            $destination = '+' . $destination;
        }

        $session = [
            "source" => $message->getSender(),
            "sourceTON" => $message->getSenderTypeOfNumber(),
            "destination"=> $destination,
            "destinationTON" => "MSISDN",
            "partnerId" => $this->configuration->partnerId,
            "platformPartnerId" => $this->configuration->platformPartnerId,
            "userData" => $message->getText()
        ];

        if ($message->getSilentBilling()) {
            $session["customParameter"]["chargeOnly"] = "true"; // sic: use string "true", not boolean true
        }

        if ($message->getTTL() > 0) {
            $session["relativeValidityTime"] = $message->getTTL() * 1000; // Set in seconds, Link expects milliseconds
        }

        if ($message->getTariff() != null) {
            $session["tariff"] = $message->getTariff();
            $session["productDescription"] = $message->getProductDescription();

            if ($message->getSenderTypeOfNumber() != \DC\SMS\TypeOfNumber::SHORTNUMBER)
            {
                throw new GatewayException("SenderTypeOfNumber must be set to SHORTNUMBER for premium messages");
            }

            if ($this->configuration->isGoodsAndServices) {
                $session["productCategory"] = $this->configuration->productCategory;
            }
        }

        $result = $this->call($session);
        $json = json_decode($result);
        return new \DC\SMS\MessageReceipt($json->messageId, true, $result);
    }

    /**
     * @param string $data
     * @return \DC\SMS\DeliveryReportInterface
     */
    function parseDeliveryReport($data)
    {
        return new DeliveryReport($data);
    }
}