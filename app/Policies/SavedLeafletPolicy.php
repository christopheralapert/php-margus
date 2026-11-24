<?php

namespace App\Policies;

use App\Models\SavedLeaflet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SavedLeafletPolicy
{
    public function view(User $user, SavedLeaflet $leaflet): Response
    {
        return $user->id === $leaflet->user_id ? Response::allow() : Response::denyAsNotFound();
    }

    public function download(User $user, SavedLeaflet $leaflet): Response
    {
        return $this->view($user, $leaflet);
    }

    public function delete(User $user, SavedLeaflet $leaflet): Response
    {
        return $this->view($user, $leaflet);
    }
}
