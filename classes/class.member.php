<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\App\App;

class Member
{
    public $id = null;

    private $db = null;
    private $base_data = null;

    public function __construct($id)
    {
        $this->id = $id;
        $this->db = App::instance()->db;

        $this->db->where('id', $this->id);
        $this->base_data = $this->db->getOne('forge_teams_members');
    }

    public function getRole()
    {
        return $this->base_data['role'];
    }

    public function getJoinDate()
    {
        return $this->base_data['join_date'];
    }

}

