<?php

namespace CSV_Functions;


use RetailCrm\Api\Model\Request\Customers\CustomersEditRequest;
use RetailCrm\Api\Enum\ByIdentifier;
use RetailCrm\Api\Model\Entity\Customers\Customer;
use DateTime;
use DateTimeZone;


class CSV_Functions
{
    private $api;
    private $logger;

    public function __construct($api, $logger)
    {
        $this->api = $api;
        $this->logger = $logger;
    }

    public function processCSVFile($filename, $timezone)
    {
        if (($handle = fopen($filename, "r")) !== FALSE) {
            $lineNumber = 1;
            while (($data = fgetcsv($handle)) !== FALSE) {
                $email = $data[0];
                if (!empty($email)) {
                    $this->processEmail($email, $lineNumber, $timezone);
                } else {
                    $this->logger->logResult->info("Артефакт. В строке $lineNumber. Не было обнаружено адреса");
                }
                $lineNumber++;
            }
            fclose($handle);
        }
    }

    private function processEmail($email, $lineNumber, $timezone)
    {
        $userResponse = $this->api->findCustomerByEmail($email);
        if (!empty($userResponse->customers)) {
            $customer = $userResponse->customers[0];
            if ($customer->emailMarketingUnsubscribedAt == null) {
                $editRequest = new CustomersEditRequest();
                $editRequest->customer = new Customer();
                $editRequest->by = ByIdentifier::ID;
                $editRequest->site = $customer->site;
                $emailUnsubscribeDate = new DateTime("now", new DateTimeZone($timezone));
                $editRequest->customer->emailMarketingUnsubscribedAt = $emailUnsubscribeDate;
                $this->api->editCustomer($customer->id, $editRequest);
                $this->logger->logSuccess("Успех. $email в строке $lineNumber. Клиент с id $customer->id был успешно отписан");
            } else {
                $this->logger->logSuccess("Без изменений. $email в строке $lineNumber. Клиент с id $customer->id был отписан ранее");
            }
        } else {
            $this->logger->logSuccess("Не найден. $email в строке $lineNumber. Клиент с указанным адресом не найден в CRM");
        }
    }
}