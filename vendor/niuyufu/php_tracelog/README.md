php_tracelog

CI 支持 tracelog 类库

## 使用方法
### 初始化操作日志

```
// 定义tracelog的action
$actionMap = [
    'adapt_area_switch_edit' => '自适应区域配时开关修改',
];
initOperateLog("operate_log.log", $actionMap, 1);
```

### 记录操作日志
```
operateLog("niuyufu", "adapt_area_switch_edit", 516, ["old" => "status=0", "new" => "status=1",]);
```
## 安装类库

1、修改 composer.json 文件，添加如下内容：
```
{
  "repositories": [
    {
      "type": "git",
      "url": "git@git.xiaojukeji.com:niuyufu/php_tracelog.git"
    }
  ],
  "require": {
    "niuyufu/php_tracelog": "*"
  }
}
```
2、执行

```
composer update
```


