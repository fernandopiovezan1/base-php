<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\CustomSoftDelete;

abstract class BaseModel extends Model
{
    use HasFactory, CustomSoftDelete;

    protected bool $hasCompanyId = true;
    protected array $relationsBySearch = [];
    protected array $noUpper = [];
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_active',
    ];

    /**
     * @return bool
     */
    public function hasCompanyId(): bool
    {
        return $this->hasCompanyId;
    }

    /**
     * Função responsável por gravar log onde for definido
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param String $event
     * @return array
     */
    public function saveLog(Model $model, string $event)
    {
        if ($event == 'saving' && $model->exists) {
            $log = array_diff_assoc($model->getAttributes(), $model->getOriginal());
        } elseif ($event == 'deleting' && !$model->exists) {
            $log = $model->getAttributes();
        } else {
            $log = $model->getAttributes();
        }
        return $log;
    }

    public static function getCastsStatic(): array
    {
        return (new static())->getCasts();
    }

    /**
     * Função responsável por retornar o tipo do campo
     * para a consulta
     * @param string $field
     * @return string|bool
     */
    public static function getFieldType(string $field)
    {
        if (array_key_exists($field, static::getCastsStatic())) {
            return (new static())->getCastType($field);
        }

        return false;
    }

    /**
     * Método para retornar os relacionamentos
     * que podem ser consultados
     * @return string[]
     */
    public function getRelationsBySearch()
    {
        return $this->relationsBySearch;
    }

    /**
     * Retorna se registo esta ativo
     * @return bool
     */
    public function getIsActiveAttribute()
    {
        return empty($this->deleted_at);
    }

    protected static function boot()
    {
        static::saving(function ($model) {
            if ($model->exists) {
                $model->updated_by = auth('api')->user() ? auth('api')->user()->getAuthIdentifier() : 1;
            } else {
                $model->created_by = auth('api')->user() ? auth('api')->user()->getAuthIdentifier() : 1;
                if ($model->hasCompanyId) {
                    if (config('app.env') === 'testing' || PHP_SAPI === 'cli') {
                        $model->company_id = 1;
                    } else {
                        $model->company_id = config('current_company_id') ?? auth('api')->user()->company_id;
                    }
                }
            }
            //             Descomentar quando definir como será usado o log
            //             parent::saveLog($model, 'saving');
        });
        parent::boot();
    }
}
