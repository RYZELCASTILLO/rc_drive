<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UsersController extends AbstractController
{
    #[Route('', name: 'app_users', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        return $this->render('users/index.html.twig', [
            'users' => $users->findBy([], ['id' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Account created for ' . $user->getName() . '.');

            return $this->redirectToRoute('app_users');
        }

        return $this->render('users/form.html.twig', [
            'form'  => $form,
            'title' => 'Create Account',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_users_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isSelf($user) && $user->getRole() !== User::ROLE_ADMIN) {
                $this->addFlash('error', 'You cannot remove your own admin role.');

                return $this->redirectToRoute('app_users_edit', ['id' => $user->getId()]);
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($hasher->hashPassword($user, (string) $plainPassword));
            }

            $em->flush();

            $this->addFlash('success', 'Account updated for ' . $user->getName() . '.');

            return $this->redirectToRoute('app_users');
        }

        return $this->render('users/form.html.twig', [
            'form'  => $form,
            'title' => 'Edit Account',
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_users_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(User $user, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('toggle_user_' . $user->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_users');
        }

        if ($this->isSelf($user)) {
            $this->addFlash('error', 'You cannot disable your own account.');

            return $this->redirectToRoute('app_users');
        }

        $user->setStatus($user->getStatus() === 'Active' ? 'Disabled' : 'Active');
        $em->flush();

        $this->addFlash('success', $user->getName() . ' is now ' . strtolower($user->getStatus()) . '.');

        return $this->redirectToRoute('app_users');
    }

    #[Route('/{id}/delete', name: 'app_users_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_users');
        }

        if ($this->isSelf($user)) {
            $this->addFlash('error', 'You cannot delete your own account.');

            return $this->redirectToRoute('app_users');
        }

        $name = $user->getName();
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Account of ' . $name . ' was deleted.');

        return $this->redirectToRoute('app_users');
    }

    private function isSelf(User $user): bool
    {
        $current = $this->getUser();

        return $current instanceof User && $current->getId() === $user->getId();
    }
}