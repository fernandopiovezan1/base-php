<?php /** @noinspection PhpUndefinedClassInspection */

namespace App\Traits;

/**
 * Trait para transformação de de todos os
 * campos string em upper case
 * @property $attributes
 */
trait SaveToUpper
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

    /** @noinspection PhpUndefinedMethodInspection */
    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $value);
        if ($this->getFieldType($key) === 'string'
            && !in_array($key, $this->noUppercase)) {
            $this->attributes[$key] = trim(mb_strtoupper($value));
        }
    }
}
