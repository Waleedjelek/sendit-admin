<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\QuoteEntity;
use App\Entity\UserOrderEntity;
use App\Entity\UserTransactionEntity;
use App\Service\OrderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AdminController
{
    /**
     * @Route("/", name="app_home")
     * @Route("/admin/", name="app_admin_home")
     */
    public function home(
        ParameterBagInterface $parameterBag
    ): Response {
        if ($this->isGranted('ROLE_EDITOR')) {
            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_FOUND);
        }
        $redirectURL = $parameterBag->get('app_sendit_marketing_website_url');

        return $this->redirect($redirectURL, Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/admin/dashboard", name="app_dashboard")
     * @IsGranted("ROLE_EDITOR")
     */
    public function index(): Response
    {
        $qb = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
        $qb->setFirstResult(0);
        $qb->setMaxResults(5);
        $qb->add('orderBy', ' o.createdDate DESC ');
        $orders = $qb->getQuery()->getResult();

        $qb = $this->em()->getRepository(UserTransactionEntity::class)->createQueryBuilder('t');
        $qb->setFirstResult(0);
        $qb->setMaxResults(5);
        $qb->add('orderBy', ' t.createdDate DESC ');
        $transactions = $qb->getQuery()->getResult();

        $qb = $this->em()->getRepository(QuoteEntity::class)->createQueryBuilder('q');
        $qb->setFirstResult(0);
        $qb->setMaxResults(5);
        $qb->add('orderBy', ' q.createdDate DESC ');
        $quotes = $qb->getQuery()->getResult();

        return $this->render('controller/dashboard/index.html.twig', [
            'orders' => $orders,
            'transactions' => $transactions,
            'quotes' => $quotes,
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     * @Route("/admin/test-email/order/{id}", name="app_test_email_order")
     */
    public function sample(UserOrderEntity $userOrderEntity, OrderService $orderService): Response
    {
        $orderService->sendCustomerOrderEmail($userOrderEntity);

        exit('email');
    }
}
