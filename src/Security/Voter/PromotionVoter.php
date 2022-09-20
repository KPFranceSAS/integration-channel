<?php

namespace App\Security\Voter;

use App\Entity\Promotion;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PromotionVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        if ($attribute!='EA_EXECUTE_ACTION') {
            return false;
        }
        if (!$subject) {
            return false;
        }

        return array_key_exists('action', $subject)
                && ($subject['action'] instanceof ActionDto || $subject['action'] == 'delete')
                && array_key_exists('entity', $subject)
                && $subject['entity'] instanceof EntityDto
                && $subject['entity']->getInstance()  instanceof Promotion;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        if ($subject['action'] instanceof ActionDto) {
            switch ($subject['action']->getName()) {
                case 'delete':
                case 'edit':
                    if ($user->hasRole('ROLE_PRICING') && $user->hasSaleChannel($subject['entity']->getInstance()->getSaleChannel())) {
                        return true;
                    }
                    // logic to determine if the user can EDIT
                    // return true or false
                    break;
                case 'saveAndAddAnother':
                case 'saveAndReturn':
                    if ($user->hasRole('ROLE_PRICING')) {
                        return true;
                    }
            }
        } elseif ($subject['action']== 'delete') {
            if ($user->hasRole('ROLE_PRICING') && $user->hasSaleChannel($subject['entity']->getInstance()->getSaleChannel())) {
                return true;
            }
        }
        

        return false;
    }
}
