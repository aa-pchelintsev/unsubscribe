<?php
require_once 'vendor/autoload.php';
use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Model\Filter\Customers\CustomerFilter;
use RetailCrm\Api\Model\Request\Customers\CustomersRequest;
use RetailCrm\Api\Model\Request\Customers\CustomersEditRequest;
use RetailCrm\Api\Exception\Client\BuilderException;
use RetailCrm\Api\Enum\ByIdentifier;
use RetailCrm\Api\Model\Entity\Customers\Customer;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

$dateFormat = "Y:m:d, H:i:s e";
$output = "%datetime% > %message%\n";
$formatter = new LineFormatter($output, $dateFormat);
$streamResult = new StreamHandler("operationResult.log", Level::Info);
$streamResult->setFormatter($formatter);
$logResult = new Logger('operationResult');
$logResult->pushHandler($streamResult);

$formatterError = new LineFormatter(null,null,false,false,true);
$streamError = new StreamHandler("errors.log", Level::Error);
$streamError->setFormatter($formatterError);
$logError = new Logger('error');
$logError->pushHandler($streamError);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$url = $_ENV['API_URL'];
$apiKey = $_ENV['API_KEY'];

try {
    $client = SimpleClientFactory::createClient($url, $apiKey);
    $userSettings = $client->settings->get();
    $userSettingsTimeZone = $userSettings->settings->timezone->value;
} catch (BuilderException|ApiExceptionInterface $e) {
    $logError->error($e);
    exit;
}

if (($handle = fopen("book1.csv", "r")) !== FALSE) {
    $lineNumber = 1;
    while (($data = fgetcsv($handle)) !== FALSE) {
        $email = $data[0];
        if (!empty($email)) {
            $userRequest = new CustomersRequest();
            $userRequest->filter = new CustomerFilter();
            $userRequest->filter->email = $email;
            $userResponse = $client->customers->list($userRequest);
            if (!empty($userResponse->customers)) {
                $customer = $userResponse->customers[0];
                if ($customer->emailMarketingUnsubscribedAt == null) {
                    $editRequest = new CustomersEditRequest();
                    $editRequest->customer = new Customer();
                    $editRequest->by = ByIdentifier::ID;
                    $editRequest->site = $customer->site;
                    $emailUnsubscribeDate = new DateTime("now", new DateTimeZone($userSettingsTimeZone));
                    $editRequest->customer->emailMarketingUnsubscribedAt = $emailUnsubscribeDate;
                    $userResponse = $client->customers->edit($customer->id, $editRequest);
                    $logResult->info("Успех. $email в строке $lineNumber. Клиент с id $customer->id был успешно отписан");
                } else {
                    $logResult->info("Без изменений. $email в строке $lineNumber. Клиент с id $customer->id был отписан ранее");
                }
            } else {
                $logResult->info("Не найден. $email в строке $lineNumber. Клиент с указанным адресом не найден в CRM");
            }
        } else {
            $logResult->info("Артефакт. В строке $lineNumber. Не было обнаружено адреса");
        }
        $lineNumber++;
    }
    fclose($handle);
}
$logResult->close();
$logError->close();
