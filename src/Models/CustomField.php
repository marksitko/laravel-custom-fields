<?php

namespace Givebutter\LaravelCustomFields\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CustomField extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'answers' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();
        $this->initializeTraits();
        $this->syncOriginal();
        $this->fill($attributes);

        $this->table = config('custom-fields.tables.fields', 'custom_fields');
    }

    private function fieldValidationRules()
    {
        return [
            'text' => [
                'nullable',
                'string',
                'max:255',
            ],
            'textarea' => [
                'nullable',
                'string',
            ],
            'select' => [
                'nullable',
                'string',
                'max:255',
                Rule::in($this->answers),
            ],
            'number' => [
                'nullable',
                'integer',
            ],
            'checkbox' => [
                'nullable',
                'boolean',
            ],
            'radio' => [
                'nullable',
                'string',
                'max:255',
                Rule::in($this->answers),
            ],
        ];
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function responses()
    {
        return $this->hasMany(CustomFieldResponse::class, 'field_id');
    }

    public function getValidationRulesAttribute()
    {
        $typeRules = $this->fieldValidationRules()[$this->type];

        if ($this->required) {
            array_push($typeRules, 'required');
        }

        return $typeRules;
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($field) {
            $lastFieldOnCurrentModel = $field->model->customFields()->orderBy('order', 'desc')->first();
            $field->order = ($lastFieldOnCurrentModel ? $lastFieldOnCurrentModel->order : 0) + 1;
        });
    }
}
