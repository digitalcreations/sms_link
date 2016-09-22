<?php

namespace DC\Tests;

use DC\SMS\Link\Gateway;

class GatewayTest extends \PHPUnit_Framework_TestCase {

    private function getConfiguration() {
        $configuration = new \DC\SMS\Link\Configuration();
        $configuration->endpoint = "http://example.com/f0000";
        $configuration->username = "foo";
        $configuration->password = "bar";
        $configuration->platformId = "MyPlatformId";
        $configuration->platformPartnerId = "MyPlatformPartnerId";
        $configuration->deliveryReportGate = "abc";
        $configuration->defaultSender = "phpunit";
        return $configuration;
    }

    public function testSendMessage()
    {
        $jsonIn = '{"source":"Vegard","sourceTON":"ALPHANUMERIC","destination":"+4712345678","destinationTON":"MSISDN","platformId":"MyPlatformId","platformPartnerId":"MyPlatformPartnerId","userData":"Does this work?"}';
        $jsonOut = '{"messageId":"Dcshuhod0PMAAAFQ+/PbnR3x","resultCode":1005,"description":"Queued"}';

        $mockCaller = $this->createMock('\DC\SMS\Link\APICaller');
        $mockCaller->expects($this->once())
            ->method('call')
            ->with($this->equalTo($jsonIn))
            ->willReturn($jsonOut);

        $api = new \DC\SMS\Link\Gateway($this->getConfiguration(), $mockCaller);
        $msg = new \DC\SMS\TextMessage("Does this work?", "4712345678");
        $msg->setSender("Vegard");
        $result = $api->sendMessage($msg);
        $this->assertTrue($result->wasEnqueued());
        $this->assertEquals("Dcshuhod0PMAAAFQ+/PbnR3x", $result->getMessageIdentifier());
    }

    /**
     * @expectedException \DC\SMS\Link\GatewayException
     */
    public function testSendMessageWithTariffFailsFromAlphanumericSenderFails()
    {
        $mockCaller = $this->createMock('\DC\SMS\Link\APICaller');

        $api = new \DC\SMS\Link\Gateway($this->getConfiguration(), $mockCaller);
        $msg = new \DC\SMS\TextMessage("Does this work?", "4712345678");
        $msg->setSender("Vegard");
        $msg->setTariff(100); // 1 NOK
        $api->sendMessage($msg);
    }

    public function testSendMessageWithTariff()
    {
        $jsonIn = '{"source":"1980","sourceTON":"SHORTNUMBER","destination":"+4712345678","destinationTON":"MSISDN","platformId":"MyPlatformId","platformPartnerId":"MyPlatformPartnerId","userData":"Does this work?","tariff":100,"productDescription":null,"deliveryReportGates":["abc"],"productCategory":19}';
        $jsonOut = '{"messageId":"Dcshuhod0PMAAAFQ+/PbnR3x","resultCode":1005,"description":"Queued"}';

        $mockCaller = $this->createMock('\DC\SMS\Link\APICaller');
        $mockCaller->expects($this->once())
            ->method('call')
            ->with($this->equalTo($jsonIn))
            ->willReturn($jsonOut);

        $api = new \DC\SMS\Link\Gateway($this->getConfiguration(), $mockCaller);
        $msg = new \DC\SMS\TextMessage("Does this work?", "4712345678");
        $msg->setSender("1980");
        $msg->setSenderTypeOfNumber(\DC\SMS\TypeOfNumber::SHORTNUMBER);
        $msg->setTariff(100); // 1 NOK
        $result = $api->sendMessage($msg);

        $this->assertTrue($result->wasEnqueued());
        $this->assertEquals($jsonOut, $result->getResponseContent());
        $this->assertEquals("Dcshuhod0PMAAAFQ+/PbnR3x", $result->getMessageIdentifier());
    }

    public function testSendMessageWithTTL()
    {
        $jsonIn = '{"source":"Vegard","sourceTON":"ALPHANUMERIC","destination":"+4712345678","destinationTON":"MSISDN","platformId":"MyPlatformId","platformPartnerId":"MyPlatformPartnerId","userData":"Does this work?","relativeValidityTime":3600000}';
        $jsonOut = '{"messageId":"Dcshuhod0PMAAAFQ+/PbnR3x","resultCode":1005,"description":"Queued"}';

        $mockCaller = $this->createMock('\DC\SMS\Link\APICaller');
        $mockCaller->expects($this->once())
            ->method('call')
            ->with($this->equalTo($jsonIn))
            ->willReturn($jsonOut);

        $api = new \DC\SMS\Link\Gateway($this->getConfiguration(), $mockCaller);
        $msg = new \DC\SMS\TextMessage("Does this work?", "4712345678");
        $msg->setSender("Vegard");
        $msg->setTTL(3600); // 1 hour
        $result = $api->sendMessage($msg);

        $this->assertTrue($result->wasEnqueued());
        $this->assertEquals($jsonOut, $result->getResponseContent());
        $this->assertEquals("Dcshuhod0PMAAAFQ+/PbnR3x", $result->getMessageIdentifier());
    }

    /**
     * @expectedException \DC\SMS\Link\GatewayException
     */
    public function testSendMessageWithError() {
        $mockCaller = $this->createMock('\DC\SMS\Link\APICaller');
        $mockCaller->expects($this->once())
            ->method('call')
            ->willThrowException(new \DC\SMS\Link\GatewayException("Error", 1));

        $api = new \DC\SMS\Link\Gateway($this->getConfiguration(), $mockCaller);
        $msg = new \DC\SMS\TextMessage("Does this work?", "4712345678");
        $api->sendMessage($msg);
    }

    public function testParseDeliveryReport() {
        $api = new \DC\SMS\Link\Gateway($this->getConfiguration());
        $deliveryReport = '{"refId": "0", "id": "Dcshuhod0PMAAAFQ+/PbnR3x", "operator": "no.telenor", "sentTimestamp": "2015-11-19T09:37:35Z", "timestamp": "2015-11-19T09:37:00Z", "resultCode": 1001, "operatorResultCode": "2", "segments": 1, "gateCustomParameters": {}, "customParameters": { "received": "2015-11-19 10:37:36" }}';

        $dlr = $api->parseDeliveryReport($deliveryReport);
        $this->assertEquals("Dcshuhod0PMAAAFQ+/PbnR3x", $dlr->getMessageIdentifier());
        $this->assertEquals(\DC\SMS\DeliveryState::Delivered, $dlr->getState());
        $this->assertEquals($deliveryReport, $dlr->getRawReport());
        $this->assertTrue($dlr->isBilled());
        $this->assertTrue($dlr->isDelivered());
        $this->assertTrue($dlr->isFinalDeliveryReport());
        $this->assertFalse($dlr->isError());
    }
}
 