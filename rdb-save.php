<?php
    list($rdby,$rdbm,$rdbd) = explode("-",date("Y-m-d"));
    $rdbfile = "../../dis2_db/rdb/RDB-$dbname-$rdby-$rdbm-$rdbd.sql";
    $fh = fopen($rdbfile,"a");
    fwrite($fh,$rdb);
    fclose($fh);
?>
