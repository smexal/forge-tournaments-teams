<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\App\App;
use Forge\Core\Classes\CollectionItem;
use Forge\Core\Classes\TableBar;
use Forge\Core\Classes\Utils;

class Teams
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
     * Draws the table for backend
     * @return string
     */
    public function renderTableBackend()
    {
        $bar = new TableBar(Utils::url(['api', 'forge-teams', 'teams', $this->organizationId]), 'teamsTable');
        $bar->enableSearch();

        return $bar->render() . App::instance()->render(CORE_TEMPLATE_DIR . "assets/", "table", array(
                'id' => 'teamsTable',
                'th' => $this->getThs(),
                'td' => $this->getTeams()
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
        if ($this->isAdmin) {
            $ths[] = Utils::tableCell(i('Created on', 'forge-teams'));
        }
        if ($this->isAdmin) {
            $ths[] = Utils::tableCell(i('Actions', 'forge-teams'));
        }
        return $ths;
    }

    public function getTeams()
    {
        $db = App::instance()->db;
        $db->where('organization_id', $this->organizationId);
        $db->orderBy("join_date", "asc");

        $teams = $db->get('forge_organizations_teams');
        $tds = [];
        foreach ($teams as $item) {
            $team = new CollectionItem($item['team_id']);
            $row = new \stdClass();
            $row->tds = $this->getTeamTd($team);
            $row->rowAction = Utils::getUrl(['manage', 'collections', $team->getType(), 'edit', $team->id]);
            array_push($tds, $row);
        }
        return $tds;
    }

    private function getTeamTd($team)
    {
        if ($this->searchTerm) {
            $found = false;
            if (strstr(strtolower($team->getName()), strtolower($this->searchTerm))) {
                $found = true;
            }
            if (!$found) {
                return;
            }
        }
        $td = [];
        $td[] = Utils::tableCell($team->getName(), false, false, false, Utils::getUrl(['manage', 'collections', $team->getType(), 'edit', $team->id]));
        if ($this->isAdmin) {
            $td[] = Utils::tableCell($team->getCreationDate());
        }
        if ($this->isAdmin) {
            $td[] = Utils::tableCell($this->actions($team->id));
        }
        return $td;
    }

    private function actions($id)
    {
        return App::instance()->render(CORE_TEMPLATE_DIR . "assets/", "table.actions", array(

            'actions' => array(
                array(
                    "url" => Utils::getUrl(array("manage", "collections", 'forge-teams', 'edit', $id)),
                    "icon" => "mode_edit",
                    "name" => i('Edit organization', 'forge-teams'),
                    "ajax" => false,
                    "confirm" => false
                ),
                array(
                    "url" => Utils::getUrl(Utils::getUriComponents(), true, ['deleteTeam' => $id]),
                    "icon" => "delete",
                    "name" => i('Remove team from organization', 'forge-teams'),
                    "ajax" => true,
                    "confirm" => false
                )
            )
        ));
    }

    public function delete($team_id)
    {
        $db = App::instance()->db;
        $db->where('organization_id', $this->organizationId);
        $db->where('team_id', $team_id);
        //$db->delete('forge_events_seat_reservations');
    }

}