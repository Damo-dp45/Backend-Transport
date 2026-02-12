<?php

namespace App\Controller;

use App\Entity\Ticket;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class TicketPrintController extends AbstractController
{
    public function __invoke(Ticket $data): Response
    {
        $html = $this->renderView('ticket/pdf.html.twig', [
            'ticket' => $data
        ]);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            ['Content-Type' => 'application/pdf']
        );
    }
}
