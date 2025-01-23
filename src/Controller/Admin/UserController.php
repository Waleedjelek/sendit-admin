<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\UserEntity;
use App\Entity\UserOrderEntity;
use App\Entity\UserTransactionEntity;
use App\Form\UserChangePasswordType;
use App\Form\UserEntityType;
use App\Form\UserProfileType;
use App\Repository\UserEntityRepository;
use App\Service\OrderQuoteService;
use App\Service\UserService;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\User;

/**
 * @Route("/admin/user")
 * @IsGranted("ROLE_EDITOR")
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class UserController extends AdminController
{

    private $orderQuoteService;

    public function __construct(OrderQuoteService $orderQuoteService)
    {
        $this->orderQuoteService = $orderQuoteService;
    }
    /**
     * @Route("/users/download", name="app_user_download")
     */
    public function downloadCsv(
        UserEntityRepository $userRepository
    ): Response {
        $users = $userRepository->findAll();

        $csvData = [];
        $csvData[] = ['Name', 'Email', 'Phone', 'Role', 'Registered', 'Last Login'];

        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'ROLE_MEMBER')
            ->orderBy('u.createdDate', 'DESC');
        $users = $qb->getQuery()->getResult();
        foreach ($users as $user) {
            $csvData[] = [
                $user->getFirstName() . ' ' . $user->getLastName(),
                $user->getEmail(),
                $user->getMobileNumber(),
                $user->getRole(), // Adjust based on your role caption logic
                $user->getCreatedDate()->format('Y-m-d H:i:s'),
                $user->getLastLoginDate() instanceof \DateTime ? $user->getLastLoginDate()->format('Y-m-d H:i:s') : '-',
            ];
        }

        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', $row) . "\n";
        }

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');

        return $response;
    }


    /**
     * @Route("/", name="app_user_index", methods={"GET","POST"})
     */
    public function index(
        Request $request,
        UserService $userService,
        UserEntityRepository $userRepository
    ): Response {
        if ($request->isMethod('get')) {
            $data = $this->orderQuoteService->getTodayOrdersAndQuotes();
            return $this->render('controller/user/index.html.twig',[
                'newOrders' => $data['newOrders'],
                'newQuotes' => $data['newQuotes'],
            ]);
        }

        $draw = $request->get('draw', 0);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        if ($length < 0) {
            $length = 10;
        }

        $search = $request->get('search');
        $searchString = null;
        if (null != $search && isset($search['value']) && !empty($search['value'])) {
            $searchString = $search['value'];
        }

        $qb = $userRepository->createQueryBuilder('u');
        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        if (!empty($searchString)) {
            $qb->andWhere(' ( u.email LIKE :query1 OR  u.firstName LIKE :query1  OR  u.lastName LIKE :query1  ) ');
            $qb->setParameter('query1', '%' . $searchString . '%');
        }

        $qb->add('orderBy', ' u.createdDate DESC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(u) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $userEntity UserEntity
             */
            foreach ($result as $userEntity) {
                $ca = [];
                $ca['DT_RowId'] = $userEntity->getId();
                $ca['name'] = $userEntity->getFirstName() . ' ' . $userEntity->getLastName();
                $ca['email'] = $userEntity->getEmail();
                $ca['phone'] = $userEntity->getMobileNumber();
                $ca['role'] = $userService->getRoleCaption($userEntity->getRole());
                $ca['created'] = $this->formatToTimezone($userEntity->getCreatedDate());
                $ca['lastLogin'] = '-';
                if ($userEntity->getLastLoginDate() instanceof \DateTime) {
                    $ca['lastLogin'] = $this->formatToTimezone($userEntity->getLastLoginDate());
                }
                $ca['action'] = [
                    'details' => $this->generateUrl('app_user_show', ['id' => $userEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/profile", name="app_user_profile", methods={"GET","POST"})
     */
    public function profile(
        Request $request,
        UserService $userService
    ): Response {
        /** @var UserEntity $userEntity */
        $userEntity = $this->getUser();

        $form = $this->createForm(
            UserProfileType::class,
            null,
            [
                'user' => $userEntity,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneNumber = $phoneUtil->parse($formData['mobile'], $userEntity->getCountryCode());
                if (false === $phoneUtil->isValidNumber($phoneNumber)) {
                    $form->get('mobile')->addError(new FormError('Invalid mobile number'));
                }
                $phoneType = $phoneUtil->getNumberType($phoneNumber);
                if (PhoneNumberType::MOBILE != $phoneType && PhoneNumberType::FIXED_LINE_OR_MOBILE != $phoneType) {
                    $form->get('mobile')->addError(new FormError('Invalid mobile number'));
                }
            } catch (NumberParseException $exception) {
                $form->get('mobile')->addError(new FormError($exception->getMessage()));
            }

            if ($form->isValid()) {
                $userEntity->setFirstName($formData['firstName']);
                $userEntity->setLastName($formData['lastName']);

                if ($formData['profileImage'] instanceof UploadedFile) {
                    $profileImage = $userService->saveProfileImage($formData['profileImage']);
                    if (!is_null($profileImage)) {
                        $userService->deleteProfileImage($userEntity->getProfileImage());
                        $userEntity->setProfileImage($profileImage);
                    }
                }

                $mobile = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
                $userEntity->setMobileNumber($mobile);
                $userEntity->setModifiedDate(new \DateTime());
                $this->em()->flush();

                $this->addLog(
                    'User',
                    'Update Profile',
                    'Update user profile - ' . $userEntity->getEmail(),
                    $userEntity->getId()
                );

                $this->addFlash('message', 'Profile updated!');

                return $this->redirectToRoute('app_user_profile', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->renderForm('controller/user/profile.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/password", name="app_user_password", methods={"GET","POST"})
     */
    public function password(
        Request $request,
        UserService $userService
    ): Response {
        /** @var UserEntity $userEntity */
        $userEntity = $this->getUser();

        $form = $this->createForm(
            UserChangePasswordType::class,
            null,
            [
                'user' => $userEntity,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            if (!password_verify($formData['currentPassword'], $userEntity->getPassword())) {
                $form->get('currentPassword')->addError(new FormError('Invalid current password'));
            }

            if ($formData['currentPassword'] == $formData['password']) {
                $form->get('password')->addError(new FormError('New password cannot be your old password'));
            }

            if (!$userService->isPasswordStrong($formData['password'])) {
                $form->get('password')->addError(new FormError('New password does not meet all requirement'));
            }

            if ($form->isValid()) {
                $hashedPassword = $userService->getPasswordEncoder()->hashPassword($userEntity, $formData['password']);
                $userEntity->setPassword($hashedPassword);
                $userEntity->setModifiedDate(new \DateTime());
                $this->em()->flush();

                $this->addLog(
                    'User',
                    'Update Password',
                    'Update user password - ' . $userEntity->getEmail(),
                    $userEntity->getId()
                );

                $this->addFlash('message', 'Password updated!');

                return $this->redirectToRoute('app_user_password', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->renderForm('controller/user/password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/new", name="app_user_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(
        Request $request,
        UserService $userService
    ): Response {
        $userEntity = new UserEntity();
        $form = $this->createForm(
            UserEntityType::class,
            $userEntity,
            ['roles' => $userService->getCurrentUserCapableRoles()]
        );
        $form->handleRequest($request);

        //        $this->isGranted()

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $userEntity->setPassword('123');
            $userEntity->setCreatedDate(new \DateTime());
            $entityManager->persist($userEntity);
            $entityManager->flush();

            $userService->resetPassword($userEntity);

            $this->addLog(
                'User',
                'Add',
                'Added user - ' . $userEntity->getEmail(),
                $userEntity->getId()
            );

            $this->addFlash('message', 'Created user and emailed credentials!');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/user/new.html.twig', [
            'user' => $userEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_user_show", methods={"GET"})
     */
    public function show(UserEntity $user, UserService $userService): Response
    {
        $roleCaption = $userService->getRoleCaption($user->getRole());

        $qb = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
        $qb->setFirstResult(0);
        $qb->setMaxResults(5);
        $qb->andWhere(" o.user = '" . $user->getId() . "' ");
        $qb->add('orderBy', ' o.createdDate DESC ');
        $orders = $qb->getQuery()->getResult();

        $qb = $this->em()->getRepository(UserTransactionEntity::class)->createQueryBuilder('t');
        $qb->setFirstResult(0);
        $qb->setMaxResults(5);
        $qb->andWhere(" t.user = '" . $user->getId() . "' ");
        $qb->add('orderBy', ' t.createdDate DESC ');
        $transactions = $qb->getQuery()->getResult();

        return $this->render('controller/user/show.html.twig', [
            'orders' => $orders,
            'transactions' => $transactions,
            'roleCaption' => $roleCaption,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_user_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(
        Request $request,
        UserEntity $user,
        UserService $userService
    ): Response {
        $form = $this->createForm(
            UserEntityType::class,
            $user,
            ['roles' => $userService->getCurrentUserCapableRoles()]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setModifiedDate(new \DateTime());
            $this->getDoctrine()->getManager()->flush();

            $this->addLog(
                'User',
                'Edit',
                'Edited user - ' . $user->getEmail(),
                $user->getId()
            );

            $this->addFlash('message', 'Updated user!');

            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/reset-password", name="app_user_pass_reset", methods={"POST"})
     */
    public function resetPassword(
        Request $request,
        UserEntity $userEntity,
        UserService $userService
    ): Response {
        if ($this->isCsrfTokenValid('reset-password' . $userEntity->getId(), $request->request->get('_token'))) {
            $userService->resetPassword($userEntity);

            $this->addLog(
                'User',
                'Reset Password',
                'Reset user password - ' . $userEntity->getEmail(),
                $userEntity->getId()
            );

            $this->addFlash('message', 'Updated user password and emailed credentials!');

            return $this->redirectToRoute('app_user_show', ['id' => $userEntity->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}", name="app_user_delete", methods={"POST"})
     */
    public function delete(Request $request, UserEntity $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                $userId = $user->getId();
                $userEmail = $user->getEmail();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($user);
                $entityManager->flush();

                $this->addLog(
                    'User',
                    'Delete',
                    'Deleted user - ' . $userEmail,
                    $userId
                );

                $this->addFlash('message', 'Deleted user successfully!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete user!');
            }
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
