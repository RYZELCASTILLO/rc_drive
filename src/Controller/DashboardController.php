<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
        #[Route('', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        // ---------- DATA FROM THE DATABASE ----------
        $vehicles = [];
        foreach ($em->getRepository(Vehicle::class)->findAll() as $vehicle) {
            $vehicles[] = [
                'name'          => $vehicle->getName(),
                'brand'         => $vehicle->getBrand(),
                'price_per_day' => (float) $vehicle->getPricePerDay(),
                'total_units'   => $vehicle->getTotalUnits(),
            ];
        }

        $bookings = [];
        foreach ($em->getRepository(Booking::class)->findBy([], ['id' => 'DESC']) as $booking) {
            $bookings[] = [
                'id'             => $booking->getId(),
                'customer'       => $booking->getCustomerName(),
                'vehicle'        => $booking->getVehicle()->getName(),
                'pickup'         => $booking->getPickupDate()->format('Y-m-d'),
                'return'         => $booking->getReturnDate()->format('Y-m-d'),
                'total'          => (float) $booking->getTotalFee(),
                'status'         => $booking->getStatus(),
                'payment_status' => $booking->getPaymentStatus(),
            ];
        }

        // ---------- CALCULATIONS (same rules as before) ----------
        $activeStatuses = ['Approved', 'Picked Up', 'Cancel Requested'];
        $rentedStatuses = ['Approved', 'Picked Up', 'Returned'];

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