<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BookingsController extends AbstractController
{
    private const LATE_FEE_PER_DAY = 1000; // same late fee as your old project

    #[Route('/bookings', name: 'app_bookings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $allBookings = $this->sampleBookings();
        $statuses = ['Pending', 'Approved', 'Picked Up', 'Cancel Requested', 'Returned', 'Rejected', 'Cancelled'];

        $filter = $request->query->getString('status', 'All');
        if (!in_array($filter, $statuses, true)) {
            $filter = 'All';
        }

        $counts = array_fill_keys($statuses, 0);
        foreach ($allBookings as $booking) {
            $counts[$booking['status']]++;
        }

        $bookings = array_values(array_filter(
            $allBookings,
            fn (array $b) => $filter === 'All' || $b['status'] === $filter
        ));

        foreach ($bookings as $key => $booking) {
            $bookings[$key]['badge']     = $this->statusBadgeClass($booking['status']);
            $bookings[$key]['pay_badge'] = $this->paymentBadgeClass($booking['payment_status']);
        }

        return $this->render('bookings/index.html.twig', [
            'bookings' => $bookings,
            'counts'   => $counts,
            'filter'   => $filter,
            'total'    => count($allBookings),
        ]);
    }

    #[Route(
        '/bookings/{id}/{action}',
        name: 'app_bookings_action',
        requirements: ['id' => '\d+', 'action' => 'approve|reject|pickup|return|cancel|approve-cancel|decline-cancel'],
        methods: ['POST']
    )]
    public function action(int $id, string $action, Request $request): RedirectResponse
    {
        // Later: update the booking in the database here.
        [$type, $text] = match ($action) {
            'approve'        => ['success', "Booking #$id approved."],
            'reject'         => ['error',   "Booking #$id rejected."],
            'pickup'         => ['success', "Booking #$id marked as picked up."],
            'return'         => ['success', $this->returnMessage($id, $request)],
            'cancel'         => ['error',   "Booking #$id cancelled."],
            'approve-cancel' => ['success', "Cancellation for booking #$id approved. The car is available again."],
            'decline-cancel' => ['info',    "Cancellation request for booking #$id declined. The booking stays approved."],
            default          => ['info',    'Nothing changed.'],
        };

        $this->addFlash($type, $text);

        return $this->redirectToRoute('app_bookings');
    }

    private function returnMessage(int $id, Request $request): string
    {
        $daysLate = max(0, $request->request->getInt('days_late'));
        $fee      = $daysLate * self::LATE_FEE_PER_DAY;

        $text = "Booking #$id marked as returned.";
        if ($fee > 0) {
            $text .= ' Late fee: ₱' . number_format($fee) . " ($daysLate day(s) x ₱" . number_format(self::LATE_FEE_PER_DAY) . ').';
        }

        return $text;
    }

    private function sampleBookings(): array
    {
        return [
            ['id' => 1, 'customer' => 'Maria Santos',    'phone' => '0917 123 4567', 'vehicle' => 'Executive Sedan', 'pickup' => '2026-10-09', 'return' => '2026-10-12', 'total' => 7500,  'status' => 'Approved',         'payment_status' => 'Paid',                 'note' => ''],
            ['id' => 2, 'customer' => 'Juan Dela Cruz',  'phone' => '0918 234 5678', 'vehicle' => 'Family SUV',      'pickup' => '2026-10-10', 'return' => '2026-10-14', 'total' => 15200, 'status' => 'Pending',          'payment_status' => 'Unpaid',               'note' => ''],
            ['id' => 3, 'customer' => 'Ana Reyes',       'phone' => '0919 345 6789', 'vehicle' => 'Luxury Sports',   'pickup' => '2026-10-08', 'return' => '2026-10-10', 'total' => 13000, 'status' => 'Picked Up',        'payment_status' => 'Partial',              'note' => ''],
            ['id' => 4, 'customer' => 'Carlo Mendoza',   'phone' => '0920 456 7890', 'vehicle' => 'Executive Sedan', 'pickup' => '2026-10-07', 'return' => '2026-10-09', 'total' => 5000,  'status' => 'Cancel Requested', 'payment_status' => 'Unpaid',               'note' => 'Reason: Change of travel date'],
            ['id' => 5, 'customer' => 'Liza Fernandez',  'phone' => '0921 567 8901', 'vehicle' => 'Family SUV',      'pickup' => '2026-10-12', 'return' => '2026-10-15', 'total' => 11400, 'status' => 'Approved',         'payment_status' => 'Pending Verification', 'note' => ''],
            ['id' => 6, 'customer' => 'Mark Villanueva', 'phone' => '0922 678 9012', 'vehicle' => 'Executive Sedan', 'pickup' => '2026-10-01', 'return' => '2026-10-04', 'total' => 7500,  'status' => 'Returned',         'payment_status' => 'Paid',                 'note' => ''],
            ['id' => 7, 'customer' => 'Rhea Gonzales',   'phone' => '0923 789 0123', 'vehicle' => 'Family SUV',      'pickup' => '2026-10-02', 'return' => '2026-10-05', 'total' => 11400, 'status' => 'Rejected',         'payment_status' => 'Unpaid',               'note' => ''],
        ];
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'Approved'         => 'dash-badge-approved',
            'Picked Up'        => 'dash-badge-pickedup',
            'Returned'         => 'dash-badge-returned',
            'Cancel Requested' => 'dash-badge-cancelreq',
            'Cancelled'        => 'dash-badge-cancelled',
            'Rejected'         => 'dash-badge-rejected',
            default            => 'dash-badge-pending',
        };
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