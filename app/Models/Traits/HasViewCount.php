<?php namespace App\Models\Traits;

use Illuminate\Support\Str;

Trait HasViewCount
{
    protected static $viewModel;
    protected static $viewModelTableName;
    protected static $modelInstance;
    protected static $modelTableName;

    public static function getModelTableName()
    {
        if (self::$modelTableName) {
            return self::$modelTableName;
        }

        if (!self::$modelInstance) {
            // unfortunately we have to create an instance of the model
            // to get the table name, so we'll cache it
            // with a static variable
            self::$modelInstance = new static;
        }

        $tableName = self::$modelInstance->getTable();
        return self::$modelTableName = (object) [
            'plural' => $tableName,
            'singular' => Str::singular($tableName)
        ];
    }

    public static function getViewModelClassName(): string
    {
        throw new \Exception('You must define getViewModelClassName() method to use HasViewCount trait');
    }

    public function viewedByUser($userId): bool
    {
        return $this->views()->where('user_id', $userId)->exists();
    }

    public function getMostRecentViewByUser($userId)
    {
        return $this->views()
            ->take(1)
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    public function views()
    {
        return $this->hasMany($this->getViewModelClassName());
    }

    public function addViewByUser($userId): self
    {
        $this->views()->create([
            'user_id' => $userId
        ]);

        $this->increment('view_count');

        return $this;
    }

    public static function getViewModelTableName(): \stdClass
    {   
        if (self::$viewModelTableName) {
            return self::$viewModelTableName;
        }

        if (!self::$viewModel) {
            $viewModelName = self::getViewModelClassName();
            // unfortunately we have to create an instance of the model
            // to get the table name, so we'll cache it
            // with a static variable
            self::$viewModel = new $viewModelName;
        }

        $tableName = self::$viewModel->getTable();
        return self::$viewModelTableName = (object) [
            'plural' => $tableName,
            'singular' => Str::singular($tableName)
        ];
    }

    public static function whereNotViewedByUser($userId)
    {
        $viewTable = self::getViewModelTableName();
        $table = self::getModelTableName();

        return static::whereNotExists(function($query) use ($userId, $table, $viewTable) {
            $query->selectRaw(1)
                ->from($viewTable->plural)
                ->whereColumn("{$table->plural}.id", "{$table->singular}_id")
                ->where('user_id', $userId);
        });
    }

    public static function whereViewedByUser($userId)
    {
        $viewTable = self::getViewModelTableName();
        $table = self::getModelTableName();

        return static::whereExists(function($query) use ($userId, $table, $viewTable) {
            $query->selectRaw(1)
                ->from($viewTable->plural)
                ->whereColumn("{$table->plural}.id", "{$table->singular}_id")
                ->where('user_id', $userId);
        });
    }
}