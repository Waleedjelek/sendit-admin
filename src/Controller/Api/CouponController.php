<?php

namespace App\Controller\Api;

use App\Classes\Controller\AuthenticatedAPIController;
use App\Classes\Exception\APIException;
use App\Service\CouponService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/coupon")
 */
class CouponController extends AuthenticatedAPIController
{
    /**
     * @Route("/get", methods={"POST"}).
     *
     * @throws APIException
     */
    public function getInfo(
        CouponService $couponService
    ): Response {
        $couponCode = $this->getRequiredVar('couponCode');

        $couponCodeEntity = $couponService->getCouponByCode($couponCode);
        if (is_null($couponCodeEntity)) {
            throw new APIException('Invalid Coupon Code', 101);
        }
        $data['coupon'] = $couponService->getCouponArray($couponCodeEntity);
        return $this->dataJson($data);
    }

}
