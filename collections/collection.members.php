<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\Auth;
use Forge\Core\Classes\Relations\CollectionRelation;
use Forge\Core\Classes\Relations\Enums\Directions;
use Forge\Core\Classes\User;

class MembersCollection extends DataCollection {
    public $permission = "manage.collection.teams";

    protected function setup() {
        $this->preferences['name'] = 'forge-members';
        $this->preferences['title'] = i('Members', 'forge-teams');
        $this->preferences['all-title'] = i('Manage members', 'forge-teams');
        $this->preferences['add-label'] = i('Add member', 'forge-teams');
        $this->preferences['single-item'] = i('Member', 'forge-teams');

        Auth::registerPermissions('api.collection.forge-members.read');

        $this->custom_fields();
    }

    private function custom_fields() {
        $users = [];
        $users[0] = i('Choose a user', 'ftt');
        foreach(User::getAll() as $user) {
            $users[$user['id']] = $user['username'].' ('.$user['email'].')';
        }
        $this->addFields([
            [
                'key' => 'user',
                'label' => i('User', 'ftt'),
                'multilang' => false,
                'type' => 'select',
                'chosen' => true,
                'order' => 30,
                'position' => 'left',
                'hint' => i('Direct relation to a user', 'ftt'),
                'values' => $users
            ]
        ]);
    }

}

?>