<?php

namespace Forge\Modules\ForgeTournaments;

use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\Classes\Fields;
use Forge\Core\Classes\Utils;
use Forge\Modules\TournamentsTeams\MembersCollection;
use Forge\Modules\TournamentsTeams\OrganizationsCollection;
use Forge\Modules\TournamentsTeams\TeamsCollection;

class Signup {
    private $item = null;

    public function __construct($item) {
        $this->item = $item;
    }

    public function render() {

        if(! Auth::any()) {
            $parts = Utils::getUriComponents();
            array_pop($parts);
            App::instance()->redirect('login', Utils::getUrl($parts), true);
        }

        if(! $this->item->getMeta('allow_signup')) {
            App::instance()->redirect('denied', false, true);
        }

        $title = sprintf(i('Signup for <i>%s</i>', 'forge-tournaments'), $this->item->getMeta('title'));

        if($this->item->getMeta('team_size') == 1) {
            $description = i('You can signup for this tournament as a member.', 'forge-tournaments');
            $teamLink = false;
        } else {
            $description = sprintf(i('Make sure you are the owner of an organization with a team of at least %s members. Otherwise you cant signup for this tournament.', 'forge-tournaments'), $this->item->getMeta('team_size'));
            $teamLinkText = i('To the organization management site', 'forge-tournaments');
            $teamLink = App::instance()->vm->getViewByName('teams')->buildURL();
        }
        return App::instance()->render(MOD_ROOT.'forge-tournaments/templates/views/',
            'signup', [
                'title' => $title,
                'description' => $description,
                'teamLink' => $teamLink,
                'teamLinkText' => $teamLinkText,
                'form' => $this->item->getMeta('team_size') == 1 
                    ? $this->getSignupFormUser() : $this->getSignupFormTeam()
            ]
        );
    }

    private function getSignupFormTeam() {
        $content = [];
        $content[] = Fields::select([
            'label' => i('Define members', 'ftt'),
            'key' => 'team_members',
            'chosen' => true,
            'values' => $this->getViableTeams()
        ]);
        $content[] = Fields::button(i('Signup', 'ftt'));

        return App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'form', [
            'action' => Utils::getCurrentUrl(),
            'method' => 'post',
            'ajax' => true,
            'ajax_target' => '#slidein-overlay .content',
            'horizontal' => false,
            'content' => $content
        ]);
    }

    private function getSignupFormUser() {
        return 'user signup';
    }

    private function getViableTeams() {
        // get Member
        $memberID = MembersCollection::getByUser(App::instance()->user);
        $organizations = MembersCollection::getOwnedOrganizations($memberID);
        $viableTeams = [];
        foreach($organizations as $orga) {
            foreach(OrganizationsCollection::getTeams($orga) as $team) {
                if(TeamsCollection::getMemberCount($team) >= $this->item->getMeta('team_size')) {
                    $viableTeams[$team] = OrganizationsCollection::getName($orga).' - '.TeamsCollection::getName($team);
                } else {
                    var_dump('not => '.$team);
                }
            }
        }
        // get Organizations, where this member is Owner
        // get Teams which have enough members
        
        if(count($viableTeams) == 0) {
            return [
                0 => i('You are not owner of a vialbe team for this tournament.', 'forge-tournaments')
            ];
        }

        return array_merge([
            0 => i('Choose your team', 'forge-tournaments'),
        ], $viableTeams);
    }

}

?>