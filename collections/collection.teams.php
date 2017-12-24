<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\Auth;
use Forge\Core\Classes\Utils;

class TeamsCollection extends DataCollection {
    public $permission = "manage.collection.teams";

    protected function setup() {
        $this->preferences['name'] = 'forge-teams';
        $this->preferences['title'] = i('Teams', 'forge-teams');
        $this->preferences['all-title'] = i('Manage teams', 'forge-teams');
        $this->preferences['add-label'] = i('Add team', 'forge-teams');
        $this->preferences['single-item'] = i('Team', 'forge-teams');

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
            ]
        ]);
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
