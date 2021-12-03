<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;

/**
 * Trait para validação de dias e horarios
 * permitidos para acesso do usuário
 */
trait UserValidation
{
    protected function validWorkSchedule(User $user)
    {
        $now = Carbon::now();
        if (!empty($user->start_work) && !empty($user->end_work)) {
            return $now->between($user->start_work, $user->end_work);
        }
        return true;
    }

    /**
     * @param User $user
     * @return bool
     */
    protected function validDaySchedule(User $user)
    {
//        $now = Carbon::now();
//        $days = $user->days_week_work;
//        if (!empty($days)) {
//            return isset($days[$now->dayOfWeek]) && $days[$now->dayOfWeek] == $now->dayOfWeek;
//        }
        return empty($user);
    }
}
