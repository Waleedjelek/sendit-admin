<?php

namespace App\Service;

use App\Classes\Exception\APIException;
use App\Classes\Service\BaseService;
use App\Entity\UserEntity;
use App\Kernel;
use App\Repository\CountryEntityRepository;
use App\Repository\UserAddressEntityRepository;
use App\Repository\UserEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gregwar\Image\Image;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use ReCaptcha\ReCaptcha;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserService extends BaseService
{
    private Kernel $kernel;
    private EntityManagerInterface $entityManager;
    private UserEntityRepository $userEntityRepository;
    private ReCaptcha $reCaptcha;
    private EmailService $emailService;
    private UserPasswordHasherInterface $passwordEncoder;

    private $profileFolder = '/profile';
    private Filesystem $filesystem;
    private UserAddressEntityRepository $userAddressEntityRepository;
    private CountryEntityRepository $countryEntityRepository;
    private MessageBusInterface $messageBus;
    private RouterInterface $router;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ?SlackService $slackService;

    public function __construct(
        Kernel $kernel,
        EntityManagerInterface $entityManager,
        UserEntityRepository $userEntityRepository,
        UserAddressEntityRepository $userAddressEntityRepository,
        CountryEntityRepository $countryEntityRepository,
        ReCaptcha $reCaptcha,
        EmailService $emailService,
        Filesystem $filesystem,
        UserPasswordHasherInterface $passwordEncoder,
        MessageBusInterface $messageBus,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        ?SlackService $slackService = null
    ) {
        $this->entityManager = $entityManager;
        $this->userEntityRepository = $userEntityRepository;
        $this->reCaptcha = $reCaptcha;
        $this->emailService = $emailService;
        $this->passwordEncoder = $passwordEncoder;
        $this->kernel = $kernel;
        $this->slackService = $slackService;
        $this->filesystem = $filesystem;
        $this->userAddressEntityRepository = $userAddressEntityRepository;
        $this->countryEntityRepository = $countryEntityRepository;
        $this->messageBus = $messageBus;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getUserByEmail(string $email): ?UserEntity
    {
        return $this->userEntityRepository->getByEmail($email);
    }

    public function createNewMember(
        string $email,
        string $countryCode,
        string $mobile,
        string $password,
        ?string $firstName,
        ?string $lastName
    ): UserEntity {
        $userEntity = new UserEntity();
        $userEntity->setEmail($email);
        $userEntity->setFirstName($firstName);
        $userEntity->setLastName($lastName);
        $userEntity->setCountryCode($countryCode);
        $userEntity->setEmailVerified(false);
        $userEntity->setMobileNumber($mobile);
        $userEntity->setMobileVerified(false);
        $password = $this->passwordEncoder->hashPassword($userEntity, $password);
        $userEntity->setPassword($password);
        $userEntity->setRole('ROLE_MEMBER');
        $userEntity->setCreatedDate(new \DateTime());
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();

        return $userEntity;
    }

    public function getRoleCaption(string $role): string
    {
        $caption = 'Unknown';
        switch ($role) {
            case 'ROLE_MEMBER':
                $caption = 'Member';
                break;
            case 'ROLE_EDITOR':
                $caption = 'Editor';
                break;
            case 'ROLE_ADMIN':
                $caption = 'Admin';
                break;
            case 'ROLE_SUPER_ADMIN':
                $caption = 'Super Admin';
                break;
        }

        return $caption;
    }

    public function getCurrentUserCapableRoles(): array
    {
        $roles = [];
        if ($this->authorizationChecker->isGranted('ROLE_MEMBER')) {
            $roles['Member'] = 'ROLE_MEMBER';
        }
        if ($this->authorizationChecker->isGranted('ROLE_EDITOR')) {
            $roles['Editor'] = 'ROLE_EDITOR';
        }
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $roles['Admin'] = 'ROLE_ADMIN';
        }
        if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $roles['Super Admin'] = 'ROLE_SUPER_ADMIN';
        }

        return $roles;
    }

    public function saveProfileImage(UploadedFile $uploadImage): ?string
    {
        $profileFilename = $this->profileFolder.gmdate('/Y/m/').uniqid('pic_').'.jpg';
        $saveImageFile = $this->getUploadFolder().$profileFilename;

        $this->filesystem->mkdir(dirname($saveImageFile));

        $image = Image::open($uploadImage->getPathname());
        $image->zoomCrop(200, 200, 0xFFFFFF, 'center', 'center');

        try {
            $image->save($saveImageFile, 'jpeg', 100);

            return $profileFilename;
        } catch (\Exception $e) {
        }

        return null;
    }

    public function deleteProfileImage(?string $imageName): bool
    {
        if (is_null($imageName)) {
            return true;
        }
        $deleteImageFile = $this->getUploadFolder().$imageName;
        $this->filesystem->remove($deleteImageFile);

        return true;
    }

    /**
     * @throws \Exception
     */
    public function resetPasswordRequest(string $email, ?string $captchaToken): UserEntity
    {
        try {
            // TODO: Re-enable reCAPTCHA validation when secret key is configured
            // Temporarily disabled - reCAPTCHA secret key not configured
            /*
            if (!is_null($captchaToken) && !empty($captchaToken)) {
                $reResponse = $this->reCaptcha->verify($captchaToken);
                if (!$reResponse->isSuccess()) {
                    $error = 'Captcha validation failed!';
                    $this->sendSlackError('Password Reset Request - ' . $error, [
                        'email' => $email,
                        'error_type' => 'captcha_validation_failed'
                    ]);
                    throw new \Exception($error);
                }
            }
            */

            $userEntity = $this->userEntityRepository->findOneBy(['email' => $email]);
            if (is_null($userEntity)) {
                $error = 'User does not exists!';
                $this->sendSlackError('Password Reset Request - ' . $error, [
                    'email' => $email,
                    'error_type' => 'user_not_found'
                ]);
                throw new \Exception($error);
            }

            if (!$userEntity->isActive()) {
                $error = 'Account is disabled!';
                $this->sendSlackError('Password Reset Request - ' . $error, [
                    'email' => $email,
                    'user_id' => $userEntity->getId(),
                    'error_type' => 'account_disabled'
                ]);
                throw new \Exception($error);
            }

            $passwordResetDate = $userEntity->getPasswordResetTokenDate();
            if ($passwordResetDate instanceof \DateTime) {
                $currentDate = new \DateTime();
                $interval = $currentDate->getTimestamp() - $passwordResetDate->getTimestamp();
                if ($interval < 3600) {
                    $error = 'Password reset was recently requested!';
                    $this->sendSlackError('Password Reset Request - ' . $error, [
                        'email' => $email,
                        'user_id' => $userEntity->getId(),
                        'last_reset_date' => $passwordResetDate->format('Y-m-d H:i:s'),
                        'error_type' => 'rate_limit_exceeded'
                    ]);
                    throw new \Exception($error);
                }
            }

            $random = random_bytes(10);
            $resetToken = hash('sha1', $random);

            $userEntity->setPasswordResetToken($resetToken);
            $userEntity->setPasswordResetTokenDate(new \DateTime());
            $this->entityManager->flush();

            $emailMessage = new TemplatedEmail();
            $emailMessage->addTo($userEntity->getEmail());
            $emailMessage->subject('Send it - Password Reset Request!');
            $emailMessage->htmlTemplate('emails/reset-validate.html.twig');
            $emailMessage->context([
                'user' => $userEntity,
                'email_sent_to' => $userEntity->getEmail(),
                'resetURL' => $this->generateUrl('app_reset_validate', [
                    'id' => $userEntity->getId(),
                    'code' => $userEntity->getPasswordResetToken(),
                ],
                    UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            
            $emailSent = $this->emailService->send($emailMessage);
            
            if (!$emailSent) {
                $this->sendSlackError('Password Reset Email Failed to Send', [
                    'email' => $userEntity->getEmail(),
                    'user_id' => $userEntity->getId(),
                    'error_type' => 'email_send_failed'
                ]);
                throw new \Exception('Failed to send password reset email. Please try again later.');
            }

            return $userEntity;
        } catch (\Exception $e) {
            // If it's already been handled with Slack notification, rethrow
            if (strpos($e->getMessage(), 'Password Reset Request') !== false || 
                strpos($e->getMessage(), 'Captcha') !== false ||
                strpos($e->getMessage(), 'User does not') !== false ||
                strpos($e->getMessage(), 'Account is disabled') !== false ||
                strpos($e->getMessage(), 'recently requested') !== false) {
                throw $e;
            }
            
            // Catch any unexpected errors
            $this->sendSlackError('Password Reset Request - Unexpected Error', [
                'email' => $email,
                'error_message' => $e->getMessage(),
                'error_type' => 'unexpected_error',
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function sendSlackError(string $message, array $context = []): void
    {
        if ($this->slackService !== null) {
            $this->slackService->sendErrorNotification($message, $context);
        }
    }

    /**
     * @throws \Exception
     */
    public function apiResetPasswordRequest(string $email, ?string $captchaToken = null): UserEntity
    {
        try {
            // TODO: Re-enable reCAPTCHA validation when secret key is configured
            // Temporarily disabled - reCAPTCHA secret key not configured
            /*
            if (!is_null($captchaToken) && !empty($captchaToken)) {
                $reResponse = $this->reCaptcha->verify($captchaToken);
                if (!$reResponse->isSuccess()) {
                    $error = 'Captcha validation failed!';
                    $this->sendSlackError('API Password Reset Request - ' . $error, [
                        'email' => $email,
                        'error_type' => 'captcha_validation_failed'
                    ]);
                    throw new APIException($error, 102);
                }
            }
            */

            $userEntity = $this->userEntityRepository->findOneBy(['email' => $email]);
            if (is_null($userEntity)) {
                $error = 'User does not exists!';
                $this->sendSlackError('API Password Reset Request - ' . $error, [
                    'email' => $email,
                    'error_type' => 'user_not_found'
                ]);
                throw new APIException($error, 103);
            }

            if (!$userEntity->isActive()) {
                $error = 'Account is disabled!';
                $this->sendSlackError('API Password Reset Request - ' . $error, [
                    'email' => $email,
                    'user_id' => $userEntity->getId(),
                    'error_type' => 'account_disabled'
                ]);
                throw new APIException($error, 104);
            }

            $passwordResetDate = $userEntity->getPasswordResetTokenDate();
            if ($passwordResetDate instanceof \DateTime) {
                $currentDate = new \DateTime();
                $interval = $currentDate->getTimestamp() - $passwordResetDate->getTimestamp();
                if ($interval < 3600) {
                    $error = 'Password reset was recently requested!';
                    $this->sendSlackError('API Password Reset Request - ' . $error, [
                        'email' => $email,
                        'user_id' => $userEntity->getId(),
                        'last_reset_date' => $passwordResetDate->format('Y-m-d H:i:s'),
                        'error_type' => 'rate_limit_exceeded'
                    ]);
                    throw new APIException($error, 105);
                }
            }

            $random = random_bytes(10);
            $resetToken = hash('sha1', $random);

            $userEntity->setPasswordResetToken($resetToken);
            $userEntity->setPasswordResetTokenDate(new \DateTime());
            $this->entityManager->flush();

            $emailMessage = new TemplatedEmail();
            $emailMessage->addTo($userEntity->getEmail());
            $emailMessage->subject('Send it - Password Reset Request!');
            $emailMessage->htmlTemplate('emails/reset-validate.html.twig');
            $emailMessage->context([
                'user' => $userEntity,
                'email_sent_to' => $userEntity->getEmail(),
                'resetURL' => $this->generateUrl('app_api_reset_validate', [
                    'id' => $userEntity->getId(),
                    'code' => $userEntity->getPasswordResetToken(),
                ],
                    UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            
            $emailSent = $this->emailService->send($emailMessage);
            
            if (!$emailSent) {
                $this->sendSlackError('API Password Reset Email Failed to Send', [
                    'email' => $userEntity->getEmail(),
                    'user_id' => $userEntity->getId(),
                    'error_type' => 'email_send_failed'
                ]);
            }

            return $userEntity;
        } catch (APIException $e) {
            // If it's already been handled with Slack notification, rethrow
            throw $e;
        } catch (\Exception $e) {
            // Catch any unexpected errors
            $this->sendSlackError('API Password Reset Request - Unexpected Error', [
                'email' => $email,
                'error_message' => $e->getMessage(),
                'error_type' => 'unexpected_error',
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function apiEmailActivateRequest(UserEntity $userEntity, bool $newUser = true): UserEntity
    {
        $random = random_bytes(10);
        $verificationToken = hash('sha1', $random);

        $userEntity->setEmailVerificationToken($verificationToken);
        $userEntity->setEmailVerificationTokenDate(new \DateTime());
        $this->entityManager->flush();

        $email = new TemplatedEmail();
        $email->addTo($userEntity->getEmail());
        $email->subject('Send it - Activate your email!');
        if ($newUser) {
            $email->htmlTemplate('emails/new-user-email-activate.html.twig');
        } else {
            $email->htmlTemplate('emails/existing-user-email-activate.html.twig');
        }
        $email->context([
            'user' => $userEntity,
            'email_sent_to' => $userEntity->getEmail(),
            'activationURL' => $this->generateUrl('app_api_email_activate', [
                'id' => $userEntity->getId(),
                'code' => $userEntity->getEmailVerificationToken(),
            ],
                UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $this->emailService->send($email);

        return $userEntity;
    }

    public function resetPassword(UserEntity $userEntity): bool
    {
        $generator = new ComputerPasswordGenerator();
        $generator
            ->setUppercase()
            ->setLowercase()
            ->setNumbers()
            ->setSymbols(false)
            ->setLength(15);

        $password = $generator->generatePassword();

        $hashedPassword = $this->passwordEncoder->hashPassword($userEntity, $password);
        $userEntity->setPassword($hashedPassword);
        $userEntity->setModifiedDate(new \DateTime());
        $this->entityManager->flush();

        $email = new TemplatedEmail();
        $email->addTo($userEntity->getEmail());
        $email->subject('Send it - Login Credentials');
        $email->htmlTemplate('emails/user-password.html.twig');
        $email->context([
            'user' => $userEntity,
            'email_sent_to' => $userEntity->getEmail(),
            'userEmail' => $userEntity->getEmail(),
            'userPassword' => $password,
            'loginURL' => $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $this->emailService->send($email);

        return true;
    }

    public function apiResetPassword(UserEntity $userEntity): bool
    {
        $generator = new ComputerPasswordGenerator();
        $generator
            ->setUppercase()
            ->setLowercase()
            ->setNumbers()
            ->setSymbols(false)
            ->setLength(15);

        $password = $generator->generatePassword();

        $hashedPassword = $this->passwordEncoder->hashPassword($userEntity, $password);
        $userEntity->setPassword($hashedPassword);
        $userEntity->setModifiedDate(new \DateTime());
        $this->entityManager->flush();

        $email = new TemplatedEmail();
        $email->addTo($userEntity->getEmail());
        $email->subject('Send it - Login Credentials');
        $email->htmlTemplate('emails/user-password.html.twig');
        $email->context([
            'user' => $userEntity,
            'email_sent_to' => $userEntity->getEmail(),
            'userEmail' => $userEntity->getEmail(),
            'userPassword' => $password,
            'loginURL' => 'https://app-sendit.bluebeetle.net/login',
        ]);
        $this->emailService->send($email);

        return true;
    }

    public function getActiveAddresses(UserEntity $userEntity): array
    {
        $addresses = $this->userAddressEntityRepository->getUserActiveAddresses($userEntity);
        if (!empty($addresses)) {
            foreach ($addresses as $addressEntity) {
                $countryEntity = $this->countryEntityRepository->getByCode($addressEntity->getCountryCode());
                $addressEntity->setCountryName($countryEntity->getName());
                $addressEntity->setCountryDialCode('+'.$countryEntity->getDialCode());

                $contactMobile = $addressEntity->getContactMobile();
                if (!empty($contactMobile)) {
                    $contactMobile = str_replace('+'.$countryEntity->getDialCode(), '', $contactMobile);
                    $addressEntity->setContactMobile($contactMobile);
                }
            }
        }

        return $addresses;
    }

    public function isPasswordStrong(string $password): bool
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
            return false;
        }

        return true;
    }

    public function getUploadFolder(): string
    {
        return $this->kernel->getProjectDir().'/public/uploads';
    }

    public function getPasswordEncoder(): UserPasswordHasherInterface
    {
        return $this->passwordEncoder;
    }

    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->router->generate($route, $parameters, $referenceType);
    }
}
