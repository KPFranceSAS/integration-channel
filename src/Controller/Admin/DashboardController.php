<?php

namespace App\Controller\Admin;

use App\Entity\ProductCorrelation;
use App\Entity\User;
use App\Entity\WebOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/", name="admin")
     */
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Channel tools');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::subMenu('Orders', 'fa fa-shopping-cart')->setSubItems([
                MenuItem::linkToCrud('Amazon', 'fab fa-amazon', WebOrder::class)->setController(ChannelAdvisorOrderCrudController::class),
                MenuItem::linkToCrud('Aliexpress', 'fab fa-alipay', WebOrder::class)->setController(AliexpressOrderCrudController::class),
                MenuItem::linkToCrud('All', 'fa fa-shopping-cart', WebOrder::class)->setController(WebOrderCrudController::class),
            ]),
            MenuItem::linkToCrud('Product Correlations', 'fa fa-exchange', ProductCorrelation::class),
            MenuItem::linkToCrud('Users', 'fa fa-user', User::class),
        ];
    }


    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user->getUserIdentifier())
            ->displayUserName(false)
            ->displayUserAvatar(false);
    }


    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setPaginatorPageSize(30);
    }
}
