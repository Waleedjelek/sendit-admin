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
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Mime\Address;
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
        ParameterBagInterface $parameterBag,
        EmailService $emailService,
        MailerInterface $mailer
    ): Response {
        $email = $request->query->get('email', '');
        $sendEmail = $request->query->has('send_email') && $request->query->get('send_email') !== '';
        $emailResult = [
            'success' => false,
            'message' => '',
            'error_details' => null,
            'exception' => null,
        ];

        // If send_email is requested, actually send the test email
        if ($sendEmail) {
            if (empty($email)) {
                $emailResult['success'] = false;
                $emailResult['message'] = 'Email address is required';
                $emailResult['error_details'] = [
                    'message' => 'Please provide an email address to send test email',
                    'code' => 0,
                    'file' => __FILE__,
                    'line' => __LINE__,
                ];
            } else {
                // Check email configuration first
                $emailEnabled = $parameterBag->get('app_email_enabled');
                $senderEmail = $parameterBag->get('app_email_sender_email');
                $senderName = $parameterBag->get('app_email_sender_name');
                $mailerDsn = $_ENV['MAILER_DSN'] ?? 'Not set';
                
                if (!$emailEnabled) {
                    $emailResult['success'] = false;
                    $emailResult['message'] = 'Email sending is disabled';
                    $emailResult['error_details'] = [
                        'message' => 'Email sending is disabled. Set APP_EMAIL_ENABLED=true in your environment variables.',
                        'code' => 0,
                        'file' => __FILE__,
                        'line' => __LINE__,
                        'configuration' => [
                            'app_email_enabled' => $emailEnabled ? 'true' : 'false',
                            'app_email_sender_email' => $senderEmail,
                            'app_email_sender_name' => $senderName,
                            'MAILER_DSN' => $mailerDsn,
                        ],
                    ];
                } else {
                    try {
                        // Create a simple test email
                        $testEmail = new TemplatedEmail();
                        $testEmail->addTo($email);
                        $testEmail->subject('Test Email - Send it');
                        $testEmail->htmlTemplate('emails/test-email.html.twig');
                        $testEmail->context([
                            'email_sent_to' => $email,
                            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                        ]);
                        
                        // Set sender information (same as EmailService does)
                        $senderAddress = new Address($senderEmail, $senderName);
                        $testEmail->sender($senderAddress);
                        $testEmail->replyTo($senderAddress);
                        $testEmail->returnPath($parameterBag->get('app_email_return_email'));
                        $testEmail->from($senderAddress);
                        
                        // Try to send directly to catch detailed exceptions
                        try {
                            $mailer->send($testEmail);
                            
                            $emailResult['success'] = true;
                            $emailResult['message'] = 'Test email sent successfully to: ' . $email;
                            $emailResult['error_details'] = null;
                        } catch (HandlerFailedException $e) {
                            // Unwrap HandlerFailedException to get the actual transport exception
                            $nestedExceptions = $e->getNestedExceptions();
                            $actualException = !empty($nestedExceptions) ? $nestedExceptions[0] : ($e->getPrevious() ?: $e);
                            
                            $this->handleEmailException($actualException, $emailResult, $emailEnabled, $senderEmail, $senderName, $mailerDsn);
                        } catch (TransportExceptionInterface $e) {
                            // Catch transport exceptions for detailed error info
                            $this->handleEmailException($e, $emailResult, $emailEnabled, $senderEmail, $senderName, $mailerDsn);
                        }
                    } catch (\Exception $e) {
                        $emailResult['success'] = false;
                        $emailResult['message'] = 'Failed to send test email';
                        $emailResult['error_details'] = [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                            'configuration' => [
                                'app_email_enabled' => $emailEnabled ? 'true' : 'false',
                                'app_email_sender_email' => $senderEmail,
                                'app_email_sender_name' => $senderName,
                                'MAILER_DSN' => $mailerDsn,
                            ],
                        ];
                        $emailResult['exception'] = get_class($e);
                    }
                }
            }
        }

        return $this->render('controller/security/debug-password-reset.html.twig', [
            'email' => $email,
            'emailResult' => $emailResult,
        ]);
    }
    
    private function handleEmailException(\Throwable $e, array &$emailResult, bool $emailEnabled, string $senderEmail, string $senderName, string $mailerDsn): void
    {
        $errorMessage = $e->getMessage();
        $isSignatureError = stripos($errorMessage, 'signature') !== false || stripos($errorMessage, 'InvalidSignatureException') !== false;
        $isCredentialError = stripos($errorMessage, 'credential') !== false || stripos($errorMessage, 'AccessDenied') !== false;
        
        $troubleshooting = [];
        
        if ($isSignatureError) {
            $emailResult['message'] = 'Failed to send test email - AWS Signature Error';
            $troubleshooting = [
                'Invalid Secret Key' => 'The AWS Secret Access Key in MAILER_DSN is incorrect or contains special characters that need URL encoding',
                'URL Encode Secret Key' => 'If your secret key contains special characters (/, +, =, etc.), you must URL-encode them in the MAILER_DSN',
                'Check Secret Key' => 'Verify the AWS Secret Access Key is correct in your .env file',
                'Regenerate Credentials' => 'Consider regenerating AWS credentials if the key might have been corrupted',
                'Example URL Encoding' => 'Special chars: / becomes %2F, + becomes %2B, = becomes %3D',
            ];
        } elseif ($isCredentialError) {
            $emailResult['message'] = 'Failed to send test email - AWS Credentials Error';
            $troubleshooting = [
                'Check Access Key' => 'Verify AWS Access Key ID is correct',
                'Check Secret Key' => 'Verify AWS Secret Access Key is correct',
                'Check IAM Permissions' => 'Ensure IAM user has ses:SendEmail permission',
                'Verify Region' => 'Ensure region matches your SES configuration',
            ];
        } else {
            $emailResult['message'] = 'Failed to send test email - Transport Error';
            $troubleshooting = [
                'Check AWS credentials' => 'Verify AWS access key and secret key are correct',
                'Verify AWS region' => 'Ensure region (eu-west-1) matches your SES configuration',
                'Check SES permissions' => 'Ensure IAM user has ses:SendEmail permission',
                'Verify sender email' => 'Sender email must be verified in AWS SES',
                'Check error logs' => 'Look in var/log/ directory for additional details',
            ];
        }
        
        $emailResult['success'] = false;
        $emailResult['error_details'] = [
            'message' => $errorMessage,
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'exception' => get_class($e),
            'configuration' => [
                'app_email_enabled' => $emailEnabled ? 'true' : 'false',
                'app_email_sender_email' => $senderEmail,
                'app_email_sender_name' => $senderName,
                'MAILER_DSN' => $mailerDsn,
                'MAILER_DSN (masked)' => $this->maskMailerDsn($mailerDsn),
            ],
            'troubleshooting' => $troubleshooting,
        ];
        $emailResult['exception'] = get_class($e);
    }
    
    private function maskMailerDsn(string $dsn): string
    {
        // Mask AWS credentials in DSN for security
        // Format: ses+api://ACCESS_KEY:SECRET_KEY@default?region=REGION
        if (preg_match('/^(ses\+api:\/\/)([^:]+):([^@]+)@(.+)$/', $dsn, $matches)) {
            $accessKey = $matches[2];
            $secretKey = $matches[3];
            $rest = $matches[4];
            
            // Mask access key (show first 4 chars)
            $maskedAccessKey = substr($accessKey, 0, 4) . str_repeat('*', max(0, strlen($accessKey) - 4));
            
            // Mask secret key completely
            $maskedSecretKey = str_repeat('*', strlen($secretKey));
            
            return $matches[1] . $maskedAccessKey . ':' . $maskedSecretKey . '@' . $rest;
        }
        
        return $dsn;
    }
}
