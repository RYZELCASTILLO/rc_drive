<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bookings')]
#[IsGranted('ROLE_STAFF')]
final class BookingsController extends AbstractController
{
    private const LATE_FEE_PER_DAY = 1000;

    #[Route('', name: 'app_bookings', methods: ['GET'])]
    public function index(Request $request, BookingRepository $repository): Response
    {
        $term   = trim($request->query->getString('q'));
        $status = $request->query->getString('status');
        if (!in_array($status, Booking::STATUSES, true)) {
            $status = '';
        }

        return $this->render('bookings/index.html.twig', [
            'bookings' => $repository->search($term, $status),
            'statuses' => Booking::STATUSES,
            'term'     => $term,
            'status'   => $status,
        ]);
    }

    #[Route('/new', name: 'app_bookings_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $booking = new Booking();
        $booking->setCreatedBy($user);

        $form = $this->createForm(BookingType::class, $booking, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'is_edit'  => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->recalculateTotal();
            $em->persist($booking);
            $em->flush();

            $this->addFlash('success', sprintf(
                'Booking created for %s. Total: ₱%s (%d day(s)).',
                $booking->getCustomerName(),
                number_format((float) $booking->getTotalFee(), 2),
                $booking->getRentalDays()
            ));

            return $this->redirectToRoute('app_bookings');
        }

        return $this->render('bookings/form.html.twig', [
            'form'  => $form,
            'title' => 'New Booking',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bookings_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('BOOKING_EDIT', subject: 'booking')]
    public function edit(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BookingType::class, $booking, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'is_edit'  => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->recalculateTotal();
            $em->flush();

            $this->addFlash('success', sprintf('Booking #%d updated. Total: ₱%s.', $booking->getId(), number_format((float) $booking->getTotalFee(), 2)));

            return $this->redirectToRoute('app_bookings');
        }

        return $this->render('bookings/form.html.twig', [
            'form'  => $form,
            'title' => 'Edit Booking #' . $booking->getId(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_bookings_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('BOOKING_DELETE', subject: 'booking')]
    public function delete(Booking $booking, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_booking_' . $booking->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_bookings');
        }

        $id = $booking->getId();
        $em->remove($booking);
        $em->flush();

        $this->addFlash('success', "Booking #$id was deleted.");

        return $this->redirectToRoute('app_bookings');
    }

    #[Route(
        '/{id}/status/{action}',
        name: 'app_bookings_status',
        requirements: ['id' => '\d+', 'action' => 'approve|reject|pickup|return'],
        methods: ['POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function status(Booking $booking, string $action, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('status_booking_' . $booking->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_bookings');
        }

        $id = $booking->getId();

        if ($action === 'return') {
            $daysLate = max(0, $request->request->getInt('days_late'));
            $lateFee  = $daysLate * self::LATE_FEE_PER_DAY;

            $booking->setStatus('Returned');
            $booking->setLateFee(number_format($lateFee, 2, '.', ''));

            $message = "Booking #$id marked as returned.";
            if ($lateFee > 0) {
                $message .= ' Late fee: ₱' . number_format($lateFee) . " ($daysLate day(s) x ₱" . number_format(self::LATE_FEE_PER_DAY) . ').';
            }
        } else {
            [$newStatus, $message] = match ($action) {
                'approve' => ['Approved', "Booking #$id approved."],
                'reject'  => ['Rejected', "Booking #$id rejected."],
                'pickup'  => ['Picked Up', "Booking #$id marked as picked up."],
            };
            $booking->setStatus($newStatus);
        }

        $em->flush();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_bookings');
    }
}