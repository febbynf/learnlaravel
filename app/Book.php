<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    //mass assignment
    protected $fillable = ['title', 'author_id', 'amount'];

    public function author()
    {
    	return $this->belongsTo('App\Author');
    }
}
