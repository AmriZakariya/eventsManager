<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class ProductCategory extends Model
{
    use AsSource, HasLocalizedContent;

    protected $fillable = ['name', 'name_translations', 'slug'];

    protected $casts = [
        'name_translations' => 'array',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
