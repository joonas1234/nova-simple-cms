<?php

namespace Joonas1234\NovaSimpleCms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\UpdateResourceRequest;

class UpdateController extends Controller
{
    
    public function formFields(NovaRequest $request)
    {
        $resource = $request->newResourceWith($request->findModelOrFail());

        $blueprint = $request->changedblueprint ?? $resource->blueprint;

        $request->request->add(['blueprint' => $blueprint]);
        $request->query->add(['blueprint' => $blueprint]);

        $resource->authorizeToUpdate($request);

        $fields = $resource->updateFieldsWithinPanels($request);

        $dynamicFields = array_keys(config('blueprints.' . $blueprint . '.fields'));

        foreach($fields as $field) {
            if(in_array($field->attribute, $dynamicFields)) {
                $field->value = $resource->data[$field->attribute] ?? null;
            }
        }
        
        return response()->json([
            'fields' => $fields,
            'panels' => $request->newResource()->availablePanelsForUpdate($request),
            'blueprint' => $blueprint,
        ]);
    }

    /**
     * Create a new resource.
     *
     * @param  \Laravel\Nova\Http\Requests\UpdateResourceRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(UpdateResourceRequest $request)
    {
        $request->findResourceOrFail()->authorizeToUpdate($request);

        $resource = $request->resource();

        $resource::validateForUpdate($request);

        $model = DB::transaction(function () use ($request, $resource) {
            $model = $request->findModelQuery()->lockForUpdate()->firstOrFail();

            // Catch data column from model
            $data = $model->data;

            if ($this->modelHasBeenUpdatedSinceRetrieval($request, $model)) {
                return response('', 409)->throwResponse();
            }

            [$model, $callbacks] = $resource::fillForUpdate($request, $model);

            $fields = config('blueprints.' . $request->blueprint . '.fields');

            foreach($fields as $fieldName => $fieldSettings) {

                // Don't touch if field is file and nothing is posted
                if(in_array($fieldSettings['type'], ['File', 'Image'])) {

                    $data[$fieldName] = $model->$fieldName ?? $data[$fieldName] ?? null;
                } else {
                    $data[$fieldName] = $model->$fieldName;
                }  

                // remove dynamic fields from model so model can be saved
                unset($model->$fieldName);
            }
            // Assign data array to data column
            $model->data = $data; 

            $model->save();

            ActionEvent::forResourceUpdate($request->user(), $model)->save();

            collect($callbacks)->each->__invoke();

            return $model;
        });

        return response()->json([
            'id' => $model->getKey(),
            'resource' => $model->attributesToArray(),
            'redirect' => $resource::redirectAfterUpdate($request, $request->newResourceWith($model)),
        ]);
    }

    /**
     * Determine if the model has been updated since it was retrieved.
     *
     * @param  \Laravel\Nova\Http\Requests\UpdateResourceRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function modelHasBeenUpdatedSinceRetrieval(UpdateResourceRequest $request, $model)
    {
        $column = $model->getUpdatedAtColumn();

        if (! $model->{$column}) {
            return false;
        }

        return $request->input('_retrieved_at') && $model->usesTimestamps() && $model->{$column}->gt(
            Carbon::createFromTimestamp($request->input('_retrieved_at'))
        );
    }
}