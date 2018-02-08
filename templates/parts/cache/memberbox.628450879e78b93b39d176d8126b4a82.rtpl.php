<?php if(!class_exists('raintpl')){exit;}?><div class="compact-infobox memberbox reveal">
    <div class="avatar">
        <?php if( $avatar ){ ?>

            <div class="image" style="background-image:url(<?php echo $avatar;?>);"></div>
        <?php }else{ ?>

            <div class="avatar-placeholder"><i class="icon ion-ios-person-outline"></i></div>
        <?php } ?>

    </div>
    <div class="data">
        <h4><?php echo $username;?></h4>
        <?php if( isset($additional) ){ ?><p class="discreet"><?php echo $additional;?></p><?php } ?>

    </div>
    <?php if( isset($action) ){ ?>

    <div class="action">
        <?php echo $action;?>

    </div>
    <?php } ?>

</div>