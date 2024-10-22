<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\UserOrderEntity;
use App\Entity\UserTransactionEntity;
use App\Service\PaymentTelrService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/payment")
 */
class PaymentController extends AdminController
{
    /**
     * @Route("/order/{id}/do-payment", name="app_payment_redirect", methods={"GET"})
     */
    public function paymentRedirect(
        Request $request,
        UserOrderEntity $userOrderEntity,
        PaymentTelrService $paymentTelrService
    ): Response {
        if ('Draft' != $userOrderEntity->getStatus()) {
            return $this->redirect($userOrderEntity->getSuccessRedirectURL());
        }

        $userTransactionEntity = $paymentTelrService->createNewTransaction($userOrderEntity);

        return $this->redirect($userTransactionEntity->getRedirectURL());
    }

    /**
     * @Route("/transaction/{id}/success", name="app_payment_success", methods={"GET"})
     */
    public function transactionSuccess(
        Request $request,
        UserTransactionEntity $userTransactionEntity,
        PaymentTelrService $paymentTelrService
    ): Response {
        $paymentTelrService->checkTransactionStatus($userTransactionEntity);

        $userOrderEntity = $userTransactionEntity->getOrder();
        $redirectURL = $userOrderEntity->getSuccessRedirectURL().'?transactionId='.$userTransactionEntity->getId();

        return $this->redirect($redirectURL);
    }

    /**
     * @Route("/transaction/{id}/failed", name="app_payment_failed", methods={"GET"})
     */
    public function transactionFailed(
        Request $request,
        UserTransactionEntity $userTransactionEntity,
        PaymentTelrService $paymentTelrService
    ): Response {
        $paymentTelrService->checkTransactionStatus($userTransactionEntity);

        $userOrderEntity = $userTransactionEntity->getOrder();
        $redirectURL = $userOrderEntity->getFailureRedirectURL().'?transactionId='.$userTransactionEntity->getId();

        return $this->redirect($redirectURL);
    }

    /**
     * @Route("/transaction/{id}/cancelled", name="app_payment_cancelled", methods={"GET"})
     */
    public function transactionCancelled(
        Request $request,
        UserTransactionEntity $userTransactionEntity,
        PaymentTelrService $paymentTelrService
    ): Response {
        $paymentTelrService->updateCancelledTransaction($userTransactionEntity);

        $userOrderEntity = $userTransactionEntity->getOrder();
        $redirectURL = $userOrderEntity->getFailureRedirectURL().'?transactionId='.$userTransactionEntity->getId();

        return $this->redirect($redirectURL);
    }

    /**
     * @Route("/transaction/{id}/update", name="app_payment_update", methods={"POST"})
     */
    public function transactionUpdate(
        Request $request,
        UserTransactionEntity $userTransactionEntity,
        PaymentTelrService $paymentTelrService
    ): Response {
        $postbackValue = $request->request->all();

        $previousPostValue = $userTransactionEntity->getPostBackValues();
        $previousPostValue[] = $postbackValue;
        $userTransactionEntity->setPostBackValues($previousPostValue);

        $paymentStatus = 'Failed';
        $userOrderEntity = $userTransactionEntity->getOrder();
        $transactionType = $request->get('tran_type');
        $transactionStatus = $request->get('tran_authstatus');
        $transactionAmount = $request->get('tran_amount');
        if ('A' == $transactionStatus) {
            switch ($transactionType) {
                case '1':
                case '4':
                case '7':
                    $paymentStatus = 'Paid';
                    break;
                case '2':
                case '6':
                case '8':
                    $paymentStatus = 'Cancelled';
                    break;
                case '3':
                    $paymentStatus = 'Refunded';
                    break;
                default:
                    // No action defined
                    break;
            }
        }

        if ('Failed' == $paymentStatus || 'Cancelled' == $paymentStatus) {
            if ('Draft' == $userOrderEntity->getStatus()) {
                $userOrderEntity->setStatus('Unconfirmed');
            }
            if ('Draft' != $userOrderEntity->getStatus()) {
                $userOrderEntity->setStatus('Cancelled');
            }
            $userOrderEntity->setPaymentStatus($paymentStatus);
            $userTransactionEntity->setPaymentStatus($paymentStatus);
        }

        if ('Paid' == $paymentStatus) {
            $userOrderEntity->setStatus('Ready');
            $userOrderEntity->setPaymentStatus($paymentStatus);
            $userTransactionEntity->setPaymentStatus($paymentStatus);
        }

        if ('Refunded' == $paymentStatus) {
            $previousRefundAmount = $userTransactionEntity->getRefundAmount();
            $totalRefundAmount = bcadd($previousRefundAmount, $transactionAmount, 2);
            $userTransactionEntity->setRefundAmount($totalRefundAmount);
            if (0 == bccomp($userTransactionEntity->getPaidAmount(), $totalRefundAmount, 2)) {
                $userOrderEntity->setStatus('Cancelled');
                $userOrderEntity->setPaymentStatus($paymentStatus);
                $userTransactionEntity->setPaymentStatus($paymentStatus);
            }
        }

        $this->em()->flush();

        return new Response('Ok');
    }
}
