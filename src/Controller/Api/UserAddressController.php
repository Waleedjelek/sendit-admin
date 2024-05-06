<?php

namespace App\Controller\Api;

use App\Classes\Controller\AuthenticatedAPIController;
use App\Classes\Exception\APIException;
use App\Entity\UserAddressEntity;
use App\Repository\CountryEntityRepository;
use App\Repository\UserAddressEntityRepository;
use App\Service\UserService;
use Brick\Postcode\InvalidPostcodeException;
use Brick\Postcode\PostcodeFormatter;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/address")
 */
class UserAddressController extends AuthenticatedAPIController
{
    /**
     * @Route("/list", methods={"POST"}).
     */
    public function list(UserService $userService): Response
    {
        $userEntity = $this->getUser();

        $addresses = $userService->getActiveAddresses($userEntity);

        return $this->dataJson([
            'addresses' => $addresses,
        ]);
    }

    /**
     * @Route("/add", methods={"POST"}).
     *
     * @throws APIException
     */
    public function add(
        CountryEntityRepository $countryEntityRepository,
        UserService $userService
    ): Response {
        $userEntity = $this->getUser();
        $formatter = new PostcodeFormatter();

        $countryCode = $this->getRequiredVar('countryCode');
        $name = $this->getRequiredVar('name');
        $contactName = $this->getRequiredVar('contactName');
        $contactMobile = $this->getRequiredVar('contactMobile');
        $cityName = $this->getRequiredVar('cityName');
        if ($formatter->isSupportedCountry($countryCode)) {
            $zipCode = $this->getRequiredVar('zipCode');
        } else {
            $zipCode = $this->getVar('zipCode');
        }
        $type = $this->getRequiredVar('type');
        $primary = $this->getRequiredVar('primary');
        $contactEmail = $this->getVar('contactEmail');
        $secondary = $this->getVar('secondary');
        $landmark = $this->getVar('landmark');
        $state = $this->getVar('state');

        $countryEntity = $countryEntityRepository->getByCode($countryCode);
        if (is_null($countryEntity)) {
            throw new APIException('Invalid country code', 101);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumber = $phoneUtil->parse($contactMobile, $countryCode);
        } catch (NumberParseException $exception) {
            throw new APIException($exception->getMessage(), 102);
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            throw new APIException('Invalid contact mobile', 102);
        }
        $contactMobile = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);

        if ($formatter->isSupportedCountry($countryCode)) {
            try {
                $zipCode = $formatter->format($countryCode, $zipCode);
            } catch (InvalidPostcodeException $exception) {
                throw new APIException('Invalid zip code', 103);
            }
        }

        if (!is_null($contactEmail)) {
            $validator = new EmailValidator();
            $multipleValidations = new MultipleValidationWithAnd([
                new RFCValidation(),
//                new DNSCheckValidation(),
            ]);

            if (false === $validator->isValid($contactEmail, $multipleValidations)) {
                throw new APIException('Invalid contact email', 103);
            }
        }

        $userAddressEntity = new UserAddressEntity();
        $userAddressEntity->setUser($userEntity);
        $userAddressEntity->setName($name);
        $userAddressEntity->setContactName($contactName);
        $userAddressEntity->setContactMobile($contactMobile);
        $userAddressEntity->setContactEmail($contactEmail);
        $userAddressEntity->setType($type);
        $userAddressEntity->setPrimary($primary);
        $userAddressEntity->setSecondary($secondary);
        $userAddressEntity->setLandmark($landmark);
        $userAddressEntity->setCityName($cityName);
        $userAddressEntity->setZipCode($zipCode);
        $userAddressEntity->setState($state);
        $userAddressEntity->setCountryCode($countryCode);
        $userAddressEntity->setActive(true);
        $userAddressEntity->setCreatedDate(new \DateTime());
        $this->em()->persist($userAddressEntity);
        $this->em()->flush();

        $addresses = $userService->getActiveAddresses($userEntity);

