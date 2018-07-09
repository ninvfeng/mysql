<?php
namespace ninvfeng;
//基础数据库操作
class mysql
{

    protected $_field='*';
    protected $_where='';
    protected $_order='';
    protected $_limit='';
    protected $_join='';
    protected $_debug=false;
    protected $_param=[];
    protected $_sql=[];

    function __construct($config){
        //链接数据库
        $this->_pdo=new \PDO('mysql:host='.$config['host'].';dbname='.$config['name'],$config['user'],$config['pass'],array(\PDO::ATTR_PERSISTENT => true));

        //设置客户端字符集
        $this->_pdo->exec("set names 'utf8'");

        //禁用prepared statements的仿真效果 确保SQL语句和相应的值在传递到mysql服务器之前是不会被PHP解析
        $this->_pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        //数据表
        $this->_table=$table;
    }

    //返回pdo对象
    public function pdo(){
        return $this->_pdo;
    }

    //操作表
    public function table($table){
        $this->_table=$table;
        return $this;
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
    public function where($where,$param=[]){
        if($param){
            $this->_param=array_merge($this->_param,$param);
        }
        if(is_array($where)){
            $res='';
            foreach($where as $k => $v){
                $column_key='';
                foreach (explode('.',$k) as $kk => $vv) {
                    $column_key.='`'.$vv.'`.';
                    $column_plac='where_'.$vv;
                }
                $this->_param[$column_plac]=$v;
                $column_key=trim($column_key,'.');
                
                $res.=$column_key.'=:'.$column_plac.' and';
            }
            $where=trim($res,'and');
        }
        $this->_where.=' '.$where.' and';
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

        //语句中不包含join时自动添加left join
        if(stripos($join,'join')===false){
            $join='left join '.$join;
        }
        $this->_join=$join;
        return $this;
    }

    //调试
    public function debug(){
        $this->_debug=true;
        return $this;
    }

    //结果集
    public function select(){
        $res=$this->_query();
        if($res&&count($res[0])==1){
            $column=explode('.',$this->_field);
            $column=array_pop($column);
            $result=array_column($res,$column);
            return $result;
        }else{
            return $res;
        }
    }

    //获取单条数据
    public function find(){
        $res=$this->_query()[0];
        if($res&&count($res)==1){
            $column=explode('.',$this->_field);
            $column=array_pop($column);
            return $res[$column];
        }else{
            return $res;
        }
    }

    //更新
    public function update($data){
        if($this->_where){
            $update='';
            foreach($data as $k => $v){
                $column_key='';
                foreach (explode('.',$k) as $kk => $vv) {
                    $column_key.='`'.$vv.'`.';
                    $column_plac=$vv;
                }
                $this->_param[$column_plac]=$v;
                $column_key=trim($column_key,'.');
                $update.=$column_key."=:".$column_plac.",";
            }
            $update=trim($update,',');
            $this->preWhere();
            $this->_sql="update {$this->_table} set $update {$this->_where};";
            return $this->exec($this->_sql,$this->_param);
        }else{
            echo '保存数据需指定条件';
            die();
        }
    }

    //添加
    public function insert($data){
        $update='';
        foreach($data as $k => $v){
            $column_key='';
            foreach (explode('.',$k) as $kk => $vv) {
                $column_key.='`'.$vv.'`.';
                $column_plac=$vv;
            }
            $this->_param[$column_plac]=$v;
            $column_key=trim($column_key,'.');
            $update.=$column_key."=:".$column_plac.",";
        }
        $update=trim($update,',');
        $this->_sql="insert into {$this->_table} set $update;";
        $this->exec($this->_sql,$this->_param);
        return $this->_pdo->lastInsertId();
    }

    //删除
    public function delete(){
        if($this->_where){
            $this->preWhere();
            $this->_sql="delete from {$this->_table} {$this->_where};";
            return $this->exec($this->_sql,$this->_param);
        }else{
            echo '删除数据需指定条件';
            die();
        }
    }

    //执行原生query
    public function query($sql,$param=[]){
        if($this->_debug){
            echo "<pre>";
            echo $this->debugSql();
            die();
        }else{
            $pre=$this->_pdo->prepare($sql);
            if(!$pre){
                $this->_error();
            }
            $pre->execute($param);
            if($this->_error()){
                return $pre->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
    }

    //执行原生exec
    public function exec($sql,$param=[]){
        if($this->_debug){
            echo "<pre>";
            echo $this->debugSql();
            die();
        }else{
            $pre=$this->_pdo->prepare($sql);
            $pre->execute();
            if($this->_error()){
                return $res;
            }
        }
    }

    //事务
    public function trans($callback,$arr=[])
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

        //清空参数
    public function clearParam(){
        $this->_field='*';
        $this->_where='';
        $this->_order='';
        $this->_limit='';
        $this->_join='';
        $this->_debug=false;
        $this->_param=[];
        $this->_sql='';
    }

    //自增
    public function setInc($field,$step=1){
        if($this->_where){
            $update=$field.'='.$field.'+'.$step;
            $this->preWhere();
            $this->_sql="update {$this->_table} set $update {$this->_where};";
            return $this->exec($this->_sql,$this->_param);
        }else{
            echo '保存数据需指定条件';
            die();
        }
    }

    //自减
    public function setDec($field,$step=1){
        if($this->_where){
            $update=$field.'='.$field.'-'.$step;
            $this->preWhere();
            $this->_sql="update {$this->_table} set $update {$this->_where};";
            return $this->exec($this->_sql,$this->_param);
        }else{
            echo '保存数据需指定条件';
            die();
        }
    }
    
    //预处理where条件
    protected function preWhere(){
        $this->_where='where'.trim($this->_where,'and');
        return $this;
    }

    //查询
    protected function _query(){
        $this->preWhere();
        $this->_sql="select {$this->_field} from {$this->_table} {$this->_join} {$this->_where} {$this->_order} {$this->_limit}";
        return $this->query($this->_sql,$this->_param);
    }

    //错误处理
    protected function _error(){
        if($this->_pdo->errorCode()==00000){
            return true;
        }else{
            echo '<pre>';
            $error_msg=$this->_pdo->errorInfo()[2];
            $e=new \Exception($error_msg);
            echo '<h2>'.$error_msg.'</h2>';
            echo '<h2>'.$e->getTrace()[2]['file'].' In line '.$e->getTrace()[2]['line'].'</h2>';
            echo '<h2>SQL 语句:'.$this->debugSql().'</h2>';
            die();
        }
    }

    //生成调试sql
    protected function debugSql(){
        $res=$this->_sql;
        foreach ($this->_param as $k => $v) {
            $res=str_replace(':'.$k,'"'.$v.'"',$res);
        }
        return $res;
    }

}
