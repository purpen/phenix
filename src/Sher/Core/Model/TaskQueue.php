<?php
/**
 * 任务队列
 * @author purpen
 */
class Sher_Core_Model_TaskQueue extends Sher_Core_Model_Base {
    protected $collection = "task_queue";
    
    # 任务类型
    const TYPE_EMAIL = 1;
    
    protected $schema = array(
        'task_data' => array(),
        'type'  => self::TYPE_EMAIL,
        'state' => 0,
    );
    
    protected $required_fields = array();
    
    /**
     * 获取某一个任务，返回后并删除
     */
    public function pop($type=self::TYPE_EMAIL){
        $query = array(
            'type' => (int)$type,
        );
        $row = $this->first($query);
        if(!empty($row)){
            $this->remove($row['_id']);
        }
        return $row;
    }
    
    /**
     * 将一个任务压入指定队列中
     *
     * @param array $task_data task arguments
     * @return MongoId 返回生成的task id
     */
    public function queue($task_data=array(),$type=self::TYPE_EMAIL){
        $this->create(array(
            'task_data' => $task_data,
            'type' => (int)$type,
        ));
        
        return $this->id;
    }
    
    /**
     * 邮件列表
     */
    public function queue_email($email, $name, $edm_id){
        return $this->queue(array(
            'email'  => $email,
            'name'   => $name,
            'edm_id' => $edm_id,
        ), self::TYPE_EMAIL);
    }
    
}