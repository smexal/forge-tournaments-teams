<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\CollectionQuery;
use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\Auth;
use Forge\Core\Classes\CollectionItem;
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
    }

    public function custom_fields() {
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

    public static function getByUser($user) {
        if(! is_object($user)) {
            $user = new User($user);
        }
        return self::createIfNotExists($user);
    }

    public static function getOwnedOrganizations($member) {
        if(!is_object($member)) {
            $member = new CollectionItem($member);
        }
        $found = CollectionQuery::items([
            'author' => $member->getAuthor(),
            'name' => 'forge-organizations'
        ]);
        return $found;
    }

    public static function createIfNotExists($user) {

        $found = CollectionQuery::items([
            'author' => $user->get('id'),
            'name' => 'forge-members'
        ]);

        // "member" item from this user already exists.
        if(count($found) > 0) {
            return $found[0]->getID();
        }

        $args = [
            'author' => $user->get('id'),
            'name' => $user->get('username'),
            'type' => 'forge-members'
        ];

        $meta = [
            [
                'keyy' => 'user',
                'value' => $user->get('id'),
                'lang' => 0
            ]
        ];

        return CollectionItem::create($args, $meta);
    }

}

?>