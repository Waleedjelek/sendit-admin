<?php

namespace App\Controller\Api;

use App\Classes\Controller\APIController;
use App\Classes\Exception\APIException;
use App\Repository\CountryEntityRepository;
use App\Service\RefreshTokenService;
use App\Service\UserService;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/auth")
 */
class AuthController extends APIController
{
    /**
     * @Route("/register", methods={"POST"})
     *
     * @throws APIException
     */
    public function register(
        Request $request,
        UserService $userService,
        CountryEntityRepository $countryEntityRepository,
        RefreshTokenService $refreshTokenService
    ): Response {
        $email = $this->getRequiredVar('email');
        $countryCode = $this->getRequiredVar('countryCode');
        $mobile = $this->getRequiredVar('mobile');
        $password = $this->getRequiredVar('password');
        $firstName = $this->getVar('firstName');
        $lastName = $this->getVar('lastName');

        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation(),
        ]);

        if (false === $validator->isValid($email, $multipleValidations)) {
            throw new APIException('Invalid parameter: email', 101);
        }

        $countryEntity = $countryEntityRepository->getByCode($countryCode);
        if (is_null($countryEntity)) {
            throw new APIException('Invalid parameter: countryCode', 102);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($mobile, $countryEntity->getCode());
        } catch (NumberParseException $exception) {
            throw new APIException($exception->getMessage(), 103);
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            throw new APIException('Invalid parameter: mobile', 103);
        }

        $phoneType = $phoneUtil->getNumberType($phoneNumber);
        if (PhoneNumberType::MOBILE != $phoneType && PhoneNumberType::FIXED_LINE_OR_MOBILE != $phoneType) {
            throw new APIException('Invalid parameter: mobile', 103);
        }
        $mobile = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);

        if (strlen($password) < 8) {
            throw new APIException('Invalid parameter: password', 104);
        }

        $userEntity = $userService->getUserByEmail($email);
        if (!is_null($userEntity)) {
            throw new APIException('User already exists with email: '.$email, 105);
        }

        $userEntity = $userService->createNewMember(
            $email, $countryEntity->getCode(),
            $mobile, $password, $firstName, $lastName);
        $userEntity->setProfileImagePrefix($this->getAssetURL().'/uploads');

        $userService->apiEmailActivateRequest($userEntity);

        $response = $this->dataJson([
            'user' => $userEntity,
            'jwt' => [
                'expire' => $this->getJWTService()->getLifetime(),
                'token' => $this->getJWTService()->createToken([
                    'userId' => $userEntity->getId(),
                ])->toString(),
            ],
        ]);

        $refreshTokenEntity = $refreshTokenService->createToken($userEntity);
        $refreshTokenExpire = time() + (3600 * 24 * $refreshTokenService->getExpireInDays());
        $cookie = new Cookie(
            $refreshTokenService->getCookieName(),
            $refreshTokenEntity->getToken(),
            $refreshTokenExpire,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_NONE
        );
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @Route("/login", methods={"POST"})
     *
     * @throws APIException
     */
    public function login(
        Request $request,
        UserService $userService,
        RefreshTokenService $refreshTokenService
    ): Response {
        $email = $this->getRequiredVar('email');
        $password = $this->getRequiredVar('password');

        if (false === (new EmailValidator())->isValid($email, new RFCValidation())) {
            throw new APIException('Invalid parameter: email', 101);
        }

        $userEntity = $userService->getUserByEmail($email);
        if (is_null($userEntity)) {
            throw new APIException('User does not exists', 102);
        }

        if (!password_verify($password, $userEntity->getPassword())) {
            throw new APIException('Invalid password', 103);
        }

        $userEntity->setProfileImagePrefix($this->getAssetURL().'/uploads');
        $userEntity->setLastLoginDate(new \DateTime());
        $this->em()->flush();

        $response = $this->dataJson([
            'user' => $userEntity,
            'jwt' => [
                'expire' => $this->getJWTService()->getLifetime(),
                'token' => $this->getJWTService()->createToken([
                    'userId' => $userEntity->getId(),
                ])->toString(),
            ],
        ]);

        if ($request->cookies->has($refreshTokenService->getCookieName())) {
            $refreshToken = $request->cookies->get($refreshTokenService->getCookieName());
            if (!empty($refreshToken)) {
                $refreshTokenEntity = $refreshTokenService->getToken($refreshToken);
                if (!is_null($refreshTokenEntity)) {
                    $refreshTokenService->expireToken($refreshTokenEntity);
                }
            }
        }

        $refreshTokenEntity = $refreshTokenService->createToken($userEntity);
        $refreshTokenExpire = time() + (3600 * 24 * $refreshTokenService->getExpireInDays());
        $cookie = new Cookie(
            $refreshTokenService->getCookieName(),
            $refreshTokenEntity->getToken(),
            $refreshTokenExpire,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_NONE
        );
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @Route("/reset-password", methods={"POST"})
     *
     * @throws APIException
     */
    public function resetPassword(
        Request $request,
        UserService $userService
    ): Response {
        $email = $this->getRequiredVar('email');

        if (false === (new EmailValidator())->isValid($email, new RFCValidation())) {
            throw new APIException('Invalid email', 101);
        }

        $userEntity = $userService->apiResetPasswordRequest($email);

        return $this->dataJson(['status' => 'Ok']);
    }

    /**
     * @Route("/refresh", methods={"POST"})
     *
     * @throws APIException
     */
    public function autoLogin(
        Request $request,
        RefreshTokenService $refreshTokenService
    ): Response {
        if (!$request->cookies->has($refreshTokenService->getCookieName())) {
            throw new APIException('Cookie missing', 101);
        }

        $refreshToken = $request->cookies->get($refreshTokenService->getCookieName());
        if (empty($refreshToken)) {
            throw new APIException('Cookie empty', 102);
        }

        $refreshTokenEntity = $refreshTokenService->getToken($refreshToken);
        if (is_null($refreshTokenEntity)) {
            throw new APIException('Cookie invalid', 103);
        }

        $userEntity = $refreshTokenEntity->getUser();
        $userEntity->setProfileImagePrefix($this->getAssetURL().'/uploads');
        $userEntity->setLastLoginDate(new \DateTime());
        $this->em()->flush();

        $response = $this->dataJson([
            'user' => $userEntity,
            'jwt' => [
                'expire' => $this->getJWTService()->getLifetime(),
                'token' => $this->getJWTService()->createToken([
                    'userId' => $userEntity->getId(),
                ])->toString(),
            ],
        ]);

        $refreshTokenExpire = time() + (3600 * 24 * $refreshTokenService->getExpireInDays());
        $cookie = new Cookie(
            'refreshToken', $refreshTokenEntity->getToken(),
            $refreshTokenExpire, '/',
            null, $request->isSecure(), true, false, Cookie::SAMESITE_NONE
        );
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @Route("/logout", methods={"POST"})
     */
    public function logout(
        Request $request,
        RefreshTokenService $refreshTokenService
    ): Response {
        if ($request->cookies->has($refreshTokenService->getCookieName())) {
            $refreshToken = $request->cookies->get($refreshTokenService->getCookieName());
            if (!empty($refreshToken)) {
                $refreshTokenEntity = $refreshTokenService->getToken($refreshToken);
                if (!is_null($refreshTokenEntity)) {
                    $refreshTokenService->expireToken($refreshTokenEntity);
                }
            }
        }

        $response = $this->dataJson([]);

        $refreshTokenExpire = time() - (3600 * 24 * $refreshTokenService->getExpireInDays());
        $cookie = new Cookie(
            $refreshTokenService->getCookieName(),
            null,
            $refreshTokenExpire,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_NONE
        );

        $response->headers->setCookie($cookie);

        return $response;
    }
}
