<?php namespace Bugcat\Gist\Laravel\Models;

use Illuminate\Database\Eloquent\Collection;


class CustomCollection extends Collection
{
    
    public function toList($by = 'id', $rtnMode = null)
    {
        $dft_by = 'id';
        if ( true === $rtnMode ) {
            //return array
            $list = [];
            $items = $this->toArray();
            foreach ( $items as $li ) {
                $list[$li[$by] ?? $li[$dft_by]] = $li;
            }
        } elseif ( false === $rtnMode ) {
            //return object
            $list = new \stdClass;
            foreach ( $this->items as $li ) {
                $list->{$li->$by ?? $li->$dft_by} = $li;
            }
        } else {
            //return array but the items are object
            $list = [];
            foreach ( $this->items as $li ) {
                $list[$li->$by ?? $li->$dft_by] = $li;
            }
        }
        return $list;
    }
    
}
