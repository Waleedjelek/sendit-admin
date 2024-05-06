<?php

namespace App\Controller\Api;

use App\Classes\Controller\AuthenticatedAPIController;
use App\Classes\Exception\APIException;
use App\Service\OrderService;
use App\Service\TransactionService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/transactions")
 */
class UserTransactionController extends AuthenticatedAPIController
{
    /**
     * @Route("/list", methods={"POST"}).
     */
    public function list(
        TransactionService $transactionService,
        OrderService $orderService
    ): Response {
        $userEntity = $this->getUser();

        $userTransactions = $transactionService->getUserTransactions($userEntity);

        $data['transactions'] = [];
        if (!empty($userTransactions)) {
            foreach ($userTransactions as $transaction) {
                $transInfo = $transactionService->getTransactionArray($transaction);
                $transInfo['order'] = $orderService->getOrderArray($transaction->getOrder(), $this->getAssetURL());
                $data['transactions'][] = $transInfo;
            }
        }

        return $this->dataJson($data);
    }

    /**
     * @Route("/get", methods={"POST"}).
     *
     * @throws APIException
     */
    public function getInfo(
        TransactionService $transactionService,
        OrderService $orderService
    ): Response {
        $userEntity = $this->getUser();

        $transactionId = $this->getRequiredVar('transactionId');

        $userTransactionEntity = $transactionService->getTransactionById($transactionId, $userEntity);
        if (is_null($userTransactionEntity)) {
            throw new APIException('Invalid transaction', 101);
        }

        $data['transaction'] = $transactionService->getTransactionArray($userTransactionEntity);
        $data['transaction']['order'] = $orderService->getOrderArray($userTransactionEntity->getOrder(), $this->getAssetURL());

        return $this->dataJson($data);
    }
}
