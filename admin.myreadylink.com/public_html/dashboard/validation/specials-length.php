<?php
    if( strlen( strip_tags( $_GET['specials']) ) <= 140 ){
        echo 'true';
    }
    else{
        echo 'false';
    }
?>