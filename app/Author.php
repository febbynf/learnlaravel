<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    //mass assignment
    protected $fillable = ['name'];

    public function books()
    {
    	return $this->hasMany('App\Book');
    }
}
