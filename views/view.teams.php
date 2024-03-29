<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\CollectionQuery;
use Forge\Core\Abstracts\View;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\Classes\CollectionItem;
use Forge\Core\Classes\Fields;
use Forge\Core\Classes\Media;
use Forge\Core\Classes\Relations\Enums\Prepares;
use Forge\Core\Classes\User;
use Forge\Core\Classes\Utils as CoreUtils;
use Forge\Core\Traits\ApiAdapter;

class TeamsView extends View {
    use ApiAdapter;

    private $apiMainListener = 'forge-tournaments-teams';

    public $name = 'teams';
    public $allowNavigation = true;

    public function searchTeam($query, $data) {
        $items = CollectionQuery::items([
            'name' => 'forge-organizations',
             'limit' => 10,
             'query' => '%'.$data['query'].'%'
        ]);

        $content = '<ul>';
        foreach($items as $item) {
             $u = new User($item->getAuthor());
             $content.='<li>';
             $content.='<p>'.$item->getName().'</p>';
             $content.='<small>'.$u->get('username').'</small>';
             $joinURL = $this->buildURL(['join_request', $item->getID()]);
             $content.='<a href="'.$joinURL.'" class="btn">'.i('Join Request', 'ftt').'</a>';
             $content.='</li>';
        }
        $content.='</ul>';

        return json_encode([
            'content' => $content
        ]);
     }

    public function content($uri=array()) {
        if(! Auth::any()) {
            App::instance()->redirect('denied');
        }

        $parts = CoreUtils::getUriComponents();
        if(count($parts) > 1 && $parts[1] == 'create') {
            if(array_key_exists('team_name', $_POST)) {
                return $this->createOrganization($_POST);
            }

            return $this->getCreateView();
        }

        if(count($parts) > 1 && $parts[1] == 'join_request' && is_numeric($parts[2]) ) {
            $collection = App::instance()->cm->getCollection('forge-organizations');
            $user = App::instance()->user;
            $collection->joinRequest($parts[2], $user);
            App::instance()->addMessage('Join Request successfully sent.', 'ftt');
            App::instance()->redirect($this->buildURL());
        }

        if(count($parts) > 1 && $parts[1] == 'search') {

            return $this->searchTeamView();
        }

        $navigation = $this->getNavigation();

        $teams = $this->getOrganizations();

        return $navigation.App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/', 'teams', [
            'title' => i('Your Organizations', 'ftt'),
            'create_team_label' => i('Create', 'ftt'),
            'create_team_link' => CoreUtils::url(['teams', 'create']),
            'search_team_label' => i('Search', 'ftt'),
            'search_team_link' => CoreUtils::url(['teams', 'search']),
            'close_url' => CoreUtils::getCurrentUrl(),
            'teams' => $teams
        ]);

    }

    private function getOrganizations() {
        $preparedItems = [];
        $items = [];
        $membersForUser = CollectionQuery::items([
            'name' => 'forge-members',
            'author' => App::instance()->user->get('id')
        ]);

        $memberCount = count($membersForUser);
        if ($memberCount == 0) {
            $memberForUser = MembersCollection::createIfNotExists(App::instance()->user);
        } else if ($memberCount >= 1) {
            $memberForUser = $membersForUser[0];
        } else {
            return $preparedItems;
        }

        $relation = App::instance()->rd->getRelation('ftt_organization_members');
        if(is_object($memberForUser)) {
            $members = $relation->getOfRight($memberForUser->id, Prepares::AS_IDS_LEFT);
            foreach($members as $memberTeam) {
                $i = new CollectionItem($memberTeam);
                array_push($items, $i);
            }
        }


        foreach($items as $item) {
            $img = new Media($item->getMeta('logo'));
            if(is_null($item->url())) {
                continue;
            }
            $preparedItems[] = [
                'title' => $item->getMeta('title'),
                'image' => $img->getSizedImage(280, 170),
                'url' => $item->url()
            ];
        }
        return $preparedItems;
    }

    private function createOrganization($data) {
        $metas = [];
        $hasError = false;
        if(strlen($data['team_short']) > 0) {
            $metas['shorttag'] = ['value' => strip_tags($data['team_short'])];
        }
        if(strlen($data['team_name']) > 0) {
            $metas['title'] = ['value' => strip_tags($data['team_name'])];
        } else {
            App::instance()->addMessage(i('Organization could not be created without a name', 'ftt'));
            $hasError = true;
        }
        if(strlen($data['team_description']) > 0) {
            $metas['description'] = ['value' => strip_tags($data['team_description'])];
        }
        if(strlen($data['team_website']) > 0) {
            $metas['website'] = ['value' => strip_tags($data['team_website'])];
        }
        $metas['status'] = ['value' => 'published'];

        if(strlen($_FILES['team_image']['name']) > 0) {
            $team_image = new Media();
            $team_image->create($_FILES['team_image']);
            $metas['logo'] = ['value' => $team_image->id];
        }

        if(! $hasError) {
            $organization_id = CollectionItem::create([
                'name' => CoreUtils::methodName($data['team_name']),
                'type' => 'forge-organizations',
                'author' => App::instance()->user->get('id')
            ], $metas);

            $collection = App::instance()->cm->getCollection('forge-organizations');
            $memberId = MembersCollection::createIfNotExists(App::instance()->user);
            $collection->joinRequest($organization_id, App::instance()->user);
            $collection->acceptJoinRequest($organization_id, $memberId);

            return '<h2>'.i('Your team has been created.', 'ftt').'</h2>';
        }
    }

    private function getNavigation() {
        return '';
    }

    private function searchTeamView() {
        $heading = '<h2>'.i('Search for a team', 'ftt').'</h2>';
        $heading.= '<p>'.i('Find a team and request to join it.', 'ftt').'</p>';
        $content = [];
        $content[] = Fields::text([
            'label' => i('Search Term', 'ftt'),
            'key' => 'team_search',
        ]);
        $orgas = json_decode($this->searchTeam('', ['query' => '']));
        return '<div id="team-search-form" class="wrapper">'.$heading.App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'form', [
            'action' => CoreUtils::getUrl(['api', $this->apiMainListener, 'search-team']),
            'method' => 'post',
            'ajax' => true,
            'ajax_target' => '#slidein-overlay .ajax-content',
            'horizontal' => false,
            'content' => $content
        ]).'<div id="team-results">'.$orgas->content.'</div></div>';
    }

    private function getCreateView() {
        $heading = '<h2>'.i('Create a new Team', 'ftt').'</h2>';
        $content = [];
        $content[] = Fields::text([
            'label' => i('Team Name', 'ftt'),
            'key' => 'team_name',
        ]);
        $content[] = Fields::text([
            'label' => i('Short Name', 'ftt'),
            'key' => 'team_short',
        ]);
        $content[] = Fields::text([
            'label' => i('Description', 'ftt'),
            'key' => 'team_description',
        ]);
        $content[] = Fields::fileStandard([
            'label' => i('Image / Logo', 'ftt'),
            'key' => 'team_image',
        ]);
        $content[] = Fields::text([
            'label' => i('Website', 'ftt'),
            'key' => 'team_website',
        ]);
        $content[] = Fields::button(i('Create', 'ftt'));
        return '<div class="wrapper">'.$heading.App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'form', [
            'action' => CoreUtils::getCurrentUrl(),
            'method' => 'post',
            'ajax' => true,
            'ajax_target' => '#slidein-overlay .ajax-content',
            'horizontal' => false,
            'content' => $content
        ]).'</div>';
    }

}
