<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Author extends Model
{
    //mass assignment
    protected $fillable = ['name'];

    public function books()
    {
    	return $this->hasMany('App\Book');
    }

    public static function boot() 
    {
    	parent::boot();

    	self::deleting(function($author) {

    		//penulis masih punya buku?
    		if($author->books->count() > 0) {

    			$html = 'Penulis masih memiliki buku : ';
    			$html .= '<ul>';
    			foreach ($author->books as $book) {
    			 	$html .= "<li>$book->title</li>";
    			}
    			$html .= '</ul>';

    			Session::flash("flash_notification", [
    				"level" => "danger",
    				"message" => $html
    			]);

    			//membatalkan proses hapus
    			return false; 
    		}
    	});
    }
}
