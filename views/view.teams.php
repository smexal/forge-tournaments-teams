<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\CollectionQuery;
use Forge\Core\App\Auth;
use Forge\Core\Classes\CollectionItem;
use Forge\Core\Classes\Fields;
use Forge\Core\Classes\Media;
use \Forge\Core\Abstracts\View;
use \Forge\Core\App\App;
use \Forge\Core\Classes\Utils as CoreUtils;

class TeamsView extends View {
    public $name = 'teams';
    public $allowNavigation = true;

    public function content() {
        if(! Auth::any()) {
            App::instance()->redirect('denied');
        }

        $parts = CoreUtils::getUriComponents();
        if(count($parts) > 1 && $parts[1] == 'create') {
            if(array_key_exists('team_name', $_POST)) {
                return $this->createTeam($_POST);
            }

            return $this->getCreateView();
        }

        $navigation = $this->getNavigation();

        $teams = $this->getTeams();

        return $navigation.App::instance()->render(MOD_ROOT.'forge-tournaments-teams/templates/', 'teams', [
            'title' => i('Overview', 'ftt'),
            'create_team_label' => i('Create a team', 'ftt'),
            'create_team_link' => CoreUtils::url(['teams', 'create']),
            'search_team_label' => i('Search a team', 'ftt'),
            'close_url' => CoreUtils::getCurrentUrl(),
            'teams' => $teams
        ]);

    }

    private function getTeams() {
        $items = CollectionQuery::items([
            'name' => 'forge-organizations',
            'author' => App::instance()->user->get('id')
        ]);
        $preparedItems = [];
        foreach($items as $item) {
            $img = new Media($item->getMeta('logo'));
            $preparedItems[] = [
                'title' => $item->getMeta('title'),
                'image' => $img->getSizedImage(280, 170),
                'url' => $item->url()
            ];
        }
        return $preparedItems;
    }

    private function createTeam($data) {
        $metas = [];
        $hasError = false;
        if(strlen($data['team_short']) > 0) {
            $metas['shorttag'] = ['value' => $data['team_short']];
        }
        if(strlen($data['team_name']) > 0) {
            $metas['title'] = ['value' => $data['team_name']];
        } else {
            App::instance()->addMessage(i('Team could not be created without a name', 'ftt'));
            $hasError = true;
        }
        if(strlen($data['team_description']) > 0) {
            $metas['description'] = ['value' => $data['team_description']];   
        }
        if(strlen($data['team_website']) > 0) {
            $metas['website'] = ['value' => $data['team_website']];   
        }
        $metas['status'] = ['value' => 'published'];

        if(strlen($_FILES['team_image']['name']) > 0) {
            $team_image = new Media();
            $team_image->create($_FILES['team_image']);
            $metas['logo'] = ['value' => $team_image->id];
        }

        if(! $hasError) {
            CollectionItem::create([
                'name' => CoreUtils::methodName($data['team_name']),
                'type' => 'forge-organizations',
                'author' => App::instance()->user->get('id')
            ], $metas);
            return '<h2>'.i('Your team has been created.', 'ftt').'</h2>';
        }
    }

    private function getNavigation() {
        return '';
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
        $content[] = Fields::button(i('Create team', 'ftt'));
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