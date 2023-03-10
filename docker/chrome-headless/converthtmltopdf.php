<?php
$filepath = $_POST['filepath'];


if($filepath){
    shell_exec("/usr/bin/google-chrome --headless --no-sandbox --disable-setuid-sandbox --disable-software-rasterizer --disable-gpu --virtual-time-budget=9999999 --run-all-compositor-stages-before-draw --print-to-pdf-no-header --print-to-pdf=" . $filepath . ".pdf" . " " . $filepath . ".html");
    shell_exec("chmod a+r ".$filepath . ".pdf");
}