        return $this->dataJson([
            'addedId' => $userAddressEntity->getId(),
            'addresses' => $addresses,
        ]);
    }

    /**
     * @Route("/edit", methods={"POST"}).
     *
     * @throws APIException
     */
    public function edit(
        CountryEntityRepository $countryEntityRepository,
        UserService $userService,
        UserAddressEntityRepository $userAddressEntityRepository
    ): Response {
        $userEntity = $this->getUser();
        $formatter = new PostcodeFormatter();

        $countryCode = $this->getRequiredVar('countryCode');
        $addressId = $this->getRequiredVar('id');
        $name = $this->getRequiredVar('name');
        $contactName = $this->getRequiredVar('contactName');
        $contactMobile = $this->getRequiredVar('contactMobile');
        $cityName = $this->getRequiredVar('cityName');
        if ($formatter->isSupportedCountry($countryCode)) {
            $zipCode = $this->getRequiredVar('zipCode');
        } else {
            $zipCode = $this->getVar('zipCode');
        }
        $type = $this->getRequiredVar('type');
        $primary = $this->getRequiredVar('primary');
        $contactEmail = $this->getVar('contactEmail');
        $secondary = $this->getVar('secondary');
        $landmark = $this->getVar('landmark');
        $state = $this->getVar('state');

        $userAddressEntity = $userAddressEntityRepository->find($addressId);
        if (is_null($userAddressEntity)) {
            throw new APIException('Address not found', 101);
        }

        $countryEntity = $countryEntityRepository->getByCode($countryCode);
        if (is_null($countryEntity)) {
            throw new APIException('Invalid country code', 102);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumber = $phoneUtil->parse($contactMobile, $countryCode);
        } catch (NumberParseException $exception) {
            throw new APIException($exception->getMessage(), 102);
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            throw new APIException('Invalid contact mobile', 102);
        }
        $contactMobile = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);

        if ($formatter->isSupportedCountry($countryCode)) {
            try {
                $zipCode = $formatter->format($countryCode, $zipCode);
            } catch (InvalidPostcodeException $exception) {
                throw new APIException('Invalid zip code', 103);
            }
        }

        if (!is_null($contactEmail)) {
            $validator = new EmailValidator();
            $multipleValidations = new MultipleValidationWithAnd([
                new RFCValidation(),
//                new DNSCheckValidation(),
            ]);

            if (false === $validator->isValid($contactEmail, $multipleValidations)) {
                throw new APIException('Invalid contact email', 104);
            }
        }

        $userAddressEntity->setName($name);
        $userAddressEntity->setContactName($contactName);
        $userAddressEntity->setContactMobile($contactMobile);
        $userAddressEntity->setContactEmail($contactEmail);
        $userAddressEntity->setType($type);
        $userAddressEntity->setPrimary($primary);
        $userAddressEntity->setSecondary($secondary);
        $userAddressEntity->setLandmark($landmark);
        $userAddressEntity->setCityName($cityName);
        $userAddressEntity->setZipCode($zipCode);
        $userAddressEntity->setState($state);
        $userAddressEntity->setCountryCode($countryCode);
        $userAddressEntity->setModifiedDate(new \DateTime());
        $this->em()->flush();

        $addresses = $userService->getActiveAddresses($userEntity);

        return $this->dataJson([
            'editedId' => $userAddressEntity->getId(),
            'addresses' => $addresses,
        ]);
    }

    /**
     * @Route("/delete", methods={"POST"}).
     *
     * @throws APIException
     */
    public function delete(
        UserAddressEntityRepository $userAddressEntityRepository,
        UserService $userService
    ): Response {
        $userEntity = $this->getUser();
        $addressId = $this->getRequiredVar('id');

        $userAddressEntity = $userAddressEntityRepository->find($addressId);
        if (is_null($userAddressEntity)) {
            throw new APIException('Address not found', 101);
        }

        $userAddressEntity->setActive(false);
        $userAddressEntity->setModifiedDate(new \DateTime());
        $this->em()->flush();

        $addresses = $userService->getActiveAddresses($userEntity);

        return $this->dataJson([
            'deletedId' => $addressId,
            'addresses' => $addresses,
        ]);
    }
}
