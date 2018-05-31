# 一个简单的PHP Mysql数据库操作类

## 依赖
- pdo

## 安装
1. composer 安装 ``` composer require ninvfeng/mysql ```
2. 引入/vendor目录下的autoload.php ``` require 'vendor/autoload.php'; ```

## 初始化
```
//配置
$config=[
    'host'=>'127.0.0.1',
    'port'=>3306,
    'name'=>'test'
];

//推荐使用函数进行实例化,后续操作更加方便
function db($table='null') use $config{
    static $_db;
    if(!$_db){
        $_db=new \ninvfeng\mysql($config);
    }
    return $_db->table($table);
}
```
### 增
```
db('user')->insert(['user'=>'ninvfeng','pass'=>'password']);
db('user')->insert(['user'=>'lvlv','pass'=>'password']);
```

### 删
```
db('user')->where(['user'=>'ninvfeng'])->delete();
```

### 改
```
db('user')->where(['user'=>'lvlv'])->update(['pass'=>'password2']);
```

### 查找一条
```
db('user')->where(['user'=>'lvlv'])->find();
```

### 查找全部
```
db('user')->select();
```

### 条件查找
```
db('user')->where(['user'=>'ninvfeng'])->select();
```

### 分页查找
```
db('user')->page(1)->select();
```

### 字段查找
```
db('user')->field('user')->select();
```

### 排序
```
db('user')->order('id desc')->select();
```

### join
```
db('user')->join('user_info on user_info.user_id=user.id')->select();
```

### debug 仅打印sql不执行
```
db('user')->debug()->select();
```

### 执行原生sql
```
db('user')->query("select * from user");
```

### 事务
```
db('user')->trans();
```

### 返回原生对象
```
db()->pdo();
```
