<?php
namespace Tests\Models;
class Article extends \Cocoon\Database\Model
{
      protected static $table = 'articles';

      public function relations()
      {
          return [
            'user' => $this->belongsTo(User::class),
            'tags' => $this->belongsToMany(Tag::class, ArticleTag::class)
          ];
      }
}