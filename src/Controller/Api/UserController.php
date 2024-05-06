<?php

namespace App\Controller\Api;

use App\Classes\Controller\AuthenticatedAPIController;
use App\Classes\Exception\APIException;
use App\Service\UserService;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1/user")
 */
class UserController extends AuthenticatedAPIController
{
    /**
     * @Route("/updateProfile", methods={"POST"}).
     */
    public function updateProfile(): Response
    {
        $userEntity = $this->getUser();

        $firstName = $this->getRequiredVar('firstName');
        $lastName = $this->getRequiredVar('lastName');
        $mobile = $this->getRequiredVar('mobile');

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($mobile, $userEntity->getCountryCode());
        } catch (NumberParseException $exception) {
            throw new APIException($exception->getMessage(), 101);
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            throw new APIException('Invalid parameter: mobile', 101);
        }

        $phoneType = $phoneUtil->getNumberType($phoneNumber);
        if (PhoneNumberType::MOBILE != $phoneType && PhoneNumberType::FIXED_LINE_OR_MOBILE != $phoneType) {
            throw new APIException('Invalid parameter: mobile', 101);
        }
        $mobile = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);

        $userEntity->setFirstName($firstName);
        $userEntity->setLastName($lastName);
        $userEntity->setMobileNumber($mobile);
        $userEntity->setModifiedDate(new \DateTime());
        $this->em()->flush();

        $userEntity->setProfileImagePrefix($this->getAssetURL().'/uploads');

        return $this->dataJson([
            'user' => $userEntity,
        ]);
    }

    /**
     * @Route("/changePassword", methods={"POST"}).
     */
    public function changePassword(UserService $userService): Response
    {
        $userEntity = $this->getUser();

        $oldPassword = $this->getRequiredVar('oldPassword');
        $newPassword = $this->getRequiredVar('newPassword');

        if (!password_verify($oldPassword, $userEntity->getPassword())) {
            throw new APIException('Invalid current password', 101);
        }

        if ($oldPassword == $newPassword) {
            throw new APIException('New password cannot be your old password', 102);
        }

        if (!$userService->isPasswordStrong($newPassword)) {
            throw new APIException('New password does not meet all requirement', 103);
        }

        $hashedPassword = $userService->getPasswordEncoder()->hashPassword($userEntity, $newPassword);
        $userEntity->setPassword($hashedPassword);
        $userEntity->setModifiedDate(new \DateTime());
        $this->em()->flush();

        $userEntity->setProfileImagePrefix($this->getAssetURL().'/uploads');

        return $this->dataJson([
            'user' => $userEntity,
        ]);
    }

    /**
     * @Route("/activateEmail", methods={"POST"}).
     */
    public function activateEmail(UserService $userService): Response
    {
        $userEntity = $this->getUser();
        if ($userEntity->isEmailVerified()) {
            throw new APIException('Email already verified', 101);
        }

        $userService->apiEmailActivateRequest($userEntity, false);

        return $this->dataJson([
            'user' => $userEntity,
        ]);
    }

    /**
     * @Route("/updatePicture", methods={"POST"}).
     */
    public function updatePicture(
        Request $request,
        UserService $userService,
        ValidatorInterface $validator
    ): Response {
        $userEntity = $this->getUser();

        $uploadedFile = $request->files->get('profileImage');
        if (!$uploadedFile instanceof UploadedFile) {
            throw new APIException('Required file missing : profileImage', 100);
        }

        $errors = $validator->validate(
            $uploadedFile, [
                new Image([
                    'minWidth' => 200,
                    'minHeight' => 200,
                    'maxWidth' => 2048,
                    'maxHeight' => 2048,
                ]),
            ]
        );

        if ($errors->count() > 0) {
            throw new APIException($errors->get(0)->getMessage(), 101);
        }

        $profileImage = $userService->saveProfileImage($uploadedFile);
        if (!is_null($profileImage)) {
            $userService->deleteProfileImage($userEntity->getProfileImage());
            $userEntity->setProfileImage($profileImage);
        }
        $userEntity->setModifiedDate(new \DateTime());
        $this->em()->flush();
        $userEntity->setProfileImagePrefix($this->getAssetURL().'/uploads');

        return $this->dataJson([
            'user' => $userEntity,
        ]);
    }
}
