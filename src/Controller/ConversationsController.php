<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConversationsController extends AbstractController
{
    #[Route('/conversations', name: 'app_conversations')]
    public function index(): Response
    {
        return $this->render('conversations/index.html.twig', [
            'controller_name' => 'ConversationsController',
        ]);
    }
}
