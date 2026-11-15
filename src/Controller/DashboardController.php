<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DashboardStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(DashboardStatsService $stats): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('dashboard/index.html.twig', ['admin_name' => $user->getName()] + $stats->build());
    }
}