<?php

namespace App\Extension;

use App\Classes\MenuItem;
use App\Service\MenuService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    private MenuService $menuService;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ?\DateTimeZone $currentTimeZone = null;

    public function __construct(
        MenuService $menuService,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->menuService = $menuService;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('toTZ', [$this, 'formatToTimezone']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getTopMenu', [$this, 'getTopMenu']),
            new TwigFunction('getLeftMenu', [$this, 'getLeftMenu']),
            new TwigFunction('getRightMenu', [$this, 'getRightMenu']),
        ];
    }

    /**
     * @return MenuItem[]
     */
    public function getTopMenu(): array
    {
        $filteredMenus = [];
        $menus = $this->menuService->getTopMenu();
        $currentController = $this->menuService->getCurrentController();
        if (!empty($menus)) {
            foreach ($menus as $menu) {
                if ($this->authorizationChecker->isGranted($menu->getUserRole())) {
                    $filteredMenus[] = $menu;
                }
                if ($currentController == $menu->getControllerClass()) {
                    $menu->setActive(true);
                }
            }
        }

        return $filteredMenus;
    }

    /**
     * @return MenuItem[]
     */
    public function getLeftMenu(): array
    {
        $filteredMenus = [];
        $menus = $this->menuService->getLeftMenu();
        $currentController = $this->menuService->getCurrentController();
        if (!empty($menus)) {
            foreach ($menus as $menu) {
                if ($this->authorizationChecker->isGranted($menu->getUserRole())) {
                    $filteredMenus[] = $menu;
                }
                if ($currentController == $menu->getControllerClass()) {
                    $menu->setActive(true);
                }
            }
        }

        return $filteredMenus;
    }

    /**
     * @return MenuItem[]
     */
    public function getRightMenu(): array
    {
        $filteredMenus = [];
        $menus = $this->menuService->getRightMenu();
        dump($menus);
        $currentController = $this->menuService->getCurrentController();
        if (!empty($menus)) {
            foreach ($menus as $menu) {
                if ($this->authorizationChecker->isGranted($menu->getUserRole())) {
                    $filteredMenus[] = $menu;
                }
                if ($currentController == $menu->getControllerClass()) {
                    $menu->setActive(true);
                }
            }
        }

        return $filteredMenus;
    }

    public function formatToTimezone($dateTime, $format = 'd-M-y h:i A')
    {
        if (!($dateTime instanceof \DateTime)) {
            return $dateTime;
        }
        if (is_null($this->currentTimeZone)) {
            $timezone = 'Asia/Dubai';
            $this->currentTimeZone = new \DateTimeZone($timezone);
        }
        $dateTime->setTimezone($this->currentTimeZone);

        return $dateTime->format($format);
    }
}
