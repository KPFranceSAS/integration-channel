<?php

namespace App\Controller;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Entity\WebOrder;
use App\Helper\Utils\InvoiceDownload;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceController extends AbstractController
{
    /**
     * @Route("/invoice/aliexpress", name="invoice_list", methods={"GET","POST"})
     */
    public function getInvoice(Request $request, ManagerRegistry $doctrine, GadgetIberiaConnector $gadgetIberiaConnector, ApiAggregator $apiAggregator): Response
    {
        $invoice = new InvoiceDownload();
        $form = $this->createFormBuilder($invoice)
            ->add(
                'externalNumber',
                TextType::class,
                [
                    'label' => 'ID del pedido',
                    'help' => 'Por favor, introduzca su número de pedido de Aliexpress',
                    'required' => true
                ]
            )
            ->add(
                'dateInvoice',
                DateType::class,
                [
                    'label' => 'Fecha del pedido',
                    'help' => 'Por favor, introduzca la fecha de pedido de Aliexpress',
                    'widget' => 'single_text',
                    'required' => true
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'Descargo mi factura'
                ]
            )
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Doctrine\ORM\EntityManagerInterface */
            $manager = $doctrine->getManager();
            $qb = $manager->createQueryBuilder();
            $qb->select('w')
                ->from(WebOrder::class, 'w')
                ->where('w.externalNumber = :externalNumber')
                ->andWhere('w.channel IN (:channel)')
                ->andWhere('w.purchaseDate >= :date_start')
                ->andWhere('w.purchaseDate <= :date_end')
                ->setParameter('date_start', $invoice->getDateStartString())
                ->setParameter('date_end', $invoice->getDateEndString())
                ->setParameter('channel', [WebOrder::CHANNEL_ALIEXPRESS, WebOrder::CHANNEL_FITBITEXPRESS])
                ->setParameter('externalNumber', $invoice->externalNumber);


            $webOrders = $qb->getQuery()->getResult();
            $webOrder = count($webOrders) > 0 ? reset($webOrders) : null;
            if ($webOrder) {
                if ($webOrder->getStatus() == WebOrder::STATE_INVOICED) {
                    $orderAli = $apiAggregator->getApi($webOrder->getChannel())
                                            ->getOrder($webOrder->getExternalNumber());
                    if (!$orderAli) {
                        $this->addFlash('danger', 'Actualmente no podemos conectar con Aliexpress.');
                    } else {
                        if ($orderAli->order_status == "FINISH" && $orderAli->order_end_reason == "buyer_accept_goods") {
                            $invoice = $gadgetIberiaConnector->getSaleInvoiceByNumber($webOrder->getInvoiceErp());
                            $contentInvoice  = $gadgetIberiaConnector->getContentInvoicePdf($invoice['id']);
                            $response = new Response();
                            $response->headers->set('Cache-Control', 'private');
                            $response->headers->set('Content-type', "application/pdf");
                            $response->headers->set('Content-Disposition', 'attachment; filename="' . $webOrder->getExternalNumber() . '-' . $invoice['number'] . '.pdf";');
                            $response->headers->set('Content-length', strlen($contentInvoice));
                            $response->sendHeaders();
                            $response->setContent($contentInvoice);
                            return $response;
                        } else {
                            $this->addFlash('info', 'Tiene que confirmar la recepcion de tu pedido en Aliexpress para poder descargarlo.');
                        }
                    }
                } else {
                    $this->addFlash('info', 'Su factura aún no está lista. Tiene que esperar a que le enviemos su pedido para poder descargarlo.');
                }
            } else {
                $this->addFlash('danger', 'Actualmente no tenemos pedidos que coincidan con el número de Aliexpress que has introducido.');
            }
        }
        return $this->renderForm('invoice/aliexpress.html.twig', [
            'form' => $form,
        ]);
    }
}
