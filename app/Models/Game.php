<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $primaryKey = 'game_id'; //pkの変更
    public $incrementing = false;   //pkが自動増加整数じゃない宣言
    use HasFactory;
}
