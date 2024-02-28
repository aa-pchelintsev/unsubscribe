<?php

namespace CRM_API;

use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Model\Filter\Customers\CustomerFilter;
use RetailCrm\Api\Model\Request\Customers\CustomersRequest;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Exception\Client\BuilderException;
use RetailCrm\Api\Model\Request\Customers\CustomersEditRequest;

class CRM_API
{

    private $client;
    private $timezone;

    public function __construct($url, $apiKey)
    {
        try {
            $this->client = SimpleClientFactory::createClient($url, $apiKey);
            $settings = $this->client->settings->get();
            if ($settings !== null && isset($settings->settings->timezone->value)) {
                $this->timezone = $settings->settings->timezone->value;
            }
        } catch (BuilderException|ApiExceptionInterface $e) {
            $logger = new Custom_Logger();
            $logger->logError($e);
            exit;
        }
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function findCustomerByEmail(string $email)
    {
        $userRequest = new CustomersRequest();
        $userRequest->filter = new CustomerFilter();
        $userRequest->filter->email = $email;
        return $this->client->customers->list($userRequest);
    }

    public function editCustomer(string $customerId, CustomersEditRequest $editRequest)
    {
        return $this->client->customers->edit($customerId, $editRequest);
    }


}