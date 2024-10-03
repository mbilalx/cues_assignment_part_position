<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Set the innodb_lock_wait_timeout to a custom value.
     *
     * @param int $timeout The new lock wait timeout in seconds.
     * @return void
     */
    public static function setLockTimeout(int $timeout): void
    {
        DB::statement("SET innodb_lock_wait_timeout = {$timeout}");
    }

    /**
     * Get the current value of the innodb_lock_wait_timeout.
     *
     * @return int The current lock wait timeout value.
     */
    public static function getCurrentLockTimeout(): int
    {
        $result = DB::select("SELECT @@innodb_lock_wait_timeout as timeout");
        return $result[0]->timeout ?? 50; // Return 50 as a default if no value found
    }
}
