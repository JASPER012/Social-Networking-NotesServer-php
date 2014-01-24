<?php
    $dbhost = '140.113.67.212';
    $dbuser = 'root';
    $dbpass = 'JACKY183';
    $dbname = 'project';
    $conn = mysql_connect($dbhost, $dbuser, $dbpass) or die('Error with MySQL connection');
    mysql_query("SET NAMES 'utf8'");
    mysql_select_db($dbname);
?>