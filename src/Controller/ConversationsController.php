<?php

namespace App\Controller;

use App\Entity\Inquiry;
use App\Entity\InquiryMessage;
use App\Repository\InquiryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class ConversationsController extends AbstractController
{
    #[Route('/conversations', name: 'app_conversations', methods: ['GET'])]
    public function index(Request $request, InquiryRepository $repository, EntityManagerInterface $em): Response
    {
        $inquiries  = $repository->findInbox();
        $selectedId = $request->query->getInt('id');

        $selected = null;
        foreach ($inquiries as $inquiry) {
            if ($inquiry->getId() === $selectedId) {
                $selected = $inquiry;
            }
        }
        $selected ??= $inquiries[0] ?? null;

        // Opening a conversation marks the customer's messages as read
        if ($selected) {
            $selected->markAsRead();
            $em->flush();
        }

        $list = [];
        foreach ($inquiries as $inquiry) {
            $list[] = [
                'id'       => $inquiry->getId(),
                'customer' => $inquiry->getCustomerName(),
                'subject'  => $inquiry->getSubject(),
                'status'   => $inquiry->getStatus(),
                'unread'   => $inquiry->getUnreadCount(),
            ];
        }

        $thread = null;
        if ($selected) {
            $messages = [];
            foreach ($selected->getMessages() as $message) {
                $messages[] = [
                    'from' => $message->getSender(),
                    'text' => $message->getBody(),
                    'time' => $message->getCreatedAt()->format('M j, g:i A'),
                ];
            }

            $thread = [
                'id'       => $selected->getId(),
                'customer' => $selected->getCustomerName(),
                'subject'  => $selected->getSubject(),
                'status'   => $selected->getStatus(),
                'messages' => $messages,
            ];
        }

        return $this->render('conversations/index.html.twig', [
            'inquiries'    => $list,
            'selected'     => $thread,
            'total_unread' => array_sum(array_column($list, 'unread')),
        ]);
    }

    #[Route(
        '/conversations/{id}/{action}',
        name: 'app_conversations_action',
        requirements: ['id' => '\d+', 'action' => 'reply|close'],
        methods: ['POST']
    )]
    public function action(
        Inquiry $inquiry,
        string $action,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): RedirectResponse {
        $id = $inquiry->getId();

        if (!$this->isCsrfTokenValid('conversation_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_conversations', ['id' => $id]);
        }

        if ($action === 'close') {
            $inquiry->setStatus(Inquiry::STATUS_CLOSED);
            $em->flush();

            $this->addFlash('info', "Inquiry #$id closed.");

            return $this->redirectToRoute('app_conversations', ['id' => $id]);
        }

        if ($inquiry->getStatus() === Inquiry::STATUS_CLOSED) {
            $this->addFlash('error', 'This inquiry is closed. You cannot reply to it.');

            return $this->redirectToRoute('app_conversations', ['id' => $id]);
        }

        $message = (new InquiryMessage())
            ->setSender(InquiryMessage::FROM_ADMIN)
            ->setBody($request->request->getString('message'))
            ->setIsRead(true);

        $errors = $validator->validate($message);
        if (count($errors) > 0) {
            $this->addFlash('error', $errors->get(0)->getMessage());

            return $this->redirectToRoute('app_conversations', ['id' => $id]);
        }

        $inquiry->addMessage($message);
        $em->persist($message);
        $em->flush();

        $this->addFlash('success', 'Reply sent to the customer.');

        return $this->redirectToRoute('app_conversations', ['id' => $id]);
    }
}