<?php

namespace App\Repositories;

class EloquentRepository
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function getAll($limit = 50, $offset = 0)
    {
        return $this->model->take($limit)->skip($offset)->get()->toArray();
    }

    public function store(array $properties)
    {
        $model = $this->model->create($properties);

        $model->save();

        return $model->id;
    }

    /**
     * Insert a model if no id field is passed, or update
     * if the opposite is the case
     *
     * @param  array $models
     * @return void
     */
    public function bulkUpsert(array $models)
    {
        foreach ($models as $model) {
            if (array_key_exists('id', $model)) {
                $this->update($id, $model);
            } else {
                $this->store($model);
            }
        }
    }

    /**
     * Where proxy function for the underlying Eloquent model
     *
     * @param  string  $key
     * @param  string  $value
     * @return Builder
     */
    public function where($key, $value)
    {
        return $this->model->where($key, $value);
    }

    public function delete($modelId)
    {
        $model = $this->model->find($modelId);

        if (! empty($model)) {
            return $model->delete();
        }

        return false;
    }

    public function update($modelId, $properties)
    {
        $properties = array_only($properties, $this->model->getFillable());

        $model = $this->model->find($modelId);

        if (! empty($model)) {
            return $model->update($properties);
        }

        return false;
    }

    public function getById($modelId)
    {
        $model = $this->model->find($modelId);

        if (! empty($model)) {
            return $model->toArray();
        }

        return null;
    }
}
