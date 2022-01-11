<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class newsTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;



    //Relacion uno a muchos (inversa)
    public function news()
    {
        return $this->belongsTo('App\continent');
    }
}
