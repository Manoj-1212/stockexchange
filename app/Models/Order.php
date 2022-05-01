<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class Order extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $timestamps = true;
    protected $guarded = [];

    protected $table = "order_checkout";

    const TRADE = ['pending' => 1, 'active' => 0, 'closed' => 2];

}
