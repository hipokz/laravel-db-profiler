<?php

namespace Illuminated\Database;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DbProfilerServiceProvider extends ServiceProvider
{
    private static $counter;

    private static function tickCounter()
    {
        return self::$counter++;
    }

    public function boot()
    {
        if (!$this->isEnabled()) {
            return;
        }

        self::$counter = 1;

        DB::listen(function (QueryExecuted $query) {
            $i = self::tickCounter();
            $sql = $this->applyBindings($query->sql, $query->bindings);
            echo "[$i]: {$sql}; ({$query->time} ms) \n\n";
        });
    }

    private function isEnabled()
    {
        if (!App::environment(['local'])) {
            return false;
        }

        if ($this->app->runningInConsole()) {
            return in_array('-vvv', $_SERVER['argv']);
        }

        return isset($_GET['vvv']);
    }

    private function applyBindings($sql, array $bindings)
    {
        if (empty($bindings)) {
            return $sql;
        }

        foreach ($bindings as $binding) {
            switch (gettype($binding)) {
                case 'boolean':
                    $binding = (int) $binding;
                    break;

                case 'string':
                    $binding = "'{$binding}'";
                    break;
            }

            $sql = preg_replace('/\?/', $binding, $sql, 1);
        }

        return $sql;
    }
}
