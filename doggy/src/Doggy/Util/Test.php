<?php
/**
 * 辅助测试工具
 *
 */
class Doggy_Util_Test {
    /**
     * 删除指定的model 表同时也drop 相关的sequence table
     * @param string $table
     */
    public static function dropModelTable($table){
        $dba=Doggy_Dba_Manager::get_model_dba();
        $dba->execute('DROP TABLE IF EXISTS '.$table);
        $dba->execute('DROP TABLE IF EXISTS '.'SEQ_'.strtoupper($table));
    }
    /**
     * 清空model表数据，复原相关的sequence值
     * @param string $table
     */
    public static function cleanModelTable($table){
        $dba=Doggy_Dba_Manager::get_model_dba();
        $dba->execute('TRUNCATE TABLE '.$table);
        $dba->execute('DROP TABLE IF EXISTS '.'SEQ_'.strtoupper($table));
    }
}
/**vim:sw=4 et ts=4 **/
?>