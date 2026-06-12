<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\AuditableModel;
use Orchid\Screen\AsSource;

class AwardNominee extends Model {
    use AsSource, AuditableModel;
    protected $fillable = [
        'award_category_id', 'company_id', 'product_name', 'image', 'is_winner'
    ];
    protected $casts = ['is_winner' => 'boolean'];

    public function category() { return $this->belongsTo(AwardCategory::class, 'award_category_id'); }
    public function company() { return $this->belongsTo(Company::class); }
}
