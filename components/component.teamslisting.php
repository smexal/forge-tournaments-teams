<?php

namespace Forge\Modules\TournamentsTeams;

use Forge\Core\Abstracts\Components;
use Forge\Core\App\App;
use Forge\Core\Classes\Media;
use Forge\Core\Components\ListingComponent;

class TeamslistingComponent extends ListingComponent {
    protected $collection = 'forge-teams';

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
        $image = $item->getMeta('image');
        $image = new Media($image);
        return App::instance()->render(MOD_ROOT . 'forge-teams/templates/', 'listing-item', array(
            'title' => $item->getMeta('title'),
            'description' => $item->getMeta('description'),
            'email' => $item->getMeta('email'),
            'image' => [
                'src' => $image->url . $image->name,
                'alt' => $item->getMeta('title')
            ]
        ));
    }

}

?>
