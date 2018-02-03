<?php
namespace ninvfeng\mysql;
//基础数据库操作
class mysql
{

    protected $_field='*';
    protected $_where='';
    protected $_order='';
    protected $_limit='';
    protected $_debug=false;

    function __construct($table,$config)
    {
        //链接数据库
        $this->_pdo=new \PDO('mysql:host='.$config['host'].';dbname='.$config['name'],$config['user'],$config['pass'],array(\PDO::ATTR_PERSISTENT => true));

        //设置客户端字符集
        $this->_pdo->exec("set names 'utf8'");

        //数据表
        $this->_table=$table;
    }

    //返回pdo对象
    public function pdo(){
        return $this->_pdo;
    }

    //字段
    public function field($field){
        $this->_field=$field;
        return $this;
    }

    //排序
    public function order($order){
        $this->_order='order by '.$order;
        return $this;
    }

    //限制
    public function limit($limit){
        $this->_limit='limit '.$limit;
        return $this;
    }

    //条件
    public function where($where){
        if(is_array($where)){
            $res='';
            foreach($where as $k => $v){
                $res.='`'.$k.'`="'.$v.'" and';
            }
            $where=trim($res,'and');
        }
        $this->_where='where '.$where;
        return $this;
    }

    //分页
    public function page($page=1,$num=10){
        $page=intval($page);
        $num=intval($num);
        $start=($page-1)*$num;
        $this->_limit="limit $start,$num";
        return $this;
    }

    //join
    public function join($join){
        $this->_limit=$join;
        return $this;
    }

    //调试
    public function debug(){
        $this->_debug=true;
        return $this;
    }

    //结果集
    public function select(){
        return $this->_query();
    }

    //获取单条数据
    public function find(){
        return $this->_query()[0];
    }

    //更新
    public function save($data){
        if($this->_where){
            $update='';
            foreach($data as $k => $v){
                $update.='`'.$k.'`="'.$v.'",';
            }
            $update=trim($update,',');
            $sql="update {$this->_table} set $update {$this->_where};";
            return $this->_exec($sql);
        }else{
            error('保存数据需指定条件');
        }


    }

    //添加
    public function add($data){
        $update='';
        foreach($data as $k => $v){
            $update.=$k.'="'.$v.'",';
        }
        $update=trim($update,',');
        $sql="insert into {$this->_table} set $update;";
        $this->_exec($sql);
        return $this->_pdo->lastInsertId();
    }

    //删除
    public function delete(){
        if($this->_where){
            $sql="delete from {$this->_table} {$this->_where};";
            return $this->_exec($sql);
        }else{
            error('删除数据需指定条件');
        }
    }

    //执行原生query
    public function query($sql,$param=[]){
        //禁用prepared statements的仿真效果 确保SQL语句和相应的值在传递到mysql服务器之前是不会被PHP解析
        $this->_pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        $pre=$this->_pdo->prepare($sql);
        $pre->execute($param);
        if($this->_error()){
            return $pre->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    //执行原生exec
    public function exec($sql,$param=[]){
        //禁用prepared statements的仿真效果 确保SQL语句和相应的值在传递到mysql服务器之前是不会被PHP解析
        $this->_pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        $pre=$this->_pdo->prepare($sql);
        $res=$pre->execute($param);
        if($this->_error()){
            return $res;
        }
    }

    //事务
    public function transaction($callback,$arr=[])
    {
        $this->_pdo->beginTransaction();
        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$arr]);
            }
            $this->_pdo->commit();
            return $result;
        } catch (\Exception $e) {
            $this->_pdo->rollback();
            throw $e;
        }
    }

    //执行
    protected function _exec($sql){
        if($this->_debug){
            echo $sql;
            die();
        }else{
            $res=$this->_pdo->exec($sql);
            if($this->_error()){
                return $res;
            }
        }
    }

    //查询
    protected function _query(){
        $sql="select {$this->_field} from {$this->_table} {$this->_join} {$this->_where} {$this->_order} {$this->_limit}";
        if($this->_debug){
            echo $sql;
            die();
        }else{
            $res=$this->_pdo->query($sql);
            if($this->_error()){
                return $res->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
    }

    //错误处理
    protected function _error(){
        if($this->_pdo->errorCode()==00000){
            return true;
        }else{
            echo '<pre>';
            $error_msg=$this->_pdo->errorInfo()[2];
            $e=new \Exception($error_msg);
            echo $error_msg.'<br>'.$e->getTrace()[2]['file'].' In line '.$e->getTrace()[2]['line'];
            die();
        }
    }

}
