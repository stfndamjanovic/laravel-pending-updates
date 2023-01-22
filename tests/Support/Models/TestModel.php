<?php

namespace Stfn\PendingUpdates\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Models\Concerns\HasPendingUpdate;

class TestModel extends Model
{
    use HasPendingUpdate, HasFactory;

    public $hidden = [
        'password',
    ];

    public $fillable = ['name'];
}
