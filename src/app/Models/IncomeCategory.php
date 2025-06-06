<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncomeCategory extends Model
{
    use HasFactory;

    protected $fillable = ['type_id', 'icon', 'content', 'user_id', 'deleted', 'filtered_id']; // これらの属性のみが保存される

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type():BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function fixedCategory():HasOne
    {
        return $this->hasOne(FixedCategory::class);
    }

    public function firstCreateData($user)
    {
        foreach(config('app.income_contents') as $key => $incomeContent){
            $this->create([
                'user_id' => $user->id,
                'type_id' => config('app.income_type_id'),
                'filtered_id' => $key + 1,
                'content' => $incomeContent,
                'icon' => config('app.income_icons')[$key]
            ]);
            return $this;
        }
    }

    public function createOrUpdateData($tgtModel, $data){
        if (isset($tgtModel)) {
            // 存在する場合、更新
            $tgtModel->type_id = Type::where("en_name", $data['type'])->first()->id;
            $tgtModel->icon = isset($data['icon']) ? $data['icon'] : "";
            $tgtModel->content = $data['content'];
            $tgtModel->save();
            return $tgtModel;
        } else {
            // 存在しない場合、新しいレコードを作成
            $this->type_id = Type::where("en_name", $data['type'])->first()->id;
            $this->user_id = $data['user_id'];
            $this->icon = isset($data['icon']) ? $data['icon'] : "";
            $this->content = $data['content'];
            $this->save();
        }
        return $this;
    }

    public function deleteData($tgtModel, $data){
        $tgtModel->delete();
    }
    public function sortData($tgtModel, $data, $first_id){
        if($tgtModel){
            $tgtModel->filtered_id = $data->filtered_id;
            $tgtModel->save();
            return $tgtModel;
        }
    }
}