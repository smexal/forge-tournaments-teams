<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\App\App;
use Forge\Core\Classes\CollectionItem;
use Forge\Core\Classes\TableBar;
use Forge\Core\Classes\Utils;

class MyTeams
{

    private $organizationId = false;
    private $searchTerm = false;
    public $isAdmin = false;

    public function __construct()
    {
    }

    public function setOrganization($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    /**
     * Draws the table for frontend
     * @return string
     */
    public function renderTable()
    {
        $bar = new TableBar(Utils::url(['api', 'forge-teams', 'teams', $this->organizationId]), 'teamsTable');


        if (array_key_exists('leaveTeam', $_GET) && is_numeric($_GET['leaveTeam'])) {
            ForgeTeams::leaveTeam($_GET['leaveTeam']);
        }

        return $bar->render() . App::instance()->render(CORE_TEMPLATE_DIR . "assets/", "table", array(
                'id' => 'teamsTable',
                'th' => $this->getThs(),
                'td' => $this->getTeamsByUser()
            ));
    }

    public function handleQuery($action)
    {
        switch ($action) {
            case 'search':
                $this->searchTerm = $_GET['t'];
                return json_encode([
                    'newTable' => App::instance()->render(
                        CORE_TEMPLATE_DIR . 'assets/',
                        'table-rows',
                        ['td' => $this->getTeams()]
                    )
                ]);
            default:
                break;
        }
    }

    public function getThs()
    {
        $ths = [];
        $ths[] = Utils::tableCell(i('Teamname', 'forge-teams'));
        $ths[] = Utils::tableCell(i('Organization', 'forge-teams'));
        $ths[] = Utils::tableCell(i('Your role', 'forge-teams'));
        $ths[] = Utils::tableCell(i('Created on', 'forge-teams'));
        $ths[] = Utils::tableCell(i('Actions', 'forge-teams'));
        return $ths;
    }

    public function getTeamsByUser()
    {
        $db = App::instance()->db;
        $db->join('forge_teams_members tm', 't.id = tm.team_id', 'LEFT');
        $db->join('forge_organizations_teams ot', 't.id = ot.team_id', 'LEFT');
        $db->where('tm.user_id', 54);
        $db->where('t.type', 'forge-teams');

        $teams = $db->get('collections t');
        $tds = [];
        foreach ($teams as $item) {
            $row = new \stdClass();
            $organization = new CollectionItem($item['organization_id']);
            $team = new CollectionItem($item['team_id']);
            $member = new Member($item['id']);
            $row->tds = $this->getTeamByUserTd($organization, $team, $member);
            $row->rowAction = Utils::getUrl(['manage', 'collections', $organization->getType(), 'edit', $organization->id]);
            array_push($tds, $row);
        }
        return $tds;
    }

    private function getTeamByUserTd($organization, $team, $member)
    {
        $td = [];
        $td[] = Utils::tableCell($team->getName());
        $td[] = Utils::tableCell($organization->getName(), false, false, false, Utils::getUrl(['manage', 'collections', $organization->getType(), 'edit', $organization->id]));
        $td[] = Utils::tableCell($member->getRole());
        $td[] = Utils::tableCell($team->getCreationDate());
        $td[] = Utils::tableCell($this->actions($team, $member));
        return $td;
    }


    private function actions($team, $member)
    {
        $actions = [];

        $leave_team = array(
            "url" => Utils::getUrl(Utils::getUriComponents(), true, ['leaveTeam' => $team->id]),
            "icon" => "leave",
            "name" => i('Leave team', 'forge-teams'),
            "ajax" => true,
            "confirm" => true
        );
        array_push($actions, $leave_team);

        if ($member->getRole() === Roles::OWNER) {
            $delete_team = array(
                "url" => Utils::getUrl(Utils::getUriComponents(), true, ['deleteTeam' => $team->id]),
                "icon" => "delete",
                "name" => i('Delete team', 'forge-teams'),
                "ajax" => true,
                "confirm" => false
            );
            array_push($actions, $delete_team);
        }

        return App::instance()->render(CORE_TEMPLATE_DIR . "assets/", "table.actions", array(
                'actions' => $actions
            )
        );
    }


}