<?php

namespace App\Controller\Admin;

use App\Controller\Fba\ProductCrudController;
use App\Controller\Fba\StockCrudController;
use App\Controller\Order\AliexpressOrderCrudController;
use App\Controller\Order\ChannelAdvisorOrderCrudController;
use App\Controller\Order\DeliveryOrderCrudController;
use App\Controller\Order\ErrorOrderCrudController;
use App\Controller\Order\FitbitExpressOrderCrudController;
use App\Controller\Order\FlashledOrderCrudController;
use App\Controller\Order\MinibattOrderCrudController;
use App\Controller\Order\OwletCareOrderCrudController;
use App\Controller\Order\WebOrderCrudController;
use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\FbaReturn;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\User;
use App\Entity\WebOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
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
        $manager = $this->getDoctrine()->getManager();

        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Paxira');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::subMenu('Orders', 'fa fa-shopping-cart')->setSubItems([
                MenuItem::linkToCrud('Amazon', 'fab fa-amazon', WebOrder::class)->setController(ChannelAdvisorOrderCrudController::class),
                MenuItem::linkToCrud('Aliexpress', 'fab fa-alipay', WebOrder::class)->setController(AliexpressOrderCrudController::class),
                MenuItem::linkToCrud('Fitbitexpress', 'fas fa-heartbeat', WebOrder::class)->setController(FitbitExpressOrderCrudController::class),
                MenuItem::linkToCrud('Owletcare', 'fas fa-baby', WebOrder::class)->setController(OwletCareOrderCrudController::class),
                MenuItem::linkToCrud('Flashled', 'far fa-lightbulb', WebOrder::class)->setController(FlashledOrderCrudController::class),
                MenuItem::linkToCrud('Minibatt', 'fas fa-car-battery', WebOrder::class)->setController(MinibattOrderCrudController::class),
                MenuItem::section(),
                MenuItem::linkToCrud('On delivery', 'fas fa-truck-loading', WebOrder::class)->setController(DeliveryOrderCrudController::class),
                MenuItem::linkToCrud('Errors', 'fas fa-exclamation-triangle', WebOrder::class)->setController(ErrorOrderCrudController::class),
                MenuItem::linkToCrud('All', 'fa fa-shopping-cart', WebOrder::class)->setController(WebOrderCrudController::class),
            ]),
            MenuItem::subMenu('Amazon & FBA', 'fab fa-amazon')->setSubItems([
                MenuItem::linkToCrud('Inventory', 'fas fa-cube', Product::class)->setController(StockCrudController::class),
                MenuItem::linkToCrud('FBA Returns', 'fas fa-exchange-alt', FbaReturn::class),
                MenuItem::linkToCrud('Product', 'fas fa-barcode', Product::class)->setController(ProductCrudController::class),
                MenuItem::linkToCrud('Brand', 'far fa-registered', Brand::class),
                MenuItem::linkToCrud('Category', 'fas fa-sitemap', Category::class),
            ])->setPermission('ROLE_AMAZON'),
            MenuItem::subMenu('Configuration', 'fas fa-cogs')->setSubItems([
                MenuItem::linkToCrud('SKU Mapping', 'fa fa-exchange', ProductCorrelation::class),
                MenuItem::linkToCrud('Users', 'fa fa-user', User::class)->setPermission('ROLE_ADMIN'),
            ])
        ];
    }


    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('assets/css/admin.css');
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
            ->setPaginatorPageSize(50);
    }
}
