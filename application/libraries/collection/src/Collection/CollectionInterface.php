<?php

namespace Didi\Cloud\Collection\Collection;

interface CollectionInterface
{
    /**
     * 求数组的平均值，并对结果进行回调处理（保留两位小数...）
     *
     * @param string|int|null $key 二维数组元素的键名，求一位数组则设为 null
     * @param callable|null $callback 处理结果的回调函数，可用来处理小数位数
     * @return float|int|null
     */
    public function avg($key = null, $callback = null);

    /**
     * 遍历数组，回调函数返回 false 则跳出循环
     *
     * @param Callable $callback function($k, $v) { ... }
     * @return $this
     */
    public function each($callback);

    /**
     * 移除指定键值，支持 . 表示深度，可关闭对 . 的支持
     *
     * @param string|int $key 想要移除的键名，可包含 .
     * @param bool $dotKey 是否关闭对 . 的支持
     * @return Collection
     */
    public function forget($key, $dotKey = true);

    /**
     * 获取集合指定键值的元素，支持 . 表示深度，可关闭对 . 的支持
     *
     * @param null $key 想要获取的键名，可包含 . ，为 null 则获取完整的集合元素
     * @param null $default 集合中不存在目标元素则返回 $default
     * @param bool $dotKey 是否关闭对 . 的支持
     * @return array|mixed
     */
    public function get($key = null, $default = null, $dotKey = true);

    /**
     * 将集合按照指定参数分组
     *
     * @param string|int|array|callable $param 分组依据的字段
     * @param callable|null $callback 分组后每组数据的处理
     * @param bool $preserveKey 分组后是否保留原有的键名
     * @return Collection|mixed 返回分组后的集合对象
     */
    public function groupBy($param, $callback = null, $preserveKey = false);

    /**
     * 判断集合是否存在指定键名，支持 . 表示深度
     *
     * @param array|string $key 指定键名
     * @param mixed $value 指定键值
     * @param bool $dotKey 是否开启 . 表示深度
     * @return bool|mixed
     */
    public function has($key, $value = null, $dotKey = true);

    /**
     * 设置指定键值，支持 . 表示深度
     *
     * @param string $key
     * @param mixed $value
     * @param bool $dotKey
     * @return Collection|mixed|null
     */
    public function set($key, $value, $dotKey = true);

    /**
     * 将二维数组按照指定字段排序
     *
     * @param string|int|callable $param 排序字段
     * @param int $arraySortOrder 排序方式
     * @return Collection|mixed
     */
    public function sortBy($param, $arraySortOrder = SORT_ASC);
}