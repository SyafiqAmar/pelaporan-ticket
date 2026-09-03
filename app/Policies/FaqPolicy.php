<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    
    public function view(?User $user, Faq $faq): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Faq');
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->can('Update:Faq');
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->can('Delete:Faq');
    }
}
