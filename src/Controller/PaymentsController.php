<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class PaymentsController extends AbstractController
{
    #[Route('/payments', name: 'app_payments', methods: ['GET'])]
    public function index(Request $request, BookingRepository $repository): Response
    {
        $statuses = ['Pending Verification', 'Paid', 'Partial', 'Unpaid'];

        $filter = $request->query->getString('status', 'All');
        if (!in_array($filter, $statuses, true)) {
            $filter = 'All';
        }

        $all            = $repository->search(null, null);
        $counts         = array_fill_keys($statuses, 0);
        $verifiedTotal  = 0.0;
        $awaitingAmount = 0.0;
        $payments       = [];

        foreach ($all as $booking) {
            $status = $booking->getPaymentStatus();
            $paid   = (float) $booking->getAmountPaid();
            $total  = (float) $booking->getTotalFee();

            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            if (in_array($status, ['Paid', 'Partial'], true)) {
                $verifiedTotal += $paid;
            }
            if ($status === 'Pending Verification') {
                $awaitingAmount += $paid;
            }

            if ($filter === 'All' || $filter === $status) {
                $payments[] = [
                    'booking_id' => $booking->getId(),
                    'customer'   => $booking->getCustomerName(),
                    'vehicle'    => $booking->getVehicle()?->getName() ?? '',
                    'total'      => $total,
                    'paid'       => $paid,
                    'balance'    => $total - $paid,
                    'type'       => $booking->getPaymentType() ?? '',
                    'reference'  => $booking->getPaymentReference() ?? '',
                    'status'     => $status,
                    'badge'      => $this->paymentBadgeClass($status),
                ];
            }
        }

        return $this->render('payments/index.html.twig', [
            'payments'        => $payments,
            'counts'          => $counts,
            'filter'          => $filter,
            'total'           => count($all),
            'verified_total'  => $verifiedTotal,
            'awaiting_amount' => $awaitingAmount,
        ]);
    }

    #[Route(
        '/payments/{id}/{action}',
        name: 'app_payments_action',
        requirements: ['id' => '\d+', 'action' => 'verify|reject'],
        methods: ['POST']
    )]
    public function action(Booking $booking, string $action, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $id = $booking->getId();

        if (!$this->isCsrfTokenValid('payment_booking_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_payments');
        }

        if ($booking->getPaymentStatus() !== 'Pending Verification') {
            $this->addFlash('error', "The payment for booking #$id is not waiting for verification.");

            return $this->redirectToRoute('app_payments');
        }

        if ($action === 'verify') {
            $fullyPaid = (float) $booking->getAmountPaid() >= (float) $booking->getTotalFee();
            $booking->setPaymentStatus($fullyPaid ? 'Paid' : 'Partial');

            $this->addFlash('success', "Payment for booking #$id verified.");
        } else {
            $booking->setPaymentStatus('Unpaid');
            $booking->setAmountPaid('0.00');
            $booking->setPaymentType(null);
            $booking->setPaymentReference(null);

            $this->addFlash('error', "Payment for booking #$id rejected. The customer must pay again.");
        }

        $em->flush();

        return $this->redirectToRoute('app_payments');
    }

    private function paymentBadgeClass(string $status): string
    {
        return match ($status) {
            'Paid'                 => 'dash-badge-paid',
            'Partial'              => 'dash-badge-partial',
            'Pending Verification' => 'dash-badge-verify',
            default                => 'dash-badge-unpaid',
        };
    }
}