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
final class ConversationsController extends AbstractController
{
    #[Route('/conversations', name: 'app_conversations', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $inquiries  = $this->sampleInquiries();
        $selectedId = $request->query->getInt('id', $inquiries[0]['id']);

        $selected = $inquiries[0];
        foreach ($inquiries as $inquiry) {
            if ($inquiry['id'] === $selectedId) {
                $selected = $inquiry;
                break;
            }
        }

        return $this->render('conversations/index.html.twig', [
            'inquiries'    => $inquiries,
            'selected'     => $selected,
            'total_unread' => array_sum(array_column($inquiries, 'unread')),
        ]);
    }

    #[Route(
        '/conversations/{id}/{action}',
        name: 'app_conversations_action',
        requirements: ['id' => '\d+', 'action' => 'reply|close'],
        methods: ['POST']
    )]
    public function action(int $id, string $action, Request $request): RedirectResponse
    {
        // Later: save the reply / close the inquiry in the database here.
        if ($action === 'reply') {
            $message = trim($request->request->getString('message'));

            if ($message === '') {
                $this->addFlash('error', 'Please type a message before sending.');
            } else {
                $this->addFlash('success', 'Reply sent to the customer.');
            }
        } else {
            $this->addFlash('info', "Inquiry #$id closed.");
        }

        return $this->redirectToRoute('app_conversations', ['id' => $id]);
    }

    private function sampleInquiries(): array
    {
        return [
            [
                'id' => 1, 'customer' => 'Maria Santos', 'subject' => 'Airport pickup for Executive Sedan', 'status' => 'Open', 'unread' => 0,
                'messages' => [
                    ['from' => 'customer', 'text' => 'Hi! Can I pick up the Executive Sedan at the airport on Oct 9?', 'time' => 'Oct 8, 9:15 AM'],
                    ['from' => 'admin',    'text' => 'Hello Maria! Yes, we can arrange that. Please send us your flight time.', 'time' => 'Oct 8, 9:40 AM'],
                    ['from' => 'customer', 'text' => 'My flight lands at 2:30 PM. Thank you!', 'time' => 'Oct 8, 10:02 AM'],
                ],
            ],
            [
                'id' => 2, 'customer' => 'Juan Dela Cruz', 'subject' => 'Can the Family SUV fit 5 adults?', 'status' => 'Open', 'unread' => 1,
                'messages' => [
                    ['from' => 'customer', 'text' => 'Can the Family SUV fit 5 adults with luggage for a 4-day trip?', 'time' => 'Oct 10, 8:20 AM'],
                ],
            ],
            [
                'id' => 3, 'customer' => 'Ana Reyes', 'subject' => 'Documents for Luxury Sports', 'status' => 'Closed', 'unread' => 0,
                'messages' => [
                    ['from' => 'customer', 'text' => 'What documents do I need to rent the Luxury Sports?', 'time' => 'Oct 6, 3:10 PM'],
                    ['from' => 'admin',    'text' => 'A valid driver\'s license and one government ID, Ana.', 'time' => 'Oct 6, 3:25 PM'],
                ],
            ],
            [
                'id' => 4, 'customer' => 'Liza Fernandez', 'subject' => 'Payment reference sent', 'status' => 'Open', 'unread' => 1,
                'messages' => [
                    ['from' => 'customer', 'text' => 'I sent my payment reference this morning. Can you confirm it?', 'time' => 'Oct 11, 7:45 AM'],
                ],
            ],
        ];
    }
}