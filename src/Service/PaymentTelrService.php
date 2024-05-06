<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\UserOrderEntity;
use App\Entity\UserTransactionEntity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentTelrService extends BaseService
{
    private ParameterBagInterface $parameterBag;

    private string $storeId;

    private string $authKey;

    private string $testMode;

    private ContainerInterface $container;

    private EntityManagerInterface $entityManager;
    private TransactionService $transactionService;
    private OrderService $orderService;

    public function __construct(
        ParameterBagInterface $parameterBag,
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        TransactionService $transactionService,
        OrderService $orderService
    ) {
        $this->parameterBag = $parameterBag;

        $this->storeId = $this->parameterBag->get('app_payment_telr_store_id');
        $this->authKey = $this->parameterBag->get('app_payment_telr_auth_key');
        $this->testMode = $this->parameterBag->get('app_payment_telr_test_mode');
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->transactionService = $transactionService;
        $this->orderService = $orderService;
    }

    public function createNewTransaction(UserOrderEntity $userOrderEntity): UserTransactionEntity
    {
        $userEntity = $userOrderEntity->getUser();

        $runIndex = $this->transactionService->getRunIndex();
        $transId = $this->transactionService->generateTransId($runIndex);

        $userTransactionEntity = new UserTransactionEntity();
        $userTransactionEntity->setUser($userEntity);
        $userTransactionEntity->setOrder($userOrderEntity);
        $userTransactionEntity->setRunIndex($runIndex);
        $userTransactionEntity->setTransId($transId);
        $userTransactionEntity->setCreatedDate(new \DateTime());
        $this->entityManager->persist($userTransactionEntity);
        $this->entityManager->flush();

        $successURL = $this->generateUrl(
            'app_payment_success',
            ['id' => $userTransactionEntity->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $failureURL = $this->generateUrl(
            'app_payment_failed',
            ['id' => $userTransactionEntity->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $cancelledURL = $this->generateUrl(
            'app_payment_cancelled',
            ['id' => $userTransactionEntity->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $updateURL = $this->generateUrl(
            'app_payment_update',
            ['id' => $userTransactionEntity->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $data = [
            'ivp_method' => 'create',
            'ivp_source' => 'Sendit-v1',
            'ivp_store' => $this->storeId,
            'ivp_authkey' => $this->authKey,
            'ivp_cart' => $transId,
            'ivp_test' => $this->testMode,
            'ivp_framed' => 0,
            'ivp_amount' => $userOrderEntity->getTotalPrice(),
            'ivp_lang' => 'en',
            'ivp_currency' => 'AED',
            'ivp_desc' => 'Order ID: '.$userOrderEntity->getOrderId(),
            'return_auth' => $successURL,
            'return_can' => $cancelledURL,
            'return_decl' => $failureURL,
            'bill_custref' => $userEntity->getId(),
            'bill_fname' => $userEntity->getFirstName(),
            'bill_sname' => $userEntity->getLastName(),
            'bill_email' => $userEntity->getEmail(),
            'bill_tel' => $userEntity->getMobileNumber(),
            'ivp_update_url' => $updateURL,
        ];

        $response = $this->makeAPIRequest($data);

        //Check order reference is generated else it could be an error
        if (!isset($response['order']['ref']) || empty($response['order']['ref'])) {
            $this->entityManager->remove($userTransactionEntity);
            $this->entityManager->flush();
            var_dump($response);
            exit();
        }

        //Check order redirect url is generated else it could be an error
        if (!isset($response['order']['url']) || empty($response['order']['url'])) {
            $this->entityManager->remove($userTransactionEntity);
            $this->entityManager->flush();
            var_dump($response);
            exit();
        }

        $userTransactionEntity->setReferenceCode($response['order']['ref']);
        $userTransactionEntity->setRedirectURL($response['order']['url']);
        $this->entityManager->flush();

        return $userTransactionEntity;
    }

    public function checkTransactionStatus(UserTransactionEntity $userTransactionEntity)
    {
        $userOrderEntity = $userTransactionEntity->getOrder();

        $data = [
            'ivp_method' => 'check',
            'ivp_store' => $this->storeId,
            'ivp_authkey' => $this->authKey,
            'order_ref' => $userTransactionEntity->getReferenceCode(),
        ];

        $response = $this->makeAPIRequest($data);

        if (!array_key_exists('order', $response)) {
            $userTransactionEntity->setResponseValues($response);
            $this->entityManager->flush();
            var_dump($response);
            exit();
        }

        $paymentStatus = 'Pending';

        $orderStatusCode = $response['order']['status']['code'];
        $orderStatusText = $response['order']['status']['text'];

        $orderTransactionStatus = $response['order']['transaction']['status'];
        $orderTransactionMessage = $response['order']['transaction']['message'];

        if ('A' == $orderTransactionStatus) {
            switch ($orderStatusCode) {
                case '3':
                    $paymentStatus = 'Paid';
                    break;
                case '-1':
                    $paymentStatus = 'Failed';
                    break;
                case '-2':
                case '-3':
                    $paymentStatus = 'Cancelled';
                    break;
                default:
                    // No action defined
                    break;
            }
        }

        if ('H' == $orderTransactionStatus) {
            switch ($orderStatusCode) {
                case '2':
                    $paymentStatus = 'On-Hold';
                    break;
                default:
                    // No action defined
                    break;
            }
        }

        if ('Pending' == $paymentStatus) {
            $paymentStatus = 'Failed';
        }

        $userTransactionEntity->setPaymentStatus($paymentStatus);
        $userTransactionEntity->setResponseValues($response);

        $userTransactionEntity->setPaidAmount($response['order']['amount']);
        $userTransactionEntity->setPaidCurrency($response['order']['currency']);

        $userTransactionEntity->setStatusCode($orderStatusCode);
        $userTransactionEntity->setStatusText($orderStatusText);
        $userTransactionEntity->setTransactionStatus($orderTransactionStatus);
        $userTransactionEntity->setTransactionMessage($orderTransactionMessage);

        $userOrderEntity->setPaymentStatus($paymentStatus);
        if ('Paid' == $paymentStatus || 'On-Hold' == $paymentStatus) {
            $userOrderEntity->setPaidTransaction($userTransactionEntity);
            $userOrderEntity->setStatus('Ready');
            $this->entityManager->flush();
            $this->orderService->sendCustomerOrderEmail($userOrderEntity);
            $this->orderService->sendAdminNotificationEmail($userOrderEntity);
        }
        $this->entityManager->flush();
    }

    public function updateCancelledTransaction(UserTransactionEntity $userTransactionEntity)
    {
        $userOrderEntity = $userTransactionEntity->getOrder();

        $data = [
            'ivp_method' => 'check',
            'ivp_store' => $this->storeId,
            'ivp_authkey' => $this->authKey,
            'order_ref' => $userTransactionEntity->getReferenceCode(),
        ];

        $response = $this->makeAPIRequest($data);

        if (!array_key_exists('order', $response)) {
            $userTransactionEntity->setResponseValues($response);
            $this->entityManager->flush();
            var_dump($response);
            exit();
        }

        $orderStatusCode = $response['order']['status']['code'];
        $orderStatusText = $response['order']['status']['text'];

        switch ($orderStatusCode) {
            case '-1':
                $paymentStatus = 'Failed';
                break;
            default:
                $paymentStatus = 'Cancelled';
                break;
        }

        $userTransactionEntity->setPaymentStatus($paymentStatus);
        $userTransactionEntity->setResponseValues($response);

        $userTransactionEntity->setPaidAmount($response['order']['amount']);
        $userTransactionEntity->setPaidCurrency($response['order']['currency']);

        $userTransactionEntity->setStatusCode($orderStatusCode);
        $userTransactionEntity->setStatusText($orderStatusText);

        $userOrderEntity->setPaymentStatus($paymentStatus);
        $this->entityManager->flush();
    }

    public function makeAPIRequest($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://secure.telr.com/gateway/order.json');
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);
        $results = curl_exec($ch);
        curl_close($ch);

        return json_decode($results, true);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
}
