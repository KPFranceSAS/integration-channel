<?php

namespace App\Controller\Admin;

use App\Controller\Configuration\LogisticClassCrudController;
use App\Controller\Configuration\ProductCrudController;
use App\Controller\Fba\StockCrudController;
use App\Controller\Order\AliexpressOrderCrudController;
use App\Controller\Order\AmazonOrderCrudController;
use App\Controller\Order\AriseOrderCrudController;
use App\Controller\Order\BoulangerOrderCrudController;
use App\Controller\Order\CdiscountOrderCrudController;
use App\Controller\Order\DecathlonOrderCrudController;
use App\Controller\Order\DeliveryOrderCrudController;
use App\Controller\Order\ErrorOrderCrudController;
use App\Controller\Order\FitbitCorporateOrderCrudController;
use App\Controller\Order\FlashledOrderCrudController;
use App\Controller\Order\FnacDartyOrderCrudController;
use App\Controller\Order\LateOrderCrudController;
use App\Controller\Order\LeroyMerlinOrderCrudController;
use App\Controller\Order\ManoManoOrderCrudController;
use App\Controller\Order\MediaMarktOrderCrudController;
use App\Controller\Order\MinibattOrderCrudController;
use App\Controller\Order\OwletCareOrderCrudController;
use App\Controller\Order\PaxB2COrderCrudController;
use App\Controller\Order\PreparationOrderCrudController;
use App\Controller\Order\WebOrderCrudController;
use App\Controller\Pricing\PricingCrudController;
use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonRemoval;
use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\ImportPricing;
use App\Entity\IntegrationChannel;
use App\Entity\LogisticClass;
use App\Entity\OrderLog;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\Promotion;
use App\Entity\SaleChannel;
use App\Entity\User;
use App\Entity\WebOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    protected $adminContext;

    public function __construct(AdminContextProvider $adminContext)
    {
        $this->adminContext = $adminContext;
    }

    #[Route(path: '/', name: 'admin')]
    public function index(): Response
    {
        $menu= $this->adminContext->getContext()->getMainMenu();
        return $this->render('admin/dashboard.html.twig', ["menu"=>$menu]);
    }


    #[Route(path: '/help', name: 'help')]
    public function help(): Response
    {
        return $this->render('help/user.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->disableUrlSignatures()
            ->setTitle('Patxira');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::subMenu('Orders', 'fa fa-shopping-cart')
                ->setSubItems([
                    
                    MenuItem::linkToCrud('Aliexpress', 'fab fa-alipay', WebOrder::class)
                        ->setController(AliexpressOrderCrudController::class),
                    MenuItem::linkToCrud('Amazon', 'fab fa-amazon', WebOrder::class)
                        ->setController(AmazonOrderCrudController::class),
                     MenuItem::linkToCrud('Boulanger', 'fas fa-tv', WebOrder::class)
                        ->setController(BoulangerOrderCrudController::class),
                    MenuItem::linkToCrud('Cdiscount', 'fas fa-compact-disc', WebOrder::class)
                        ->setController(CdiscountOrderCrudController::class),
                    MenuItem::linkToCrud('Decathlon', 'fas fa-volleyball-ball', WebOrder::class)
                        ->setController(DecathlonOrderCrudController::class),
                    MenuItem::linkToCrud('Fitbit Corporate', 'fas fa-running', WebOrder::class)
                        ->setController(FitbitCorporateOrderCrudController::class),
                    MenuItem::linkToCrud('Fnac Darty', 'fas fa-video', WebOrder::class)
                        ->setController(FnacDartyOrderCrudController::class),
                        
                    MenuItem::linkToCrud('Flashled', 'far fa-lightbulb', WebOrder::class)
                        ->setController(FlashledOrderCrudController::class),
                     MenuItem::linkToCrud('Leroy Merlin', 'fas fa-hammer', WebOrder::class)
                        ->setController(LeroyMerlinOrderCrudController::class),
                    MenuItem::linkToCrud('ManoMano', 'fas fa-screwdriver', WebOrder::class)
                        ->setController(ManoManoOrderCrudController::class),
                        MenuItem::linkToCrud('MediaMarkt', 'fas fa-certificate', WebOrder::class)
                        ->setController(MediaMarktOrderCrudController::class),
                    MenuItem::linkToCrud('Minibatt', 'fas fa-car-battery', WebOrder::class)
                        ->setController(MinibattOrderCrudController::class),
                    MenuItem::linkToCrud('Miravia', 'fas fa-sun', WebOrder::class)
                        ->setController(AriseOrderCrudController::class),
                    MenuItem::linkToCrud('Owlet Care', 'fas fa-baby', WebOrder::class)
                        ->setController(OwletCareOrderCrudController::class),
                    MenuItem::linkToCrud('Pax B2C', 'fas fa-cannabis', WebOrder::class)
                        ->setController(PaxB2COrderCrudController::class),
                    
                    
                   
                    MenuItem::section(),
                    MenuItem::linkToCrud('Waiting for shipping', 'fas fa-truck-loading', WebOrder::class)
                        ->setController(PreparationOrderCrudController::class),
                    MenuItem::linkToCrud('On delivery', 'fas fa-truck', WebOrder::class)
                        ->setController(DeliveryOrderCrudController::class),
                    MenuItem::linkToCrud('Late', 'fas fa-clock', WebOrder::class)
                        ->setController(LateOrderCrudController::class),
                    MenuItem::linkToCrud('Error', 'fas fa-exclamation-triangle', WebOrder::class)
                        ->setController(ErrorOrderCrudController::class),
                    MenuItem::linkToCrud('All', 'fa fa-shopping-cart', WebOrder::class)
                        ->setController(WebOrderCrudController::class),
                ])
                ->setPermission('ROLE_ORDER'),
            MenuItem::subMenu('Amazon & FBA', 'fab fa-amazon')
                ->setSubItems([
                    MenuItem::linkToCrud('Inventory', 'fas fa-cube', Product::class)
                        ->setController(StockCrudController::class),
                    MenuItem::linkToCrud('Fees', 'fas fa-money-bill-alt', AmazonFinancialEvent::class),
                    MenuItem::linkToCrud('FBA Removal', 'fas fa-exchange-alt', AmazonRemoval::class),
                    //MenuItem::linkToCrud('FBA Returns', 'fas fa-exchange-alt', FbaReturn::class),
                ])
                ->setPermission('ROLE_AMAZON'),
            MenuItem::subMenu('Pricing', 'fas fa-money-bill')
                ->setSubItems([
                    MenuItem::linkToCrud(
                        'Prices',
                        'fas fa-barcode',
                        Product::class
                    )->setController(PricingCrudController::class),
                    
                    MenuItem::linkToCrud(
                        'Promotion',
                        'fas fa-percentage',
                        Promotion::class
                    ),
                    MenuItem::linkToCrud(
                        'Import',
                        'fas fa-tasks',
                        ImportPricing::class
                    ),
                    MenuItem::linkToCrud(
                        'Sales channel',
                        'fas fa-store-alt',
                        SaleChannel::class
                    ),
                ])->setPermission('ROLE_PRICING'),
            MenuItem::subMenu('Configuration', 'fas fa-cogs')
                ->setSubItems([
                    MenuItem::linkToCrud(
                        'Product',
                        'fas fa-barcode',
                        Product::class
                    )->setController(ProductCrudController::class)->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToCrud(
                        'Brand',
                        'far fa-registered',
                        Brand::class
                    )->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToCrud(
                        'Category',
                        'fas fa-sitemap',
                        Category::class
                    )->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToCrud(
                        'Logistic class',
                        'fas fa-shipping-fast',
                        LogisticClass::class
                    )->setController(LogisticClassCrudController::class)->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToCrud(
                        'SKU Mapping',
                        'fa fa-exchange',
                        ProductCorrelation::class
                    )->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToCrud(
                        'Integration channel',
                        'fas fa-stream',
                        IntegrationChannel::class
                    )->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToCrud(
                        'Order error log',
                        'fas fa-bug',
                        OrderLog::class
                    )->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToCrud(
                        'Users',
                        'fa fa-user',
                        User::class
                    )->setPermission('ROLE_ADMIN'),
                    ])->setPermission('ROLE_ADMIN'),
                    MenuItem::linkToRoute('Help', 'fas fa-question-circle', 'help'),
        ];
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addWebpackEncoreEntry('app');
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
