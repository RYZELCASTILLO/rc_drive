<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides who may edit or delete a booking:
 *  - an admin: any booking
 *  - a staff member: only the bookings they created
 */
final class BookingVoter extends Voter
{
    public const EDIT   = 'BOOKING_EDIT';
    public const DELETE = 'BOOKING_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return true;
        }

        /** @var Booking $subject */
        $owner = $subject->getCreatedBy();

        return $owner !== null && $owner->getId() === $user->getId();
    }
}