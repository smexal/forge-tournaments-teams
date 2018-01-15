<?php
namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\Module;
use Forge\Core\App\App;
use Forge\Core\App\Auth;
use Forge\Core\App\ModifyHandler;
use Forge\Core\Classes\Relations\CollectionRelation;
use Forge\Core\Classes\Relations\Enums\Directions;
use Forge\Core\Classes\Settings;
use Forge\Core\Classes\Utils;
use Forge\Loader;


class ForgeTournamentsteams extends Module {
    public $permission = 'manage.forge-tournaments';

    public function setup() {
        $this->version = '0.0.1';
        $this->id = "forge-tournaments-teams";
        $this->name = i('Forge Teams', 'ftt');
        $this->description = i('Teams and organizations for the Forge Tournament System', 'ftt');
        $this->image = $this->url() . 'assets/images/module-image.svg';
    }

    public function start() {
        App::instance()->tm->theme->addStyle(MOD_ROOT.'forge-tournaments-teams/assets/css/teams.less');

        \registerModifier('Forge/Core/RelationDirectory/collectRelations', function($existing) {
            return array_merge($existing, [
                'ftt_organization_teams' => new CollectionRelation(
                    'ftt_organization_teams', 
                    'forge-organizations', 
                    'forge-teams', 
                    Directions::DIRECTED
                )
            ]);
        });

        \registerModifier('Forge/Core/RelationDirectory/collectRelations', function($existing) {
            return array_merge($existing, [
                'ftt_teams_members' => new CollectionRelation(
                    'ftt_teams_members', 
                    'forge-teams', 
                    'forge-members', 
                    Directions::DIRECTED
                )
            ]);
        });

        \registerModifier('Forge/Core/RelationDirectory/collectRelations', function($existing) {
            return array_merge($existing, [
                'ftt_organization_members' => new CollectionRelation(
                    'ftt_organization_members', 
                    'forge-organizations', 
                    'forge-members', 
                    Directions::DIRECTED
                )
            ]);
        });

        \registerModifier('Forge/Core/RelationDirectory/collectRelations', function($existing) {
            return array_merge($existing, [
                'ftt_organization_join_requests' => new CollectionRelation(
                    'ftt_organization_join_requests', 
                    'forge-organizations', 
                    'forge-members', 
                    Directions::DIRECTED
                )
            ]);
        });

        App::instance()->tm->theme->addScript($this->url() . "assets/scripts/ftt.js", true);

        $this->install();
        ModifyHandler::instance()->add('modify_manage_navigation', [$this, 'modifyManageNavigation']);
    }

    private function install() {
        if (Settings::get($this->name . ".installed")) {
            return;
        }

        Auth::registerPermissions("manage.collection.teams");
        Auth::registerPermissions("manage.collection.organizations");

        /*
        App::instance()->db->rawQuery('CREATE TABLE IF NOT EXISTS `forge_teams_members` (' .
            '`id` int(11) NOT NULL,' .
            '`team_id` int(11) NOT NULL,' .
            '`user_id` int(11) NOT NULL,' .
            '`role` varchar(50) NOT NULL,' .
            '`join_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        App::instance()->db->rawQuery('ALTER TABLE `forge_teams_members` ADD PRIMARY KEY (`id`), ADD KEY `team_id` (`team_id`), ADD KEY `user_id` (`user_id`);');
        App::instance()->db->rawQuery('ALTER TABLE `forge_teams_members` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');

        App::instance()->db->rawQuery('CREATE TABLE IF NOT EXISTS `forge_organizations_teams` (' .
            '`id` int(11) NOT NULL,' .
            '`organization_id` int(11) NOT NULL,' .
            '`team_id` int(11) NOT NULL,' .
            '`join_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        App::instance()->db->rawQuery('ALTER TABLE `forge_organizations_teams` ADD PRIMARY KEY (`id`), ADD KEY `organization_id` (`organization_id`), ADD KEY `team_id` (`team_id`);');
        App::instance()->db->rawQuery('ALTER TABLE `forge_organizations_teams` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
        */

        Settings::set($this->name . ".installed", 1);
    }

    public function modifyManageNavigation($navigation) {
        if (Auth::allowed('manage.collection.teams')) {
            $navigation->removeFromCollections('forge-teams');
            //$navigation->removeFromCollections('forge-members');
        }
        if (Auth::allowed('manage.collection.organizations')) {
            $navigation->removeFromCollections('forge-organizations');
        }
        if (Auth::allowed($this->permission)) {
            $navigation->add('organizations', i('Organizations'), Utils::getUrl(array('manage', 'collections', 'forge-organizations')), 'leftPanel', false, 'allocate');
            $navigation->add('teams', i('Teams'), Utils::getUrl(array('manage', 'collections', 'forge-teams')), 'leftPanel', false, 'allocate');
        }
        return $navigation;
    }

    /**
     * Removed a user from a team
     * @param $team_id
     * @param $user_id
     */
    public static function leaveTeam($team_id) {
        $db = App::instance()->db;
        $db->where('team_id', $team_id);
        $db->where('user_id', Auth::getSessionUserID());
        $db->delete('forge_teams_members');
    }

    /**
     * Deletes a team
     * @param $team_id
     */
    public function deleteTeam($team_id) {
        $db = App::instance()->db;
        $db->where('organization_id', $this->organizationId);
        $db->where('team_id', $team_id);
    }
}

?>
