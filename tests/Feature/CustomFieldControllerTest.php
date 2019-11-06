<?php

namespace Givebutter\Tests\Feature;

use Givebutter\LaravelCustomFields\Models\CustomField;
use Givebutter\Tests\Support\Survey;
use Givebutter\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CustomFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function valid_data_passes_controller_validation()
    {
        $survey = Survey::create();
        $survey->customfields()->save(
            factory(CustomField::class)->make([
                'title' => 'email',
                'type' => 'text',
            ])
        );

        Route::post("/surveys/{$survey->id}/responses", function (Request $request) use ($survey) {
            $survey->validateCustomFields($request);

            return response('All good', 200);
        });

        $this
            ->post("/surveys/{$survey->id}/responses", [
                'custom_fields' => [
                    'email' => 'daniel@tighten.co',
                ],
            ])->assertOk();
    }


    /** @test */
    public function invalid_data_throws_validation_exception()
    {
        $survey = Survey::create();
        $survey->customfields()->save(
            factory(CustomField::class)->make([
                'title' => 'favorite_album',
                'type' => 'select',
                'answers' => ['Tha Carter', 'Tha Carter II', 'Tha Carter III'],
            ])
        );

        Route::post("/surveys/{$survey->id}/responses", function (Request $request) use ($survey) {
            $validator = $survey->validateCustomFields($request);

            if ($validator->fails()) {
                return ['errors' => $validator->errors()];
            }

            return response('All good', 200);
        });

        $this
            ->post("/surveys/{$survey->id}/responses", [
                'custom_fields' => [
                    'favorite_album' => 'Yeezus',
                ],
            ])->assertJsonFragment(['favorite_album' => ['The selected favorite album is invalid.']]);
    }
}
