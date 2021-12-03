<?php

namespace App\Traits;

/**
 * Trait para transformação de de todos os
 * campos string em upper case
 */
trait ReturnToUpper
{

    /**
     * Default params that will be saved on lowercase
     * @var array No Uppercase keys
     */
    protected array $noUppercase = [
        'password',
        'username',
        'email',
        'remember_token',
        'slug',
        'login'
    ];

    /** @noinspection PhpUndefinedClassInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function attributesToArray()
    {
        $array = parent::attributesToArray();
        foreach ($array as $name => $value) {
            if ($this->getFieldType($name) === 'string'
                && !in_array($name, $this->noUppercase)) {
                $array[$name] = mb_strtoupper($value);
            } else {
                $array[$name] = $value;
            }
        }
        return $array;
    }
}
