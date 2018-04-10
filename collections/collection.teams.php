<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\Classes\CollectionItem;
use Forge\Core\Classes\Relations\Enums\Prepares;
use Forge\Core\Classes\Utils;

class TeamsCollection extends DataCollection {
    public $permission = "manage.collection.teams";

    protected function setup() {
        $this->preferences['name'] = 'forge-teams';
        $this->preferences['title'] = i('Teams', 'forge-teams');
        $this->preferences['all-title'] = i('Manage teams', 'forge-teams');
        $this->preferences['add-label'] = i('Add team', 'forge-teams');
        $this->preferences['single-item'] = i('Team', 'forge-teams');

        Auth::registerPermissions('api.collection.forge-teams.read');

        $this->custom_fields();
    }

    private function custom_fields() {
        $this->addFields([
            [
                'key' => 'shorttag',
                'label' => i('Tag', 'forge-teams'),
                'multilang' => false,
                'type' => 'text',
                'order' => 30,
                'position' => 'right',
                'hint' => ''
            ],
            [
                'key' => 'logo',
                'label' => i('Teamlogo', 'forge-teams'),
                'multilang' => false,
                'type' => 'image',
                'order' => 30,
                'position' => 'right',
                'hint' => ''
            ],
            [
                'key' => 'ftt_teams_members',
                'label' => \i('Team Members', 'ftt'),
                'values' => [],
                'value' => NULL,
                'multilang' => false,
                'type' => 'collection',
                'maxtags'=> 1,
                'collection' => 'forge-members',
                'data_source_save' => 'relation',
                'data_source_load' => 'relation',
                'relation' => [
                    'identifier' => 'ftt_teams_members'
                ],
                'order' => 10,
                'position' => 'left',
                'readonly' => true,
                'hint' => i('Assigned Members for this organization', 'ftt')
            ]
        ]);
    }

    public static function getOrganization($team) {
        if(is_object($team)) {
            $team = $team->id;
        }
        $relation = App::instance()->rd->getRelation('ftt_organization_teams');
        $orgas = $relation->getOfRight($team, Prepares::AS_IDS_LEFT);
        if(array_key_exists(0, $orgas)) {
            return $orgas[0];
        }
        return null;
    }

    public static function getMembers($item) {
        $relation = App::instance()->rd->getRelation('ftt_teams_members');
        if(is_object($item)) {
            return array_unique($relation->getOfLeft($item->getID(), Prepares::AS_IDS_RIGHT));
        } else {
            return array_unique($relation->getOfLeft($item, Prepares::AS_IDS_RIGHT));
        }
    }

    public static function getMembersAsUsers($item) {
        $members = self::getMembers($item);
        $users = [];
        foreach($members as $member) {
            $i = new CollectionItem($member);
            $users[] = $i->getMeta('user');
        }
        return $users;
    }

    public static function getName($item) {
        if(! is_object($item)) {
            $item = new CollectionItem($item);
        }
        return $item->getName();
    }

    public static function getMemberCount($item) {
        return count(self::getMembers($item));
    }

    /**
     * Register the subnavigations
     * @return array
     */
    public function getSubnavigation() {
        return [
            [
                'url' => 'members',
                'title' => i('Members', 'forge-teams')
            ]
        ];
    }

    public function subviewMembers($itemId) {
        if (!Auth::allowed("manage.collection.teams")) {
            return;
        }

        $members = new Members($itemId);
        if (Auth::allowed('manage.collection.teams', true)) {
            $members->isAdmin = true;
        }

        if (array_key_exists('deleteMember', $_GET) && is_numeric($_GET['deleteMember'])) {
            $members->delete($_GET['deleteMember']);
        }
        return $members->renderTable();
    }

    public function subviewMembersActions($itemId) {
        return $this->app->render(CORE_TEMPLATE_DIR . "assets/", "overlay-button", array(
            'url' => Utils::getUrl(array('manage', 'collections', 'members')),
            'label' => 'add member'
        ));
    }
}

?>
