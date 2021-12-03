<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait para inserção de usuario de deleção
 *
 * @return void
 */
trait CustomSoftDelete
{
    use SoftDeletes {
        SoftDeletes::runSoftDelete as parentRunSoftDelete;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());
        $time = $this->freshTimestamp();
        $columns = [
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
            'deleted_by' => auth('api')->user() ? auth('api')->user()->getAuthIdentifier() : 1
        ];
        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->timestamps && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));
    }
}
