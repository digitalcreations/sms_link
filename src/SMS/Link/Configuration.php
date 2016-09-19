<?php
namespace DC\SMS\Link;

class Configuration {
    /**
     * @var array|string Single or multiple URLs to try to post to. If one fails, go to the next one on the list.
     */
    public $endpoint = [
        "https://wsx.sp247.net/sms"
    ];

    public $defaultSender = "2270";
    public $username;
    public $password;
    public $partnerId;
    public $platformPartnerId;

    /**
     * @var bool Set to false to bill as CPA instead of GAS.
     */
    public $isGoodsAndServices = true;
    /**
     * @var int Product Category from \DC\SMS\Link\ProductCategory
     * @see \DC\SMS\Link\ProductCategory
     */
    public $productCategory = ProductCategory::GAS_MEMBERSHIP_FEE;
}