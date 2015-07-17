<?php
class Doggy_Db_Util {
    /**
     * Excuete sql input from a specific file.
     *
     * @param string $sql_file 
     * @param string $db 
     * @return void
     */
    public static function execute_file($sql_file,$db) {
        $lines = file_get_contents($sql_file);
        if (empty($lines)) {
            return;
        }
        $sqls = explode(';',$lines);
        foreach ($sqls as $sql) {
            $sql = trim($sql);
            if (empty($sql) || substr($sql,0,2) == '--') {
                continue;
            }
            $db->execute($sql);
        }
    }
}
?>