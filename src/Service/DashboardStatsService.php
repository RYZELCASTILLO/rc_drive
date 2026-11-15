<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Repository\VehicleRepository;

/**
 * Collects every number shown on the admin dashboard, so the controller stays thin.
 */
final class DashboardStatsService
{
    public function __construct(
        private readonly VehicleRepository $vehicles,
        private readonly BookingRepository $bookings,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $activeByVehicle = $this->bookings->countActiveByVehicle();

        $brandStats    = [];
        $totalFleet    = 0;
        $availableCars = 0;

        foreach ($this->vehicles->findAllOrdered() as $vehicle) {
            $booked    = $activeByVehicle[$vehicle->getId()] ?? 0;
            $available = max(0, $vehicle->getTotalUnits() - $booked);

            $brandStats[] = [
                'name'        => $vehicle->getName(),
                'brand'       => $vehicle->getBrand(),
                'price'       => (float) $vehicle->getPricePerDay(),
                'total_units' => $vehicle->getTotalUnits(),
                'available'   => $available,
                'booked'      => $booked,
            ];

            $totalFleet    += $vehicle->getTotalUnits();
            $availableCars += $available;
        }

        $allBookings      = $this->bookings->search(null, null);
        $rows             = [];
        $totalRented      = 0;
        $totalIncome      = 0.0;
        $pendingApprovals = 0;
        $cancelRequests   = 0;
        $pendingPayments  = 0;

        foreach ($allBookings as $booking) {
            $status = $booking->getStatus();

            if (in_array($status, Booking::RENTED_STATUSES, true)) {
                $totalRented++;
                $totalIncome += (float) $booking->getTotalFee();
            }
            if ($status === 'Pending') {
                $pendingApprovals++;
            }
            if ($status === 'Cancel Requested') {
                $cancelRequests++;
            }
            if ($booking->getPaymentStatus() === 'Pending Verification') {
                $pendingPayments++;
            }

            $rows[] = [
                'id'             => $booking->getId(),
                'customer'       => $booking->getCustomerName(),
                'vehicle'        => $booking->getVehicle()?->getName() ?? '',
                'pickup'         => $booking->getPickupDate()?->format('Y-m-d'),
                'return'         => $booking->getReturnDate()?->format('Y-m-d'),
                'total'          => (float) $booking->getTotalFee(),
                'status'         => $status,
                'payment_status' => $booking->getPaymentStatus(),
                'badge'          => $this->statusBadgeClass($status),
            ];
        }

        return [
            'total_fleet'       => $totalFleet,
            'available_cars'    => $availableCars,
            'total_rented'      => $totalRented,
            'total_income'      => $totalIncome,
            'pending_approvals' => $pendingApprovals,
            'cancel_requests'   => $cancelRequests,
            'pending_payments'  => $pendingPayments,
            'total_users'       => $this->users->count([]),
            'total_staff'       => $this->users->countByRole(User::ROLE_STAFF),
            'total_records'     => count($allBookings),
            'brand_stats'       => $brandStats,
            'bookings'          => $rows,
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
}