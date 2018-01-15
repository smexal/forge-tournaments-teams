<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\App\ModifyHandler;
use Forge\Core\Classes\Fields;
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

        Auth::registerPermissions('api.collection.forge-organizations.read');

        $this->custom_fields();
    }

    private function isOwner($item) {
        if(Auth::any() && App::instance()->user->get('id') == $item->getAuthor()) {
            return true;
        }
        return;
    }

    public function render($item) {
        $img = new Media($item->getMeta('logo'));
        $ownerUser = new User($item->getAuthor());

        $url_parts = Utils::getUriComponents();
        if(count($url_parts) > 3 && $url_parts[3] == 'create') {
            if($this->isOwner($item)) {
                return $this->createTeamContent();
            } else {
                App::instance()->redirect('denied');
            }
        }

        $actions = false;
        if($this->isOwner($item)) {
            $actions = true;
        }

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
            'tabs' => $this->getTeamTabs($item),
            'actions' => $actions,
            'create_team_label' => i('Create team'),
            'create_team_url' => Utils::getCurrentUrl().'/create'
        ]);
    }

    private function getTeamTabs($item) {
        $tabs =  [
            [
                'active' => true,
                'key' => 'all_members',
                'name' => i('Members', 'ftt')
            ],
        ];

        return App::instance()->render(CORE_TEMPLATE_DIR."assets/", "tabs", [
            'tabs' => $tabs,
            'tabs_content' => [
                [
                    'id' => 'all_members',
                    'active' => true,
                    'content' => 'yes'
                ]
            ]
        ]);
    }

    private function createTeamContent() {
        $heading = '<h3>'.i('Create a new Team', 'ftt').'</h3>';
        $content = [];
        $content[] = Fields::text([
            'label' => i('Team Name', 'ftt'),
            'key' => 'team_name',
        ]);
        /** tbd 
        $content[] = Fields::repeater([
            'label' => i('Define members', 'ftt'),
            'subfields'
        ]);
        */

        return '<div class="wrapper">'.$heading.App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'form', [
            'action' => Utils::getCurrentUrl(),
            'method' => 'post',
            'ajax' => true,
            'ajax_target' => '#slidein-overlay .ajax-content',
            'horizontal' => false,
            'content' => $content
        ]).'</div>';
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
            [
                'key' => 'ftt_organization_members',
                'label' => \i('Organization Members', 'ftt'),
                'values' => [],
                'value' => NULL,
                'multilang' => false,
                'type' => 'collection',
                'maxtags'=> 1,
                'collection' => 'forge-members',
                'data_source_save' => 'relation',
                'data_source_load' => 'relation',
                'relation' => [
                    'identifier' => 'ftt_organization_members'
                ],
                'order' => 20,
                'position' => 'left',
                'readonly' => false,
                'hint' => i('Assigned Members for this organization', 'ftt')
            ],
            [
                'key' => 'ftt_organization_join_requests',
                'label' => \i('Organization Join Requests', 'ftt'),
                'values' => [],
                'value' => NULL,
                'multilang' => false,
                'type' => 'collection',
                'maxtags'=> 1,
                'collection' => 'forge-members',
                'data_source_save' => 'relation',
                'data_source_load' => 'relation',
                'relation' => [
                    'identifier' => 'ftt_organization_join_requests'
                ],
                'order' => 30,
                'position' => 'left',
                'readonly' => false,
                'hint' => i('Join Requests for this organization', 'ftt')
            ]
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
