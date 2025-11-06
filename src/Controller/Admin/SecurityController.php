<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\UserEntity;
use App\Form\UserPasswordResetType;
use App\Repository\UserEntityRepository;
use App\Service\EmailService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AdminController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
//        if ($this->getUser()) {
//            return $this->redirectToRoute('app_home');
//        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('controller/security/login.html.twig',
            ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/reset-password", name="app_reset_password")
     */
    public function resetPassword(
        Request $request,
        UserService $userService
    ): Response {
        $form = $this->createForm(UserPasswordResetType::class, null, [
            'action' => $this->generateUrl('app_reset_password'),
            'attr' => [
                'id' => 'form_user_password_reset',
            ],
        ]);
        $error = false;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userEntity = null;
            $formData = $form->getData();

            try {
                // TODO: Re-enable reCAPTCHA validation when secret key is configured
                // Pass null for captcha token to bypass validation
                $userEntity = $userService->resetPasswordRequest($formData['email'], null);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            if (!is_null($userEntity)) {
                $this->addFlash('message', 'Please check your email, we have sent password reset instruction.');

                return $this->redirectToRoute('app_reset_password', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->renderForm('controller/security/reset.html.twig', [
            'errorMessage' => $error,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/reset-validate/{id}/{code}/", name="app_reset_validate")
     */
    public function validateResetPassword(
        EntityManagerInterface $entityManager,
        UserService $userService,
        UserEntity $userEntity,
        string $code
    ): Response {
        $resetCode = $userEntity->getPasswordResetToken();
        if (is_null($resetCode)) {
            $this->addFlash('error', 'Invalid password request!');

            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        if ($resetCode != $code) {
            $this->addFlash('error', 'Invalid password request!');

            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $passwordResetDate = $userEntity->getPasswordResetTokenDate();
        if ($passwordResetDate instanceof \DateTime) {
            $currentDate = new \DateTime();
            $interval = $currentDate->getTimestamp() - $passwordResetDate->getTimestamp();
            if ($interval > 3600) {
                $this->addFlash('error', 'Password reset request expired!');

                return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
            }
        }

        $userEntity->setPasswordResetToken(null);
        $userEntity->setPasswordResetTokenDate(null);
        $entityManager->flush();

        $userService->resetPassword($userEntity);

        $this->addFlash('message', 'Please check your email, we have sent new login credentials.');

        return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/reset-pass-val/{id}/{code}/", name="app_api_reset_validate")
     */
    public function apiValidateResetPassword(
        EntityManagerInterface $entityManager,
        UserService $userService,
        UserEntity $userEntity,
        ParameterBagInterface $parameterBag,
        string $code
    ): Response {
        $successMessage = 'Password reset successfully! Please check your email.';
        $errorMessage = null;
        $resetCode = $userEntity->getPasswordResetToken();

        if (is_null($resetCode)) {
            $errorMessage = 'Invalid password request!';
        }

        if ($resetCode != $code && is_null($errorMessage)) {
            $errorMessage = 'Invalid password request!';
        }

        $passwordResetDate = $userEntity->getPasswordResetTokenDate();
        if ($passwordResetDate instanceof \DateTime && is_null($errorMessage)) {
            $currentDate = new \DateTime();
            $interval = $currentDate->getTimestamp() - $passwordResetDate->getTimestamp();
            if ($interval > 3600) {
                $errorMessage = 'Password reset request expired!';
            }
        }

        $redirectURL = $parameterBag->get('app_sendit_user_website_url');
        $redirectURL = $redirectURL.'/login';

        if (is_null($errorMessage)) {
            $userEntity->setPasswordResetToken(null);
            $userEntity->setPasswordResetTokenDate(null);
            $entityManager->flush();
            $userService->apiResetPassword($userEntity);
            $redirectURL = $redirectURL.'?success='.urlencode($successMessage);
        } else {
            $redirectURL = $redirectURL.'?error='.urlencode($errorMessage);
        }

        return $this->redirect($redirectURL, Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/email-activate/{id}/{code}/", name="app_api_email_activate")
     */
    public function apiEmailActivate(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        UserEntity $userEntity,
        string $code
    ): Response {
        $successMessage = 'Email Activated Successfully!';
        $errorMessage = null;

        $activationCode = $userEntity->getEmailVerificationToken();
        if (is_null($activationCode)) {
            $errorMessage = 'Invalid email activation request!';
        }

        if ($activationCode != $code && is_null($errorMessage)) {
            $errorMessage = 'Invalid email activation request!';
        }

        $activationDate = $userEntity->getEmailVerificationTokenDate();
        if ($activationDate instanceof \DateTime && is_null($errorMessage)) {
            $currentDate = new \DateTime();
            $interval = $currentDate->getTimestamp() - $activationDate->getTimestamp();
            if ($interval > (60 * 60 * 24 * 3)) {
                $errorMessage = 'Email activation request expired!';
            }
        }

        $redirectURL = $parameterBag->get('app_sendit_user_website_url');
        $redirectURL = $redirectURL.'/login';

        if (is_null($errorMessage)) {
            $userEntity->setEmailVerified(true);
            $userEntity->setEmailVerificationToken(null);
            $userEntity->setEmailVerificationTokenDate(null);
            $entityManager->flush();
            $redirectURL = $redirectURL.'?success='.urlencode($successMessage);
        } else {
            $redirectURL = $redirectURL.'?error='.urlencode($errorMessage);
        }

        return $this->redirect($redirectURL, Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/debug-password-reset", name="app_debug_password_reset")
     */
    public function debugPasswordReset(
        Request $request,
        UserEntityRepository $userRepository,
        ParameterBagInterface $parameterBag,
        EmailService $emailService,
        UserService $userService
    ): Response {
        $email = $request->query->get('email', '');
        $debugData = [];

        // A. Email Configuration Details
        $debugData['email_config'] = [
            'enabled' => $parameterBag->get('app_email_enabled'),
            'sender_name' => $parameterBag->get('app_email_sender_name'),
            'sender_email' => $parameterBag->get('app_email_sender_email'),
            'return_email' => $parameterBag->get('app_email_return_email'),
            'mailer_dsn' => $_ENV['MAILER_DSN'] ?? 'Not set',
        ];

        // B. User Details (if email provided)
        $userEntity = null;
        if (!empty($email)) {
            $userEntity = $userRepository->findOneBy(['email' => $email]);
            
            if ($userEntity) {
                $debugData['user_details'] = [
                    'id' => $userEntity->getId(),
                    'email' => $userEntity->getEmail(),
                    'first_name' => $userEntity->getFirstName(),
                    'last_name' => $userEntity->getLastName(),
                    'role' => $userEntity->getRole(),
                    'active' => $userEntity->isActive(),
                    'email_verified' => $userEntity->isEmailVerified(),
                    'created_date' => $userEntity->getCreatedDate() ? $userEntity->getCreatedDate()->format('Y-m-d H:i:s') : null,
                    'last_login_date' => $userEntity->getLastLoginDate() ? $userEntity->getLastLoginDate()->format('Y-m-d H:i:s') : null,
                    'modified_date' => $userEntity->getModifiedDate() ? $userEntity->getModifiedDate()->format('Y-m-d H:i:s') : null,
                ];

                // C. Password Reset Token Details
                $passwordResetToken = $userEntity->getPasswordResetToken();
                $passwordResetTokenDate = $userEntity->getPasswordResetTokenDate();
                
                $debugData['password_reset_token'] = [
                    'token' => $passwordResetToken,
                    'token_date' => $passwordResetTokenDate ? $passwordResetTokenDate->format('Y-m-d H:i:s') : null,
                    'token_age_seconds' => $passwordResetTokenDate ? (time() - $passwordResetTokenDate->getTimestamp()) : null,
                    'token_age_hours' => $passwordResetTokenDate ? round((time() - $passwordResetTokenDate->getTimestamp()) / 3600, 2) : null,
                    'is_expired' => $passwordResetTokenDate ? ((time() - $passwordResetTokenDate->getTimestamp()) > 3600) : null,
                    'expires_in_seconds' => $passwordResetTokenDate ? max(0, 3600 - (time() - $passwordResetTokenDate->getTimestamp())) : null,
                ];

                // D. Email Template Details
                if ($passwordResetToken) {
                    $resetURL = $this->generateUrl('app_reset_validate', [
                        'id' => $userEntity->getId(),
                        'code' => $passwordResetToken,
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $apiResetURL = $this->generateUrl('app_api_reset_validate', [
                        'id' => $userEntity->getId(),
                        'code' => $passwordResetToken,
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $debugData['email_template'] = [
                        'template_path' => 'emails/reset-validate.html.twig',
                        'subject' => 'Send it - Password Reset Request!',
                        'recipient' => $userEntity->getEmail(),
                        'reset_url_admin' => $resetURL,
                        'reset_url_api' => $apiResetURL,
                        'context_variables' => [
                            'user' => [
                                'id' => $userEntity->getId(),
                                'email' => $userEntity->getEmail(),
                                'firstName' => $userEntity->getFirstName(),
                                'lastName' => $userEntity->getLastName(),
                            ],
                            'email_sent_to' => $userEntity->getEmail(),
                            'resetURL' => $resetURL,
                        ],
                    ];

                    // E. Email Message Details (simulated)
                    $emailMessage = new TemplatedEmail();
                    $emailMessage->addTo($userEntity->getEmail());
                    $emailMessage->subject('Send it - Password Reset Request!');
                    $emailMessage->htmlTemplate('emails/reset-validate.html.twig');
                    $emailMessage->context([
                        'user' => $userEntity,
                        'email_sent_to' => $userEntity->getEmail(),
                        'resetURL' => $resetURL,
                    ]);

                    $debugData['email_message'] = [
                        'to' => array_map(function($addr) { return $addr->getAddress(); }, $emailMessage->getTo()),
                        'subject' => $emailMessage->getSubject(),
                        'html_template' => $emailMessage->getHtmlTemplate(),
                        'context' => $emailMessage->getContext(),
                    ];
                }

                // F. Password Reset Flow Details
                $debugData['password_reset_flow'] = [
                    'request_methods' => [
                        'admin' => [
                            'route' => 'app_reset_password',
                            'url' => $this->generateUrl('app_reset_password', [], UrlGeneratorInterface::ABSOLUTE_URL),
                            'service_method' => 'UserService::resetPasswordRequest()',
                        ],
                        'api' => [
                            'route' => 'app_api_reset_password',
                            'url' => '/api/v1/auth/reset-password',
                            'service_method' => 'UserService::apiResetPasswordRequest()',
                        ],
                    ],
                    'validation_routes' => [
                        'admin' => [
                            'route' => 'app_reset_validate',
                            'pattern' => '/reset-validate/{id}/{code}/',
                            'expiry_seconds' => 3600,
                        ],
                        'api' => [
                            'route' => 'app_api_reset_validate',
                            'pattern' => '/reset-pass-val/{id}/{code}/',
                            'expiry_seconds' => 3600,
                        ],
                    ],
                    'rate_limit' => [
                        'enabled' => true,
                        'interval_seconds' => 3600,
                        'interval_hours' => 1,
                    ],
                ];

                // G. Password Generation Details (for reset password)
                $debugData['password_generation'] = [
                    'generator_class' => 'Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator',
                    'settings' => [
                        'uppercase' => true,
                        'lowercase' => true,
                        'numbers' => true,
                        'symbols' => false,
                        'length' => 15,
                    ],
                    'password_email_template' => 'emails/user-password.html.twig',
                    'password_email_subject' => 'Send it - Login Credentials',
                ];
            } else {
                $debugData['user_details'] = [
                    'error' => 'User not found with email: ' . $email,
                ];
            }
        }

        // H. Environment Variables Related to Email
        $debugData['environment'] = [
            'APP_EMAIL_ENABLED' => $_ENV['APP_EMAIL_ENABLED'] ?? 'Not set',
            'APP_EMAIL_SENDER_NAME' => $_ENV['APP_EMAIL_SENDER_NAME'] ?? 'Not set',
            'APP_EMAIL_SENDER_EMAIL' => $_ENV['APP_EMAIL_SENDER_EMAIL'] ?? 'Not set',
            'APP_EMAIL_RETURN_EMAIL' => $_ENV['APP_EMAIL_RETURN_EMAIL'] ?? 'Not set',
            'MAILER_DSN' => $_ENV['MAILER_DSN'] ?? 'Not set',
        ];

        // I. Messenger Configuration
        $debugData['messenger_config'] = [
            'async_transport' => $_ENV['MESSENGER_TRANSPORT_DSN'] ?? 'Not set',
            'email_async_disabled' => true, // Based on messenger.yaml comment
        ];

        // J. Website URLs
        $debugData['website_urls'] = [
            'admin_website_url' => $parameterBag->get('app_sendit_admin_website_url'),
            'user_website_url' => $parameterBag->get('app_sendit_user_website_url'),
            'marketing_website_url' => $parameterBag->get('app_sendit_marketing_website_url'),
        ];

        return $this->render('controller/security/debug-password-reset.html.twig', [
            'debugData' => $debugData,
            'email' => $email,
            'userEntity' => $userEntity,
        ]);
    }
}
