<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Classes\Enum;

/**
 * Enum class for team roles
 */
class Roles extends Enum {

    // created the team
    const OWNER = "OWNER";
    // can manage the team
    const MANAGER = "MANAGER";
    // just a player
    const PLAYER = "PLAYER";
}