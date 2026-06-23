<?php
$file = 'sqlite conf/common/01_db_ddl.sql';
$content = file_get_contents($file);
// Remove database creation and use
$content = preg_replace('/CREATE DATABASE.*?;/i', '', $content);
$content = preg_replace('/use stackDB;/i', '', $content);
// Change AUTO_INCREMENT to SQLite syntax
$content = preg_replace('/(\w+) INT NOT NULL AUTO_INCREMENT/i', '$1 INTEGER PRIMARY KEY AUTOINCREMENT', $content);
// Remove explicit PRIMARY KEY declarations since they are now inline
$content = preg_replace('/,\s*PRIMARY KEY\([^\)]+\)/i', '', $content);
// Change TINYINT(1) to INTEGER
$content = str_ireplace('TINYINT(1)', 'INTEGER', $content);
file_put_contents($file, trim($content));
$file2 = 'sqlite conf/common/02_db_dml.sql';
$content2 = file_get_contents($file2);
$content2 = preg_replace('/use stackDB;/i', '', $content2);
file_put_contents($file2, trim($content2));
echo "Done\n";
