<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\Components;
use Forge\Core\App\App;
use Forge\Core\Classes\Media;
use Forge\Core\Components\ListingComponent;

class TeamslistingComponent extends ListingComponent {
    protected $collection = 'forge-organizations';
    protected $cssClasses = ['wrapper', 'reveal'];

    public function prefs() {
        return array(
            'name' => i('Teams Listing'),
            'description' => i('List Teams from the Module.'),
            'id' => 'teams-listing',
            'image' => '',
            'level' => 'inner',
            'container' => false
        );
    }

    public function renderItem($item) {
        $img = new Media($item->getMeta('logo'));
        $args = [
            'username' => $item->getName(),
            'avatar' => $img ? $img->getUrl() : false,
            'link' => $item->url()
        ];
        return App::instance()->render(MOD_ROOT . 'forge-tournaments-teams/templates/', 'listing-organisation',$args);
    }

}

?>
