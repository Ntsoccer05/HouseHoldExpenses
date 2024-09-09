<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class ExpenceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['type_id', 'icon', 'content', 'fixed_category_id', 'user_id']; // これらの属性のみが保存される

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type():BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function saveTgtUpdateContent($updateData)
    {
        if($updateData['content'] || $updateData['icon']){
            $this->content = $updateData['content'];
            $this->content = $updateData['icon'];
            $this->save();
        }
    }

    public function createOrUpdateData($tgtModel, $data){
        if (isset($tgtModel)) {
            // 存在する場合、更新
            $tgtModel->type_id = Type::where("en_name", $data['type'])->first()->id;
            $tgtModel->icon = isset($data['icon']) ? $data['icon'] : "";
            $tgtModel->content = $data['content'];
            $tgtModel->save();
        } else {
            // 存在しない場合、新しいレコードを作成
            $this->fixed_category_id = $data['fixed_category_id'];
            $this->type_id = Type::where("en_name", $data['type'])->first()->id;
            $this->user_id = $data['user_id'];
            $this->icon = isset($data['icon']) ? $data['icon'] : "";
            $this->content = $data['content'];
            $this->save();
        }
    }
    public function deleteData($tgtModel, $data){
        if(isset($data->fixed_category_id)){
            if(isset($tgtModel)){
                $tgtModel->deleted = 1;
                $tgtModel->save();
            }else{
                $model = new ExpenceCategory();
                $model->fixed_category_id = $data->fixed_category_id;
                $model->type_id = config('app.expense_type_id');
                $model->user_id = $data->user_id;
                $model->icon = $data->icon;
                $model->content = isset($data->content) ? $data->content : $data->label;
                $model->deleted = 1;
                $model->save();
            }
        }else{
            $tgtModel->deleted = 1;
            $tgtModel->delete();
        }
    }
}