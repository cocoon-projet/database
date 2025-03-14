<?php
namespace Tests\Models;
class Article extends \Cocoon\Database\Model
{
      protected static $table = 'articles';

      public static function scopes()
    {
        return [
            'user_id_one' => function ($query) {
                return $query->where('user_id', '=', 1);
            }
        ];
    }

      public function relations()
      {
          return [
            'user' => $this->belongsTo(User::class),
            'tags' => $this->belongsToMany(Tag::class, ArticleTag::class)
          ];
      }
}