<?php namespace Bugcat\Tools\Laravel\Models;

//新增的扩展
trait CustomExpansionNew
{
    
    /**
     * 插入数据
     *
     * @param  array|object  $data  
     * @return true
     */
    public function add($data = [])
    {
        return $this->upd($data, 'id');
    }
    
    /**
     * 更新数据
     *
     * @param  array|object  $data  
     * @return true
     */
    public function upd($data = [], $rtn = null)
    {
        foreach ( $data as $key => $value ) {
            $this->$key = $value;
        }
        $this->save();
        if ( is_string($rtn) ) {
            return $this->$rtn;
        } elseif ( is_array($rtn) ) {
            return $this;
        } else {
            return $this;
        }
        return $this;
    }
    
    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \App\Models\Base\BaseModel|static
     */
    public function updBy(array $attributes, array $values = [])
    {
        //updateOrCreate @return Model
        //updateOrInsert @return bool
        $where = [];
        foreach ( $attributes as $f => $v) {
            $where[] = [$f, $v];
        }
        $record = $this->where($where)->first();
        if ( empty($record) ) {
            $data = array_merge($attributes, $values);
            return $this->upd($data);
        } else {
            return empty($values) ? $record : $record->upd($values);
        }
    }
    
}
