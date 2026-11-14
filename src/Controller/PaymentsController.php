<?php

namespace App\Controller;

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
    public function index(Request $request): Response
    {
        $all      = $this->samplePayments();
        $statuses = ['Pending Verification', 'Paid', 'Partial', 'Unpaid'];

        $filter = $request->query->getString('status', 'All');
        if (!in_array($filter, $statuses, true)) {
            $filter = 'All';
        }

        $counts         = array_fill_keys($statuses, 0);
        $verifiedTotal  = 0;
        $awaitingAmount = 0;

        foreach ($all as $payment) {
            $counts[$payment['status']]++;

            if (in_array($payment['status'], ['Paid', 'Partial'], true)) {
                $verifiedTotal += $payment['paid'];
            }
            if ($payment['status'] === 'Pending Verification') {
                $awaitingAmount += $payment['paid'];
            }
        }

        $payments = array_values(array_filter(
            $all,
            fn (array $p) => $filter === 'All' || $p['status'] === $filter
        ));

        foreach ($payments as $key => $payment) {
            $payments[$key]['balance'] = $payment['total'] - $payment['paid'];
            $payments[$key]['badge']   = $this->paymentBadgeClass($payment['status']);
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
    public function action(int $id, string $action): RedirectResponse
    {
        // Later: update the payment status in the database here.
        if ($action === 'verify') {
            $this->addFlash('success', "Payment for booking #$id verified.");
        } else {
            $this->addFlash('error', "Payment for booking #$id rejected. The customer will be asked to pay again.");
        }

        return $this->redirectToRoute('app_payments');
    }

    private function samplePayments(): array
    {
        return [
            ['booking_id' => 1, 'customer' => 'Maria Santos',    'vehicle' => 'Executive Sedan', 'total' => 7500,  'paid' => 7500,  'type' => 'Full',    'reference' => 'GC-1009-4821', 'status' => 'Paid'],
            ['booking_id' => 2, 'customer' => 'Juan Dela Cruz',  'vehicle' => 'Family SUV',      'total' => 15200, 'paid' => 0,     'type' => '',        'reference' => '',             'status' => 'Unpaid'],
            ['booking_id' => 3, 'customer' => 'Ana Reyes',       'vehicle' => 'Luxury Sports',   'total' => 13000, 'paid' => 6500,  'type' => 'Partial', 'reference' => 'GC-1008-7731', 'status' => 'Partial'],
            ['booking_id' => 4, 'customer' => 'Carlo Mendoza',   'vehicle' => 'Executive Sedan', 'total' => 5000,  'paid' => 0,     'type' => '',        'reference' => '',             'status' => 'Unpaid'],
            ['booking_id' => 5, 'customer' => 'Liza Fernandez',  'vehicle' => 'Family SUV',      'total' => 11400, 'paid' => 11400, 'type' => 'Full',    'reference' => 'GC-1012-3390', 'status' => 'Pending Verification'],
            ['booking_id' => 6, 'customer' => 'Mark Villanueva', 'vehicle' => 'Executive Sedan', 'total' => 7500,  'paid' => 7500,  'type' => 'Full',    'reference' => 'GC-1001-1158', 'status' => 'Paid'],
            ['booking_id' => 7, 'customer' => 'Rhea Gonzales',   'vehicle' => 'Family SUV',      'total' => 11400, 'paid' => 0,     'type' => '',        'reference' => '',             'status' => 'Unpaid'],
        ];
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