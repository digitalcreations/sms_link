<?php

namespace DC\SMS\Link;

class DeliveryReport extends \DC\SMS\DeliveryReportBase {
    private $deliveryReport;
    private $rawReport;

    const LOOKUP_FINAL = 0;
    const LOOKUP_DELIVERED = 1;
    const LOOKUP_BILLED = 2;
    const LOOKUP_DESCRIPTION = 3;
    const LOOKUP_STATE = 4;

        // Format:
        // resultCode => [<final status>, <delivered>, <billed>, <descriptive text>, <Delivery State>]
    private $resultCodeLookup = [
        0    => [true,  false, false, "Unknown error", \DC\SMS\DeliveryState::UnknownError],
        1    => [true,  false, false, "Temporary routing error", \DC\SMS\DeliveryState::Unknown],
        2    => [true,  false, false, "Permanent routing error", \DC\SMS\DeliveryState::Unknown],
        3    => [true,  false, false, "Maximum throttling exceeded", \DC\SMS\DeliveryState::Rejected],
        4    => [true,  false, false, "Timeout", \DC\SMS\DeliveryState::Unknown],
        5    => [true,  false, false, "Operator unknown error", \DC\SMS\DeliveryState::Unknown],
        6    => [true,  false, false, "Operator error", \DC\SMS\DeliveryState::Unknown],
        104  => [true,  false, false, "Configuration error", \DC\SMS\DeliveryState::Rejected],
        105  => [true,  false, false, "Internal error (internal Link Mobility error)", \DC\SMS\DeliveryState::Unknown],
        1000 => [false, false, false, "Sent (to operator)", \DC\SMS\DeliveryState::Accepted],
        1001 => [true,  true,  true,  "Billed and delivered", \DC\SMS\DeliveryState::Delivered],
        1002 => [true,  false, false, "Expired", \DC\SMS\DeliveryState::Expired],
        1004 => [true,  false, false, "Mobile full", \DC\SMS\DeliveryState::Unknown],
        1006 => [true,  false, false, "Not delivered", \DC\SMS\DeliveryState::Rejected],
        1007 => [false, true,  false, "Delivered, Billed delayed", \DC\SMS\DeliveryState::Delivered],
        1008 => [false, false, true,  "Billed OK (charged OK before sending message)", \DC\SMS\DeliveryState::Accepted],
        1009 => [true,  false, true,  "Billed OK and NOT Delivered", \DC\SMS\DeliveryState::Delivered],
        1010 => [true,  false, false, "Expired, absence of operator delivery report", \DC\SMS\DeliveryState::Unknown],
        1011 => [false, false, true,  "Billed OK and sent (to operator)", \DC\SMS\DeliveryState::Accepted],
        1012 => [false, false, false, "Delayed (temporary billing error, system will try to resend)", \DC\SMS\DeliveryState::Queued],
        2104 => [true,  false, false, "Unknown subscriber", \DC\SMS\DeliveryState::Undeliverable],
        2105 => [true,  false, false, "Destination blocked (subscriber permanently barred)", \DC\SMS\DeliveryState::BarredPermanent],
        2106 => [true,  false, false, "Number error", \DC\SMS\DeliveryState::Rejected],
        2107 => [true,  false, false, "Destination temporarily blocked (subscriber temporarily barred)", \DC\SMS\DeliveryState::BarredTemporary],
        2200 => [true,  false, false, "Charging error", \DC\SMS\DeliveryState::Undeliverable],
        2201 => [true,  false, false, "Subscriber has low balance", \DC\SMS\DeliveryState::BarredZeroBalance],
        2202 => [true,  false, false, "Subscriber barred for overcharged (premium) messages", \DC\SMS\DeliveryState::BarredPremium],
        2203 => [true,  false, false, "Subscriber too young (for this particular content)", \DC\SMS\DeliveryState::BarredAge],
        2204 => [true,  false, false, "Prepaid subscriber not allowed", \DC\SMS\DeliveryState::BarredPrepaid],
        2205 => [true,  false, false, "Service rejected by subscriber", \DC\SMS\DeliveryState::BarredTemporary],
        2206 => [true,  false, false, "Subscriber not registered in payment system", \DC\SMS\DeliveryState::Rejected],
        2207 => [true,  false, false, "Subscriber has reached max balance", \DC\SMS\DeliveryState::BarredZeroBalance],
        3000 => [true,  false, false, "GSM encoding is not supported", \DC\SMS\DeliveryState::Rejected],
        3001 => [true,  false, false, "UCS2 encoding is not supported", \DC\SMS\DeliveryState::Rejected],
        3002 => [true,  false, false, "Binary encoding is not supported", \DC\SMS\DeliveryState::Rejected],
        4000 => [true,  false, false, "Delivery report is not supported", \DC\SMS\DeliveryState::Rejected],
        4001 => [true,  false, false, "Invalid message content", \DC\SMS\DeliveryState::Rejected],
        4002 => [true,  false, false, "Invalid tariff", \DC\SMS\DeliveryState::Rejected],
        4003 => [true,  false, false, "Invalid user data", \DC\SMS\DeliveryState::Rejected],
        4004 => [true,  false, false, "Invalid user data header", \DC\SMS\DeliveryState::Rejected],
        4005 => [true,  false, false, "Invalid data coding", \DC\SMS\DeliveryState::Rejected],
        4006 => [true,  false, false, "Invalid VAT", \DC\SMS\DeliveryState::Rejected],
        4007 => [true,  false, false, "Unsupported content for destination", \DC\SMS\DeliveryState::Rejected]
    ];

    function __construct($json)
    {
        $this->rawReport = $json;
        $this->deliveryReport = json_decode($json);
    }

    function getRawReport()
    {
        return $this->rawReport;
    }

    function getMessageIdentifier()
    {
        return (string)$this->deliveryReport->id;
    }

    /**
     * @return bool
     */
    function isFinalDeliveryReport()
    {
        $resultCode = $this->deliveryReport->resultCode;
        return ($this->resultCodeLookup[$resultCode][self::LOOKUP_FINAL]);
    }

    /**
     * @return bool
     */
    function isDelivered()
    {
        $resultCode = $this->deliveryReport->resultCode;
        return ($this->resultCodeLookup[$resultCode][self::LOOKUP_DELIVERED]);
    }

    /**
     * @return bool
     */
    function isBilled()
    {
        $resultCode = $this->deliveryReport->resultCode;
        return ($this->resultCodeLookup[$resultCode][self::LOOKUP_BILLED]);
    }

    /**
     * @return \DC\SMS\DeliveryState
     */
    function getState()
    {
        $resultCode = $this->deliveryReport->resultCode;
        if (isset($this->resultCodeLookup[$resultCode])) {
            return $this->resultCodeLookup[$resultCode][self::LOOKUP_STATE];
        }
        return \DC\SMS\DeliveryState::Unknown;
    }

    /**
     * @return \DC\SMS\ResponseInterface
     */
    function getSuccessfullyProcessedResponse()
    {
        return new Response(true, (string)$this->deliveryReport->id);
    }

    /**
     * @return \DC\SMS\ResponseInterface
     */
    function getErrorInProcessingResponse()
    {
        return new Response(false, (string)$this->deliveryReport->id);
    }
}