<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\App\ModifyHandler;
use Forge\Core\Classes\Media;
use Forge\Core\Classes\User;
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

    public function render($item) {
        $img = new Media($item->getMeta('logo'));
        $ownerUser = new User($item->getAuthor());
        return App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/', 'organization-detail', [
            'title' => $item->getMeta('title'),
            'description' => $item->getMeta('description'),
            'website_label' => i('Website', 'ftt'),
            'website_value' => $item->getMeta('website') ? $item->getMeta('website') : i('None', 'ftt'),
            'shorttag_label' => i('Shorttag', 'ftt'),
            'shorttag_value' => $item->getMeta('shorttag'),
            'members_label' => i('Members', 'ftt'),
            'members_value' => 1,
            'owner_value' => $ownerUser->get('username'),
            'owner_label' => i('Owner', 'ftt'),
            'team_image' => $img ? $img->getSizedImage(420, 280) : false,
            'tabs' => $this->getTeamTabs($item)
        ]);
    }

    private function getTeamTabs($item) {
        $tabs =  [
            [
                'active' => true,
                'key' => 'all_members',
                'name' => i('All Members', 'ftt')
            ],
        ];

        if(App::instance()->user->get('id') == $item->getAuthor()) {
            $tabs[] = [
                'active' => false,
                'key' => 'create',
                'name' => i('Create Team', 'ftt')
            ];
        }

        return App::instance()->render(CORE_TEMPLATE_DIR."assets/", "tabs", [
            'tabs' => $tabs,
            'tabs_content' => [
                [
                    'id' => 'all_members',
                    'active' => true,
                    'content' => 'yes'
                ],
                [
                    'id' => 'create',
                    'active' => false,
                    'content' => 'nope'
                ]
            ]
        ]);
    } 

    private function custom_fields() {
        $this->addFields([
            [
                'key' => 'shorttag',
                'label' => i('Tag', 'ftt'),
                'multilang' => false,
                'type' => 'text',
                'order' => 30,
                'position' => 'right',
                'hint' => ''
            ],
            [
                'key' => 'website',
                'label' => i('Website', 'ftt'),
                'multilang' => false,
                'type' => 'text',
                'order' => 31,
                'position' => 'right',
                'hint' => ''
            ],
            [
                'key' => 'logo',
                'label' => i('Teamlogo', 'ftt'),
                'multilang' => false,
                'type' => 'image',
                'order' => 32,
                'position' => 'right',
                'hint' => ''
            ],
            [
                'key' => 'ftt_organization_teams',
                'label' => \i('Teams', 'ftt'),
                'values' => [],
                'value' => NULL,
                'multilang' => false,
                'type' => 'collection',
                'maxtags'=> 1,
                'collection' => 'forge-teams',
                'data_source_save' => 'relation',
                'data_source_load' => 'relation',
                'relation' => [
                    'identifier' => 'ftt_organization_teams'
                ],

                'order' => 10,
                'position' => 'left',
                'readonly' => false,
                'hint' => i('Assigned Teams for this organization', 'ftt')
            ],
        ]);
        ModifyHandler::instance()->add(
            'Core/Manage/modifiyDefaultFields',
            function($fields, $name) {
                //$fields['title']['multilang'] = false;
                if($name == 'forge-organizations') {
                    // title
                    $fields[0]['multilang'] = false;

                    // description
                    $fields[1]['multilang'] = false;
                }
                return $fields;
            }
        );
    }

    /**
     * Register the subnavigations
     * @return array
     */
    public function getSubnavigation() {
        return [
            [
                'url' => 'teams',
                'title' => i('Teams', 'ftt')
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
