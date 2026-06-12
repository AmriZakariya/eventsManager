<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\AuditableModel;
use Orchid\Screen\AsSource;

class AwardCategory extends Model {
    use AsSource, AuditableModel;
    protected $fillable = ['name', 'description'];

    public function nominees() {
        return $this->hasMany(AwardNominee::class);
    }
}
