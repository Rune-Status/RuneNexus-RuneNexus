<?php
use Illuminate\Database\Eloquent\Model as Model;

class Outbound extends Model {

    public $timestamps    = false;
    public $incrementing  = false;
    
    protected $fillable = [
        'server_id',
        'ip_address',
        'clicks',
        'click_date'
    ];

    protected $table = "outbound";


}