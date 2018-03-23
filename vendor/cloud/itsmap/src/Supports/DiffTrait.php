<?php

namespace Didi\Cloud\ItsMap\Supports;

/*
 * 提供比较操作的特性，有这个特性的类，只需要定义protected的几个方法，就可以进行比较，判断出增加，删除，更新的对象
 */
trait DiffTrait
{
    // 需要更新的字段
    protected function updateFields()
    {
        return [];
    }

    // 设置唯一的key
    protected function uniqFields()
    {
        return [];
    }

    /*
     * 唯一键
     */
    public function uniq()
    {
        $key = "";
        foreach ($this->uniqFields() as $field) {
            $key .= $this->$field . "_";
        }
        return $key;
    }

    /*
     * 判断是否需要更新
     */
    public function isNeedUpdate($object)
    {
        foreach($this->updateFields() as $field) {
            if ($this->$field != $object->$field) {
                return true;
            }
        }
        return false;
    }

    /*
     * 更新操作
     */
    public function update($object)
    {
        foreach($this->updateFields() as $field) {
            $this->$field = $object->$field;
        }
        return $this;
    }
}

