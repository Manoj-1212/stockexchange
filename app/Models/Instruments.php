<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class Instruments extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $timestamps = true;
    protected $guarded = [];

    protected $table = "instruments";


    /**
     * check if the user is admin
     *
     * @return boolean
     */
    public function is_NFO_MCX()
    {
        return $this->exchange === 'NFO' ? 1 : 2;
    }
}
