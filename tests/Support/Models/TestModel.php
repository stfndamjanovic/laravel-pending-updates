<?php

namespace Stfn\PostponeUpdates\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stfn\PostponeUpdates\Models\Concerns\HasPostponedUpdates;

class TestModel extends Model
{
    use HasPostponedUpdates, HasFactory;

    public $hidden = [
        'password',
    ];

    public $fillable = [
        'name',
        'secret',
    ];
}
