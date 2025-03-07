<?php
namespace Tests\Models;
class User extends \Cocoon\Database\Model
{
    protected static $table = 'users';

    public function  relations()
    {
        return [
            'articles' => $this->hasMany(Article::class),
        ];
    }
}