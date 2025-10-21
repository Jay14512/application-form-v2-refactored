<?php

function dump(...$data){

    $backtrace = debug_backtrace();
    if( $backtrace[0]["file"] !=  __FILE__ ){
        echo '<div style="background:#333; color:#fff; padding:5px;">Datei: '.$backtrace[0]["file"].'<br>Zeile: '.$backtrace[0]["line"].'<br>Funktion: '.$backtrace[0]["function"].'</div>';
    }

    foreach( $data as $item){
        echo '<pre style="background:#000; color:#fff; padding:5px;">';
            var_dump($item);
        echo '</pre>';
    }
}

function dd(...$data){

    $backtrace = debug_backtrace();
    echo '<div style="background:#333; color:#fff; padding:5px;">Datei: '.$backtrace[0]["file"].'<br>Zeile: '.$backtrace[0]["line"].'<br>Funktion: '.$backtrace[0]["function"].'</div>';

    dump(...$data);
    exit;
}
