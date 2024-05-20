<?php

namespace App\Service;

use App\Classes\MenuItem;
use App\Classes\Service\BaseService;
use App\Controller\Admin\AuditController;
use App\Controller\Admin\CompanyController;
use App\Controller\Admin\CouponController;
use App\Controller\Admin\CountryController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\LocaleController;
use App\Controller\Admin\OrderController;
use App\Controller\Admin\PackageTypeController;
use App\Controller\Admin\PriceSearchController;
use App\Controller\Admin\QuoteController;
use App\Controller\Admin\SecurityController;
use App\Controller\Admin\TransactionController;
use App\Controller\Admin\UserController;
use App\Entity\QuoteEntity;
use App\Entity\UserOrderEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MenuService extends BaseService
{
    private ?string $currentController;

    private ?string $currentAction;

    private array $leftMenu = [];

    private array $rightMenu = [];

    private array $topMenu = [];

    private UrlGeneratorInterface $router;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UrlGeneratorInterface $router,
        EntityManagerInterface $entityManager
    ) {
        $this->router = $router;
        $this->entityManager = $entityManager;

        $this->initLeftMenu();

        $this->initRightMenu();

        $this->initTopMenu();
    }

    private function initLeftMenu()
    {
        $this->addLeftMenu(new MenuItem(
            'dashboard',
            'Dashboard',
            $this->router->generate('app_dashboard'),
            DashboardController::class,
            'fas fa-home',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'orders',
            'Orders',
            $this->router->generate('app_order_index'),
            OrderController::class,
            'fas fa-file-invoice-dollar',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'quotes',
            'Quotes',
            $this->router->generate('app_quote_index'),
            QuoteController::class,
            'fas fa-question-circle',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'transaction',
            'Transaction',
            $this->router->generate('app_transaction_index'),
            TransactionController::class,
            'fas fa-credit-card',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'priceSearch',
            'Price Search',
            $this->router->generate('app_price_search'),
            PriceSearchController::class,
            'fas fa-money-check-alt',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'companies',
            'Companies',
            $this->router->generate('app_company_index'),
            CompanyController::class,
            'fas fa-building',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'coupon',
            'Coupons',
            $this->router->generate('app_coupon_index'),
            CouponController::class,
            'fas fa-building',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'packageTypes',
            'Package Types',
            $this->router->generate('app_package_type_index'),
            PackageTypeController::class,
            'fas fa-ruler-combined',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'countries',
            'Countries',
            $this->router->generate('app_country_index'),
            CountryController::class,
            'fas fa-flag',
            'ROLE_EDITOR'
        ));

        $this->addLeftMenu(new MenuItem(
            'locale',
            'Locale',
            $this->router->generate('app_locale_index'),
            LocaleController::class,
            'fas fa-newspaper',
            'ROLE_ADMIN'
        ));

        $this->addLeftMenu(new MenuItem(
            'audit',
            'Audit Log',
            $this->router->generate('app_audit_index'),
            AuditController::class,
            'fas fa-info-circle',
            'ROLE_ADMIN'
        ));

        $this->addLeftMenu(new MenuItem(
            'users',
            'Users',
            $this->router->generate('app_user_index'),
            UserController::class,
            'fas fa-user',
            'ROLE_EDITOR'
        ));
    }

    private function initRightMenu()
    {
        $this->addRightMenu(new MenuItem(
            'Profile',
            'Profile',
            $this->router->generate('app_user_profile'),
            SecurityController::class,
            'fas fa-sign-out-alt',
            'ROLE_EDITOR'
        ));

        $this->addRightMenu(new MenuItem(
            'Logout',
            'Logout',
            $this->router->generate('app_logout'),
            SecurityController::class,
            'fas fa-question-circle',
            'ROLE_EDITOR'
        ));
    }

    private function initTopMenu()
    {
        $qb = $this->entityManager->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
        $qb->select('COUNT(o) AS cnt');
        $qb->andWhere("  o.status = 'Ready' ");
        $countResult = $qb->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $this->addTopMenu(new MenuItem(
            'orders',
            'New Orders ('.$totalCount.')',
            $this->router->generate('app_order_index', ['filter_status' => 'Ready']),
            OrderController::class,
            'fas fa-file-invoice-dollar',
            'ROLE_EDITOR'
        ));

        $qb = $this->entityManager->getRepository(QuoteEntity::class)->createQueryBuilder('q');
        $qb->select('COUNT(q) AS cnt');
        $qb->andWhere("  q.status = 'New' ");
        $countResult = $qb->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $this->addTopMenu(new MenuItem(
            'quotes',
            'New Quotes ('.$totalCount.')',
            $this->router->generate('app_quote_index', ['filter_status' => 'New']),
            QuoteController::class,
            'fas fa-question-circle',
            'ROLE_EDITOR'
        ));
    }

    private function addLeftMenu(MenuItem $item)
    {
        $this->leftMenu[$item->getName()] = $item;
    }

    private function addRightMenu(MenuItem $item)
    {
        $this->rightMenu[$item->getName()] = $item;
    }

    private function addTopMenu(MenuItem $item)
    {
        $this->topMenu[$item->getName()] = $item;
    }

    public function getCurrentController(): ?string
    {
        return $this->currentController;
    }

    public function setCurrentController(?string $currentController): void
    {
        $this->currentController = $currentController;
    }

    public function getCurrentAction(): ?string
    {
        return $this->currentAction;
    }

    public function setCurrentAction(?string $currentAction): void
    {
        $this->currentAction = $currentAction;
    }

    /**
     * @return MenuItem[]
     */
    public function getLeftMenu(): array
    {
        return $this->leftMenu;
    }

    public function setLeftMenu(array $leftMenu): void
    {
        $this->leftMenu = $leftMenu;
    }

    public function getRightMenu(): array
    {
        return $this->rightMenu;
    }

    public function setRightMenu(array $rightMenu): void
    {
        $this->rightMenu = $rightMenu;
    }

    public function getTopMenu(): array
    {
        return $this->topMenu;
    }

    public function setTopMenu(array $topMenu): void
    {
        $this->topMenu = $topMenu;
    }
}
