<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\View;
use Forge\Core\App\App;


class MyteamsView extends View {
    public $name = 'myteams';
    public $allowNavigation = true;

    public function content($parts = array()) {
        $tTable = new MyTeams();

        return App::instance()->render(MOD_ROOT . "forge-teams/templates/", "myteams", array(
            'title' => i('Your Teams', 'forge-teams'),
            'table' => $tTable->renderTable()
        ));
    }
}
