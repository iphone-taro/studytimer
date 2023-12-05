<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    use HasFactory;

    /// 主キーカラム名を指定
    protected $primaryKey = 'date';
    /// オートインクリメント無効化
    public $incrementing = false;
    /// Laravel 6.0+以降なら指定
    protected $keyType = 'string';
}
