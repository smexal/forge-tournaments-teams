<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\App\App;
use Forge\Core\Classes\TableBar;
use Forge\Core\Classes\User;
use Forge\Core\Classes\Utils;

class Members
{

    private $teamId = false;
    private $searchTerm = false;
    public $isAdmin = false;

    public function __construct($teamId)
    {
        $this->teamId = $teamId;
    }

    public function renderTable()
    {
        $bar = new TableBar(Utils::url(['api', 'forge-teams', 'members', $this->teamId]), 'membersTable');
        $bar->enableSearch();

        return $bar->render() . App::instance()->render(CORE_TEMPLATE_DIR . "assets/", "table", array(
                'id' => 'membersTable',
                'th' => $this->getThs(),
                'td' => $this->getMembers()
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
                        ['td' => $this->getMembers()]
                    )
                ]);
            default:
                break;
        }
    }

    public function getThs()
    {
        $ths = [];
        $ths[] = Utils::tableCell(i('Username', 'forge-teams'));
        if ($this->isAdmin) {
            $ths[] = Utils::tableCell(i('Role', 'forge-teams'));
        }
        if ($this->isAdmin) {
            $ths[] = Utils::tableCell(i('Joined on', 'forge-teams'));
        }
        if ($this->isAdmin) {
            $ths[] = Utils::tableCell(i('Actions', 'forge-teams'));
        }
        return $ths;
    }

    public function getMembers()
    {
        $db = App::instance()->db;
        $db->where('team_id', $this->teamId);
        $db->orderBy("join_date", "asc");

        $members = $db->get('forge_teams_members');
        $tds = [];
        foreach ($members as $member) {
            $row = new \stdClass();
            $user = new User($member['user_id']);
            $row->tds = $this->getMemberTd($user, $member);
            $row->rowAction = Utils::getUrl(['manage', 'users', 'edit', $user->get('id')]);
            array_push($tds, $row);
        }
        return $tds;
    }

    private function getMemberTd($user, $member)
    {
        if ($this->searchTerm) {
            $found = false;
            if (strstr(strtolower($user->get('username')), strtolower($this->searchTerm))) {
                $found = true;
            }
            if (strstr(strtolower($user->get('email')), strtolower($this->searchTerm))) {
                $found = true;
            }
            if (!$found) {
                return;
            }
        }
        $td = [];
        $td[] = Utils::tableCell($user->get('username'), false, false, false, Utils::getUrl(['manage', 'users', 'edit', $user->get('id')]));
        if ($this->isAdmin) {
            $td[] = Utils::tableCell($member['role']);
        }
        if ($this->isAdmin) {
            $td[] = Utils::tableCell($member['join_date']);
        }
        if ($this->isAdmin) {
            $td[] = Utils::tableCell($this->actions($member['id']));
        }
        return $td;
    }

    private function actions($id)
    {
        return App::instance()->render(CORE_TEMPLATE_DIR . "assets/", "table.actions", array(
            'actions' => array(
                array(
                    "url" => Utils::getUrl(Utils::getUriComponents(), true, ['deleteMember' => $id]),
                    "icon" => "delete",
                    "name" => i('Delete Seat Reservation', 'forge-teams'),
                    "ajax" => true,
                    "confirm" => false
                )
            )
        ));
    }

    public function delete($user_id)
    {
        $db = App::instance()->db;
        $db->where('team_id', $this->teamId);
        $db->where('user_id', $user_id);
    }

}