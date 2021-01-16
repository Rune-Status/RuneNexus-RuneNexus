<?php
use Illuminate\Database\Eloquent\Model as Model;

class Sessions extends Model {

    public $timestamps    = false;
    public $incrementing  = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'token',
        'user_id',
        'ip_address',
        'started',
        'expires',
        'discord_token'
    ];


}