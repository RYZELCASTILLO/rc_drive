<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        // ---------- SAMPLE DATA (later: replace with database queries) ----------
        $vehicles = [
            ['name' => 'Executive Sedan', 'brand' => 'Sedan',  'price_per_day' => 2500, 'total_units' => 20],
            ['name' => 'Family SUV',      'brand' => 'SUV',    'price_per_day' => 3800, 'total_units' => 20],
            ['name' => 'Luxury Sports',   'brand' => 'Sports', 'price_per_day' => 6500, 'total_units' => 2],
        ];

        $bookings = [
            ['id' => 1, 'customer' => 'Maria Santos',     'vehicle' => 'Executive Sedan', 'pickup' => '2026-10-09', 'return' => '2026-10-12', 'total' => 7500,  'status' => 'Approved',         'payment_status' => 'Paid'],
            ['id' => 2, 'customer' => 'Juan Dela Cruz',   'vehicle' => 'Family SUV',      'pickup' => '2026-10-10', 'return' => '2026-10-14', 'total' => 15200, 'status' => 'Pending',          'payment_status' => 'Unpaid'],
            ['id' => 3, 'customer' => 'Ana Reyes',        'vehicle' => 'Luxury Sports',   'pickup' => '2026-10-08', 'return' => '2026-10-10', 'total' => 13000, 'status' => 'Picked Up',        'payment_status' => 'Partial'],
            ['id' => 4, 'customer' => 'Carlo Mendoza',    'vehicle' => 'Executive Sedan', 'pickup' => '2026-10-07', 'return' => '2026-10-09', 'total' => 5000,  'status' => 'Cancel Requested', 'payment_status' => 'Unpaid'],
            ['id' => 5, 'customer' => 'Liza Fernandez',   'vehicle' => 'Family SUV',      'pickup' => '2026-10-12', 'return' => '2026-10-15', 'total' => 11400, 'status' => 'Approved',         'payment_status' => 'Pending Verification'],
            ['id' => 6, 'customer' => 'Mark Villanueva',  'vehicle' => 'Executive Sedan', 'pickup' => '2026-10-01', 'return' => '2026-10-04', 'total' => 7500,  'status' => 'Returned',         'payment_status' => 'Paid'],
            ['id' => 7, 'customer' => 'Rhea Gonzales',    'vehicle' => 'Family SUV',      'pickup' => '2026-10-02', 'return' => '2026-10-05', 'total' => 11400, 'status' => 'Rejected',         'payment_status' => 'Unpaid'],
        ];

        // ---------- CALCULATIONS (same rules as your old dashboard.php) ----------
        $activeStatuses = ['Approved', 'Picked Up', 'Cancel Requested']; // these use up a car
        $rentedStatuses = ['Approved', 'Picked Up', 'Returned'];         // these count as rented + income

        $brandStats    = [];
        $totalFleet    = 0;
        $availableCars = 0;

        foreach ($vehicles as $vehicle) {
            $booked = 0;
            foreach ($bookings as $booking) {
                if ($booking['vehicle'] === $vehicle['name'] && in_array($booking['status'], $activeStatuses, true)) {
                    $booked++;
                }
            }
            $available = max(0, $vehicle['total_units'] - $booked);

            $brandStats[] = [
                'name'        => $vehicle['name'],
                'brand'       => $vehicle['brand'],
                'price'       => $vehicle['price_per_day'],
                'total_units' => $vehicle['total_units'],
                'available'   => $available,
                'booked'      => $booked,
            ];

            $totalFleet    += $vehicle['total_units'];
            $availableCars += $available;
        }

        $totalRented      = 0;
        $totalIncome      = 0;
        $pendingApprovals = 0;
        $cancelRequests   = 0;
        $pendingPayments  = 0;

        foreach ($bookings as $key => $booking) {
            if (in_array($booking['status'], $rentedStatuses, true)) {
                $totalRented++;
                $totalIncome += $booking['total'];
            }
            if ($booking['status'] === 'Pending') {
                $pendingApprovals++;
            }
            if ($booking['status'] === 'Cancel Requested') {
                $cancelRequests++;
            }
            if ($booking['payment_status'] === 'Pending Verification') {
                $pendingPayments++;
            }

            $bookings[$key]['badge'] = $this->statusBadgeClass($booking['status']);
        }

        return $this->render('dashboard/index.html.twig', [
            'admin_name'        => 'Admin',
            'total_fleet'       => $totalFleet,
            'available_cars'    => $availableCars,
            'total_rented'      => $totalRented,
            'total_income'      => $totalIncome,
            'pending_approvals' => $pendingApprovals,
            'cancel_requests'   => $cancelRequests,
            'pending_payments'  => $pendingPayments,
            'brand_stats'       => $brandStats,
            'bookings'          => $bookings,
        ]);
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
}