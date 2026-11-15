<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Repository\BookingRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class FleetController extends AbstractController
{
    #[Route('/fleet', name: 'app_fleet', methods: ['GET'])]
    public function index(VehicleRepository $vehicleRepository, BookingRepository $bookingRepository): Response
    {
        $activeByVehicle = $bookingRepository->countActiveByVehicle();

        $rows           = [];
        $totalUnits     = 0;
        $totalAvailable = 0;
        $totalBooked    = 0;

        foreach ($vehicleRepository->findAllOrdered() as $vehicle) {
            $units     = $vehicle->getTotalUnits();
            $booked    = $activeByVehicle[$vehicle->getId()] ?? 0;
            $available = max(0, $units - $booked);

            [$label, $class] = $this->stockStatus($vehicle->getAvailability(), $available);

            $rows[] = [
                'id'            => $vehicle->getId(),
                'name'          => $vehicle->getName(),
                'brand'         => $vehicle->getBrand(),
                'price_per_day' => (float) $vehicle->getPricePerDay(),
                'total_units'   => $units,
                'booked'        => $booked,
                'available'     => $available,
                'availability'  => $vehicle->getAvailability(),
                'image'         => $vehicle->getImage() ?? 'logo.png',
                'percent'       => $units > 0 ? (int) round($available / $units * 100) : 0,
                'status_label'  => $label,
                'status_class'  => $class,
            ];

            $totalUnits     += $units;
            $totalAvailable += $available;
            $totalBooked    += $booked;
        }

        return $this->render('fleet/index.html.twig', [
            'vehicles'        => $rows,
            'total_units'     => $totalUnits,
            'total_available' => $totalAvailable,
            'total_booked'    => $totalBooked,
        ]);
    }

    #[Route(
        '/fleet/{id}/{action}',
        name: 'app_fleet_action',
        requirements: ['id' => '\d+', 'action' => 'enable|disable'],
        methods: ['POST']
    )]
    public function action(Vehicle $vehicle, string $action, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('fleet_vehicle_' . $vehicle->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_fleet');
        }

        if ($action === 'disable') {
            $vehicle->setAvailability('Unavailable');
            $this->addFlash('error', $vehicle->getName() . ' is now unavailable for new bookings.');
        } else {
            $vehicle->setAvailability('Available');
            $this->addFlash('success', $vehicle->getName() . ' is available for booking again.');
        }

        $em->flush();

        return $this->redirectToRoute('app_fleet');
    }

    private function stockStatus(string $availability, int $available): array
    {
        if ($availability !== 'Available') {
            return ['Unavailable', 'dash-badge-cancelled'];
        }
        if ($available === 0) {
            return ['Fully Booked', 'dash-badge-rejected'];
        }
        if ($available <= 2) {
            return ['Limited', 'dash-badge-cancelreq'];
        }

        return ['Available', 'dash-badge-approved'];
    }
}