<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class QueuedJob extends Model
{

    protected $fillable = [
        'job',
        'class',
        'external_id',
    ];

}