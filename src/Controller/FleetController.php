<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class FleetController extends AbstractController
{
    #[Route('/fleet', name: 'app_fleet', methods: ['GET'])]
    public function index(): Response
    {
        $vehicles       = $this->sampleVehicles();
        $totalUnits     = 0;
        $totalAvailable = 0;
        $totalBooked    = 0;

        foreach ($vehicles as $key => $vehicle) {
            $available = max(0, $vehicle['total_units'] - $vehicle['booked']);
            [$label, $class] = $this->stockStatus($vehicle['availability'], $available);

            $vehicles[$key]['available']    = $available;
            $vehicles[$key]['percent']      = $vehicle['total_units'] > 0 ? (int) round($available / $vehicle['total_units'] * 100) : 0;
            $vehicles[$key]['status_label'] = $label;
            $vehicles[$key]['status_class'] = $class;

            $totalUnits     += $vehicle['total_units'];
            $totalAvailable += $available;
            $totalBooked    += $vehicle['booked'];
        }

        return $this->render('fleet/index.html.twig', [
            'vehicles'        => $vehicles,
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
    public function action(int $id, string $action): RedirectResponse
    {
        $name = "Vehicle #$id";
        foreach ($this->sampleVehicles() as $vehicle) {
            if ($vehicle['id'] === $id) {
                $name = $vehicle['name'];
            }
        }

        // Later: update the "availability" column in the database here.
        if ($action === 'disable') {
            $this->addFlash('error', "$name marked as unavailable for new bookings.");
        } else {
            $this->addFlash('success', "$name is available for booking again.");
        }

        return $this->redirectToRoute('app_fleet');
    }

    private function sampleVehicles(): array
    {
        return [
            ['id' => 1, 'name' => 'Executive Sedan', 'brand' => 'Sedan',  'price_per_day' => 2500, 'total_units' => 20, 'booked' => 2, 'availability' => 'Available', 'image' => 'sedan.jpg'],
            ['id' => 2, 'name' => 'Family SUV',      'brand' => 'SUV',    'price_per_day' => 3800, 'total_units' => 20, 'booked' => 1, 'availability' => 'Available', 'image' => 'suv.jpg'],
            ['id' => 3, 'name' => 'Luxury Sports',   'brand' => 'Sports', 'price_per_day' => 6500, 'total_units' => 2,  'booked' => 1, 'availability' => 'Available', 'image' => 'sports.jpg'],
        ];
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