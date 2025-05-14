<?php
namespace Tests\Models;
class User extends \Cocoon\Database\Model
{
    protected static ?string $table = 'users';

    public function  relations()
    {
        return [
            'articles' => $this->hasMany(Article::class),
            'phone' => $this->hasOne(Phone::class)
        ];
    }
}