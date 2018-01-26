<?php if(!class_exists('raintpl')){exit;}?><div id="teams">
    <div class="overview wrapper">
        <div class="row">
            <div class="col-lg-2"></div>
            <div class="col-lg-8">
                <h1><?php echo $title;?></h1>
            </div>
        </div>
        <div class="row team-listing">
            <div class="col-lg-2"></div>
            <div class="col-lg-8">
            <?php $counter1=-1; if( isset($teams) && is_array($teams) && sizeof($teams) ) foreach( $teams as $key1 => $value1 ){ $counter1++; ?>

                <a class="team" href="<?php echo $value1["url"];?>">
                    <div class="image">
                        <img src="<?php echo $value1["image"];?>" />
                    </div>
                    <h3><?php echo $value1["title"];?></h3>
                </a>
            <?php } ?>

            </div>
        </div>
        <div class="row">
            <div class="col-lg-2"></div>
            <div class="col-lg-4 add-team">
                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <a href="<?php echo $create_team_link;?>" class="to-overlay" refresh-on-close="<?php echo $close_url;?>" refresh-target="#teams">
                            <i class="icon ion-ios-plus-empty"></i>
                            <span class="cta-light"><?php echo $create_team_label;?></span>
                        </a>
                    </div>
                    <div class="col-lg-6 col-xs-6">
                        <a href="<?php echo $search_team_link;?>" class="to-overlay">
                            <i class="icon ion-ios-search"></i>
                            <span class="cta-light"><?php echo $search_team_label;?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>