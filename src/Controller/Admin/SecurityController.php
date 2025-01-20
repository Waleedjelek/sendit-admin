<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\UserEntity;
use App\Form\UserPasswordResetType;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
     * @Route("/update-role", name="update_user_role")
     */
    public function updateRole(EntityManagerInterface $entityManager): Response
    {
        $email = 'waleed.twh@gmail.com';
        // Fetch the user by email
        $user = $entityManager->getRepository(UserEntity::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new Response('User not found', Response::HTTP_NOT_FOUND);
        }
        // Update the user's roles
        $user->setRoles(['ROLE_ADMIN']);
        // Persist the changes to the database
        $entityManager->persist($user);
        $entityManager->flush();
        return new Response('User role updated to ROLE_ADMIN', Response::HTTP_OK);
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
                $userEntity = $userService->resetPasswordRequest($formData['email'], $formData['grc_token']);
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
}
