<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Entity\ImportPricing;
use App\Entity\Promotion;
use App\Entity\SaleChannel;
use App\Form\ConfirmImportPricingFormType;
use App\Form\ImportPricingFormType;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use function Symfony\Component\String\u;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImportPricingCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return ImportPricing::class;
    }


    public function getDefautOrder(): array
    {
        return ['id' => "DESC"];
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('statusLitteral', 'Status')->hideOnForm()->setTemplatePath('admin/fields/importPricing/jobStatus.html.twig'),
            TextField::new('jobLitteral', 'Job type')->hideOnForm(),
            TextField::new('username')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
            ArrayField::new('content')->setTemplatePath('admin/fields/importPricing/contentJob.html.twig')->onlyOnDetail(),
            ArrayField::new('getWarningErrorLogs', 'Warnings and errors')->setTemplatePath('admin/fields/importPricing/logs.html.twig')->onlyOnDetail(),
            ArrayField::new('logs')->setTemplatePath('admin/fields/importPricing/logs.html.twig')->onlyOnDetail(),
        ];
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setEntityPermission('ROLE_PRICING');
        return $crud;
    }


    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(CRUD::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->disable(Action::SAVE_AND_ADD_ANOTHER)
            ->add(
                Crud::PAGE_INDEX,
                Action::new('importPromotions', 'Import promotions')
                    ->setIcon('fa fa-plus')
                    ->createAsGlobalAction()
                    ->linkToCrudAction('importPromotions')
            )
                ->add(
                    Crud::PAGE_INDEX,
                    Action::new('importPricings', 'Import pricings')
                        ->setIcon('fa fa-plus')
                        ->createAsGlobalAction()
                        ->linkToCrudAction('importPricings')
                )
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel("");
            });
    }



    public function createCsvSampleFile(Request $request)
    {
        $typeFile = $request->get('typeFile', 'csv');
        $importType = $request->get('typeImport', ImportPricing::Type_Import_Pricing);
        if ($typeFile=='csv') {
            $writer = WriterEntityFactory::createCSVWriter();
            $writer->setFieldDelimiter(';');
        } else {
            $writer = WriterEntityFactory::createXLSXWriter();
        }

        if ($importType == ImportPricing::Type_Import_Pricing) {
            $saleChannels = $this->container->get('doctrine')->getManager()->getRepository(SaleChannel::class)->findAll();
            $lines = [ 'sku' ];
            foreach ($saleChannels as $saleChannel) {
                $lines[]=$saleChannel->getCode().'-enabled';
                $lines[]=$saleChannel->getCode().'-price';
            }
        } else {
            $lines = [
                    'skus',
                    'beginDate',
                    'endDate',
                    'saleChannels',
                    'priority',
                    'type',
                    'amount',
                    'frequency',
                    'comment',
                    'weekDays',
                    'beginHour',
                    'endHour',
            ];
        }

        $fileName = u($importType.' ' . date('Ymd His'))->snake() . '.'.$typeFile;
        
        $h = fopen('php://output', 'r');
        $writer->openToBrowser($fileName);
        $singleRow = WriterEntityFactory::createRowFromArray($lines);
        $writer->addRow($singleRow);
        $writer->close();
        return new Response(stream_get_contents($h));
    }
      





    private function import(ImportPricing $import)
    {
        $import->setUser($this->getUser());
        $import->setStatus(ImportPricing::Status_Created);
        $datas = $this->importDatas($import->uploadedFile);
        
        $import->setContent($datas);
        $manager =  $this->container->get('doctrine')->getManager();
        $manager->persist($import);
        
        $manager->flush();
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setAction("confirm")
            ->setEntityId($import->getId())
            ->generateUrl();
        return $this->redirect($url);
    }



    public function importPromotions(AdminContext $context)
    {
        $import = new ImportPricing();
        $import->setImportType(ImportPricing::Type_Import_Promotion);
        $form = $this->createForm(ImportPricingFormType::class, $import);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->import($import);
        }
        $saleChannels = $this->container->get('doctrine')->getManager()->getRepository(SaleChannel::class)->findAll();
        return $this->renderForm('admin/crud/importPricing/import.html.twig', ['form' => $form, 'import' => $import, 'saleChannels'=> $saleChannels]);
    }


    public function importPricings(AdminContext $context)
    {
        return $this->importForm($context, ImportPricing::Type_Import_Pricing);
    }


    public function importForm(AdminContext $context, $type)
    {
        $import = new ImportPricing();
        $import->setImportType($type);
        $form = $this->createForm(ImportPricingFormType::class, $import);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->import($import);
        }
        $saleChannels = $this->container->get('doctrine')->getManager()->getRepository(SaleChannel::class)->findAll();
        return $this->renderForm('admin/crud/importPricing/import.html.twig', ['form' => $form, 'import' => $import, 'saleChannels'=> $saleChannels]);
    }







    public function confirm(AdminContext $context)
    {
        $import = $context->getEntity()->getInstance();
        if ($import->getStatus() != ImportPricing::Status_Created) {
            $this->createAccessDeniedException('Import already confirmed');
        }
        $form = $this->createForm(ConfirmImportPricingFormType::class, $import);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            /**@var App\Entity\User */
            $user = $this->getUser();

            /**@var Symfony\Component\Form\ClickableInterface */
            $btnToImport = $form->get('toImport');
            if ($btnToImport->isClicked()) {
                $nextAction = ImportPricing::Status_ToImport;
                $import->addLog('Content confirmed by ' . $user->getUserIdentifier());
                $this->addFlash('success', 'Your job will be launched soon. You will get a confirmation email ' . $user->getEmail() . ' once finished');
            } else {
                $nextAction = ImportPricing::Status_Cancelled;
                $import->addLog('Job cancelled by ' . $user->getUserIdentifier());
                $this->addFlash('success', 'You cancelled your job.');
            }
            $import->setStatus($nextAction);
            $this->container->get('doctrine')->getManager()->flush();
            return $this->redirect(
                $this->container
                    ->get(AdminUrlGenerator::class)
                    ->setAction(Action::INDEX)
                    ->setEntityId(null)
                    ->generateUrl()
            );
        }
        return $this->renderForm('admin/crud/importPricing/confirm.html.twig', ['form' => $form, 'import' => $import]);
    }




    private function importDatas(UploadedFile $uploadedFile)
    {
        $header = null;
        $datas = [];

        if (substr($uploadedFile->getClientOriginalName(), -3) == 'csv') {
            $reader = ReaderEntityFactory::createCSVReader($uploadedFile->getClientOriginalName());
            $reader->setFieldDelimiter(';');
            $reader->setFieldEnclosure('"');
        } else {
            $reader = ReaderEntityFactory::createReaderFromFile($uploadedFile->getClientOriginalName());
        }

        $reader->open($uploadedFile->getPathname());

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if (!$header) {
                    foreach($row->getCells() as $cell){
                        $header[] = $cell->getValue();
                    }
                } else {
                    $cells = $row->toArray();
                    if (count($cells) == count($header)) {
                        $dataLines = array_combine($header, $cells);
                        $datas[] = $dataLines;
                    }
                }
            }
            return $datas;
        }
    }
}
