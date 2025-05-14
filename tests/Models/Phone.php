<?php

namespace Tests\Models;

class Phone extends \Cocoon\Database\Model
{
    protected static ?string $table = 'phones';

    public function relations()
    {
        return [
            'user' => $this->belongsTo(User::class)
        ];
    }
}