<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\Auth;
use Forge\Core\Classes\Utils;

class OrganizationsCollection extends DataCollection {
    public $permission = "manage.collection.organizations";

    protected function setup() {
        $this->preferences['name'] = 'forge-organizations';
        $this->preferences['title'] = i('Organizations', 'forge-organizations');
        $this->preferences['all-title'] = i('Manage organizations', 'forge-organizations');
        $this->preferences['add-label'] = i('Add organization', 'forge-organizations');
        $this->preferences['single-item'] = i('Organization', 'forge-organizations');
        $this->preferences['multilang'] = false;

        $this->custom_fields();
    }

    private function custom_fields() {
        $this->addFields([
            [
                'key' => 'shorttag',
                'label' => i('Tag', 'forge-organizations'),
                'multilang' => false,
                'type' => 'text',
                'order' => 30,
                'position' => 'right',
                'hint' => ''
            ],
            [
                'key' => 'logo',
                'label' => i('Teamlogo', 'forge-organizations'),
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
                'url' => 'teams',
                'title' => i('Teams', 'forge-organizations')
            ]
        ];
    }

    public function subviewTeams($itemId) {
        if (!Auth::allowed("manage.collection.organizations")) {
            return;
        }

        $teams = new Teams();
        $teams->setOrganization($itemId);
        if (Auth::allowed('manage.collection.organizations', true)) {
            $teams->isAdmin = true;
        }

        if (array_key_exists('deleteTeam', $_GET) && is_numeric($_GET['deleteTeam'])) {
            $teams->delete($_GET['deleteTeam']);
        }
        return $teams->renderTableBackend();
    }

    public function subviewTeamsActions($itemId) {
        return $this->app->render(CORE_TEMPLATE_DIR . "assets/", "overlay-button", array(
            'url' => Utils::getUrl(array('manage', 'collections', 'forge-organizations', 'assign', $itemId, 'forge-teams', 'add')),
            'label' => 'add team'
        ));
    }
}

?>
