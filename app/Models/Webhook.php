<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Auth;
use Orchid\Screen\AsSource;

class Webhook extends Model
{
    use HasFactory, AsSource;

    const ROUTE_TYPE_WHATSAPP = 0;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4();
            $model->user_id = Auth::id();
        });
    }
}
