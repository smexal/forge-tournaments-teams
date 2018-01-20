<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\DataCollection;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\App\ModifyHandler;
use Forge\Core\Classes\CollectionItem;
use Forge\Core\Classes\Fields;
use Forge\Core\Classes\Media;
use Forge\Core\Classes\Relations\Enums\Prepares;
use Forge\Core\Classes\User;
use Forge\Core\Classes\Utils;
use Forge\Modules\TournamentsTeams\TeamsCollection;

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
        $url_parts = Utils::getUriComponents();
        if(count($url_parts) > 3 && $url_parts[3] == 'create') {
            if($this->isOwner($item)) {
                if(array_key_exists('team_name', $_POST)) {
                    return $this->createTeam($item, $_POST);
                }
                return $this->createTeamContent($item);

            } else {
                App::instance()->redirect('denied');
            }
        }

        if(count($url_parts) > 3 && $url_parts[3] == 'accept_join_request' && is_numeric($url_parts[4])) {
            $this->acceptJoinRequest($item, $url_parts[4]);
            App::instance()->addMessage(i('Request accepted', 'ftt'));
            App::instance()->redirect($item->url());
        }




        // nothing special, show default

        $actions = false;
        if($this->isOwner($item)) {
            $actions = true;
        }

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
            'tabs' => $this->getTeamTabs($item),
            'actions' => $actions,
            'create_team_label' => i('Create team'),
            'create_team_url' => Utils::getCurrentUrl().'/create',
            'create_close_url' => Utils::getCurrentUrl()
        ]);
    }

    private function createTeam($item, $data) {
        $metas = [];
        $hasError = false;

        if(strlen($data['team_name']) > 0) {
            $metas['title'] = ['value' => $data['team_name']];
        } else {
            App::instance()->addMessage(i('Organization could not be created without a name', 'ftt'));
            $hasError = true;
        }
        $metas['status'] = ['value' => 'published'];

        if(! $hasError) {
            $team_id = CollectionItem::create([
                'name' => Utils::methodName($data['team_name']),
                'type' => 'forge-teams',
                'author' => App::instance()->user->get('id')
            ], $metas);

            $relation = App::instance()->rd->getRelation('ftt_teams_members');
            $relation->setRightItems($team_id, $data['team_members']);

            $relation = App::instance()->rd->getRelation('ftt_organization_teams');
            $relation->add($item->getID(), $team_id);

            return '<h3>'.i('Your team has been created', 'ftt').'</h3>';
        }
    }

    private function getTeamTabs($item) {
        $tabs =  [
            [
                'active' => true,
                'key' => 'all_members',
                'name' => i('Members', 'ftt')
            ],
        ];

        $requests = $this->getJoinRequests($item);

        $tabs_content = [
            [
                'id' => 'all_members',
                'active' => true,
                'content' => $this->tabMembers($item)
            ],
            [
                'id' => 'requests',
                'content' => $this->tabJoinRequests($requests)
            ]
        ];

        foreach(self::getTeams($item) as $team) {
            $cTeam = new CollectionItem($team);
            $tabs = array_merge($tabs, [
                    [
                        'key' => 'team-'.$team,
                        'name' => $cTeam->getName()
                    ]
            ]);

            $tabs_content = array_merge($tabs_content, [
                [
                    'id' => 'team-'.$team,
                    'content' => $this->getTeamMembers($cTeam)
                ]
            ]);
        }

        if($this->isOwner($item)) {
            $pending = '';
            if(count($requests) > 0) {
                $pending = ' <small class="not-bubble">'.count($requests).'</small>';
            }
            $tabs = array_merge($tabs, [
                [
                    'key' => 'requests',
                    'name' => i('Pending Requests').$pending,
                    'disabled' => count($requests) == 0 ? true : false
                ]
            ]);
        }


        return App::instance()->render(CORE_TEMPLATE_DIR."assets/", "tabs", [
            'tabs' => $tabs,
            'tabs_content' => $tabs_content
        ]);
    }

    private function getTeamMembers($team) {
        $member_list = TeamsCollection::getMembers($team);
        $members = '';
        foreach($member_list as $member) {
            $member = new CollectionItem($member);
            $user = new User($member->getMeta('user'));
            $args = [
                'username' => $user->get('username'),
                'avatar' => $user->getAvatar() !== null ? $user->getAvatar() : false
            ];
            $members.= App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/parts', 'memberbox', $args);
        }

        return App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/parts', 'members_tab', [
            'members' => $members
        ]);
    }

    private function tabMembers($item) {
        $member_list = $this->getMembers($item);
        $members = '';
        foreach($member_list as $member) {
            $member = new CollectionItem($member);
            $user = new User($member->getMeta('user'));
            $args = [
                'username' => $user->get('username'),
                'avatar' => $user->getAvatar() !== null ? $user->getAvatar() : false
            ];
            $members.= App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/parts', 'memberbox', $args);
        }
        return App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/parts', 'members_tab', [
            'members' => $members
        ]);
    }

    private function tabJoinRequests($requests) {
        $reqs = '';
        foreach($requests as $request) {
            $member = new CollectionItem($request);
            $user = new User($member->getMeta('user'));
            $url = Utils::getUrl(array_merge(
                Utils::getUriComponents(),
                [
                    'accept_join_request',
                    $request
                ]
            ));
            $args = [
                'username' => $user->get('username'),
                'avatar' => $user->getAvatar() !== null ? $user->getAvatar() : false,
                'additional' => '<a href="'.$url.'">'.i('Accept Join request', 'ftt').'</a>'
            ];
            $reqs.= App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/parts', 'memberbox', $args);
        }

        return App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/parts', 'join_request_tab', [
            'requests' => $reqs
        ]);
    }

    public function joinRequest($item, $user) {
        if(! is_object($item)) {
            $item = $this->getItem($item);
        }
        $memberId = MembersCollection::createIfNotExists($user);

        if(! in_array($memberId, $this->getJoinRequests($item))
        && ! in_array($memberId, $this->getMembers($item))) {
            $rel = App::instance()->rd->getRelation('ftt_organization_join_requests');
            return $rel->add($item->id, $memberId);
        }
    }

    public static function getTeams($item) {
        $relation = App::instance()->rd->getRelation('ftt_organization_teams');
        return $relation->getOfLeft($item->id, Prepares::AS_IDS_RIGHT);
    }

    private function getJoinRequests($item) {
        $relation = App::instance()->rd->getRelation('ftt_organization_join_requests');
        return $relation->getOfLeft($item->id, Prepares::AS_IDS_RIGHT);
    }

    public function acceptJoinRequest($item, $request) {
        if(! is_object($item) ) {
            $item = $this->getItem($item);
        }
        $joinRelation = App::instance()->rd->getRelation('ftt_organization_join_requests');
        $joinRelation->removeByRelationItems($item->id, $request);

        // is already member.. break;
        if(in_array($request, $this->getMembers($item))) {
            return;
        }
        $memberRelation = App::instance()->rd->getRelation('ftt_organization_members');
        $memberRelation->add($item->id, $request);
    }

    private function getMembers($item) {
        $relation = App::instance()->rd->getRelation('ftt_organization_members');
        return array_unique($relation->getOfLeft($item->id, Prepares::AS_IDS_RIGHT));
    }

    public static function getName($item) {
        if(! is_object($item)) {
            $item = new CollectionItem($item);
        }
        return $item->getName();
    }

    private function createTeamContent($item) {
        $heading = '<h3>'.i('Create a new Team', 'ftt').'</h3>';
        $content = [];
        $content[] = Fields::text([
            'label' => i('Team Name', 'ftt'),
            'key' => 'team_name',
        ]);
        $content[] = Fields::multiselect([
            'label' => i('Define members', 'ftt'),
            'key' => 'team_members',
            'values' => $this->getMultiSelectMembers($item)
        ]);
        $content[] = Fields::button(i('Create team', 'ftt'));

        return '<div class="wrapper">'.$heading.App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'form', [
            'action' => Utils::getCurrentUrl(),
            'method' => 'post',
            'ajax' => true,
            'ajax_target' => '#slidein-overlay .content',
            'horizontal' => false,
            'content' => $content
        ]).'</div>';
    }

    private function getMultiSelectMembers($item) {
        $members = $this->getMembers($item);
        $selectValues = [];
        foreach($members as $member) {
            $member = new CollectionItem($member);
            $selectValues[] = [
                'value' => $member->getID(),
                'text' => $member->getName(),
                'active' => false
            ];
        }
        return $selectValues;
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
}

?>
