<?php

namespace App\Controller;

use AmazonPHP\SellingPartner\Model\FulfillmentInbound\Weight;
use App\Entity\WebOrder;
use App\Helper\Utils\InvoiceDownload;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function getInvoice(Request $request, GadgetIberiaConnector $gadgetIberiaConnector): Response
    {

        $invoice = new InvoiceDownload();
        $form = $this->createFormBuilder($invoice)
            ->add('externalNumber', TextType::class, ['label' => 'ID del pedido', 'help' => 'Por favor, introduzca su número de pedido de Aliexpress', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Descargo mi factura'])
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $webOrder = $this->getDoctrine()->getManager()->getRepository(WebOrder::class)->findOneBy(
                [
                    'externalNumber' => $invoice->externalNumber,
                    'channel' => WebOrder::CHANNEL_ALIEXPRESS
                ]
            );

            if ($webOrder) {
                if ($webOrder->getStatus() == WebOrder::STATE_INVOICED) {
                    $invoice = $gadgetIberiaConnector->getSaleInvoiceByNumber($webOrder->getInvoiceErp());
                    $contentInvoice  = $gadgetIberiaConnector->getContentInvoicePdf($invoice['id']);
                    $response = new Response();
                    $response->headers->set('Cache-Control', 'private');
                    $response->headers->set('Content-type', "application/pdf");
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $invoice['id'] . '-' . $invoice['number'] . '.pdf";');
                    $response->headers->set('Content-length', strlen($contentInvoice));
                    $response->sendHeaders();
                    $response->setContent($contentInvoice);
                    return $response;
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
