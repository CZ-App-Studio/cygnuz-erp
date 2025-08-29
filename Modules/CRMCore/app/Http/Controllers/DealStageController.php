<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\CRMCore\app\Models\DealPipeline; // For unique rule scoped to pipeline
use Modules\CRMCore\app\Models\DealStage;

class DealStageController extends Controller
{
    /**
     * Display all deal stages across all pipelines.
     * If there's only one pipeline or a default pipeline, redirect to that pipeline's stages.
     */
    public function allStages()
    {
        // Get the first active pipeline or create one if none exists
        $deal_pipeline = DealPipeline::where('is_active', true)
            ->orderBy('position')
            ->first();

        if (! $deal_pipeline) {
            // Get any pipeline if no active ones
            $deal_pipeline = DealPipeline::orderBy('position')->first();
        }

        if (! $deal_pipeline) {
            // Create a default pipeline if none exists
            $deal_pipeline = DealPipeline::create([
                'name' => 'Default Pipeline',
                'description' => 'Default sales pipeline',
                'is_active' => true,
                'position' => 1,
            ]);

            // Create default stages
            $defaultStages = [
                ['name' => 'Lead', 'position' => 1, 'color' => '#748494'],
                ['name' => 'Qualified', 'position' => 2, 'color' => '#5A8DEE'],
                ['name' => 'Proposal', 'position' => 3, 'color' => '#FDAC41'],
                ['name' => 'Negotiation', 'position' => 4, 'color' => '#FF5B5C'],
                ['name' => 'Won', 'position' => 5, 'color' => '#39DA8A', 'is_won' => true],
                ['name' => 'Lost', 'position' => 6, 'color' => '#475F7B', 'is_lost' => true],
            ];

            foreach ($defaultStages as $stage) {
                $deal_pipeline->stages()->create($stage);
            }
        }

        $stages = $deal_pipeline->stages()->orderBy('position')->get();

        return view('crmcore::deal_stages.index', compact('deal_pipeline', 'stages'));
    }

    /**
     * DataTable for all deal stages.
     */
    public function datatable(Request $request)
    {
        $query = DealStage::with('pipeline')
            ->select('deal_stages.*');

        return datatables()
            ->eloquent($query)
            ->addColumn('pipeline_name', function ($stage) {
                return $stage->pipeline ? $stage->pipeline->name : 'N/A';
            })
            ->addColumn('actions', function ($stage) {
                return view('crmcore::deal_stages.actions', compact('stage'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Display a listing of the stages for a specific pipeline.
     */
    public function index(DealPipeline $deal_pipeline)
    {
        $stages = $deal_pipeline->stages()->orderBy('position')->get();

        return view('crmcore::deal_stages.index', compact('deal_pipeline', 'stages'));
    }

    /**
     * Fetch a single stage's data for the offcanvas edit form.
     * The {deal_pipeline} is implicitly available if needed but not directly used here
     * as $deal_stage->pipeline_id is already set.
     */
    public function getStageAjax(DealPipeline $deal_pipeline, DealStage $deal_stage)
    {
        // Ensure the stage belongs to the pipeline (though route model binding should handle this)
        if ($deal_stage->pipeline_id !== $deal_pipeline->id) {
            return response()->json(['code' => 404, 'message' => 'Stage not found in this pipeline.'], 404);
        }

        return response()->json($deal_stage);
    }

    /**
     * Store a newly created stage in storage for the given pipeline.
     */
    public function store(Request $request, DealPipeline $deal_pipeline)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('deal_stages')->where(function ($query) use ($deal_pipeline) {
                    return $query->where('pipeline_id', $deal_pipeline->id);
                }),
            ],
            'color' => 'nullable|string|max:20',
            'is_default_for_pipeline' => 'boolean',
            'is_won_stage' => 'boolean',
            'is_lost_stage' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['pipeline_id'] = $deal_pipeline->id;
            $data['is_default_for_pipeline'] = $request->boolean('is_default_for_pipeline');
            $data['is_won_stage'] = $request->boolean('is_won_stage');
            $data['is_lost_stage'] = $request->boolean('is_lost_stage');

            if ($data['is_default_for_pipeline']) {
                $deal_pipeline->stages()->where('is_default_for_pipeline', true)->update(['is_default_for_pipeline' => false]);
            }
            if ($data['is_won_stage']) { // Ensure only one won stage per pipeline
                $deal_pipeline->stages()->where('is_won_stage', true)->update(['is_won_stage' => false]);
            }
            if ($data['is_lost_stage']) { // Ensure only one lost stage per pipeline
                $deal_pipeline->stages()->where('is_lost_stage', true)->update(['is_lost_stage' => false]);
            }

            $data['position'] = ($deal_pipeline->stages()->max('position') ?? 0) + 1;

            DealStage::create($data);
            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Deal Stage created successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("DealStage Store for Pipeline ID {$deal_pipeline->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to create stage.'], 500);
        }
    }

    /**
     * Update the specified stage in storage.
     */
    public function update(Request $request, DealPipeline $deal_pipeline, DealStage $deal_stage)
    {
        if ($deal_stage->pipeline_id !== $deal_pipeline->id) {
            return response()->json(['code' => 404, 'message' => 'Stage not found in this pipeline.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('deal_stages')->where(function ($query) use ($deal_pipeline) {
                    return $query->where('pipeline_id', $deal_pipeline->id);
                })->ignore($deal_stage->id),
            ],
            'color' => 'nullable|string|max:20',
            'is_default_for_pipeline' => 'boolean',
            'is_won_stage' => 'boolean',
            'is_lost_stage' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_default_for_pipeline'] = $request->boolean('is_default_for_pipeline');
            $data['is_won_stage'] = $request->boolean('is_won_stage');
            $data['is_lost_stage'] = $request->boolean('is_lost_stage');

            if ($data['is_default_for_pipeline']) {
                $deal_pipeline->stages()->where('id', '!=', $deal_stage->id)
                    ->where('is_default_for_pipeline', true)->update(['is_default_for_pipeline' => false]);
            }
            if ($data['is_won_stage']) {
                $deal_pipeline->stages()->where('id', '!=', $deal_stage->id)
                    ->where('is_won_stage', true)->update(['is_won_stage' => false]);
            }
            if ($data['is_lost_stage']) {
                $deal_pipeline->stages()->where('id', '!=', $deal_stage->id)
                    ->where('is_lost_stage', true)->update(['is_lost_stage' => false]);
            }

            $deal_stage->update($data);
            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Deal Stage updated successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("DealStage Update for ID {$deal_stage->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to update stage.'], 500);
        }
    }

    /**
     * Remove the specified stage from storage.
     */
    public function destroy(DealPipeline $deal_pipeline, DealStage $deal_stage)
    {
        if ($deal_stage->pipeline_id !== $deal_pipeline->id) {
            return response()->json(['code' => 404, 'message' => 'Stage not found in this pipeline.'], 404);
        }

        try {
            if ($deal_stage->deals()->exists()) {
                return response()->json(['code' => 400, 'message' => 'Cannot delete stage as it has associated deals.'], 400);
            }
            if ($deal_stage->is_default_for_pipeline && $deal_pipeline->stages()->count() > 1) {
                return response()->json(['code' => 400, 'message' => 'Cannot delete the default stage. Assign another stage as default first.'], 400);
            }
            // Add similar checks for is_won_stage or is_lost_stage if you have logic depending on their existence

            $deal_stage->delete();

            // Re-order remaining stages might be needed if positions are strictly sequential without gaps
            // For simplicity, current Kanban JS can handle visual gaps or re-fetch.
            return response()->json(['code' => 200, 'message' => 'Deal Stage deleted successfully.']);
        } catch (Exception $e) {
            Log::error("DealStage Delete for ID {$deal_stage->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to delete stage.'], 500);
        }
    }

    /**
     * Update the order of stages within a pipeline.
     */
    public function updateOrder(Request $request, DealPipeline $deal_pipeline)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:deal_stages,id']);
        try {
            $orderedStageIds = $request->input('order');
            foreach ($orderedStageIds as $index => $id) {
                // Ensure the stage being ordered actually belongs to the current pipeline
                DealStage::where('id', $id)
                    ->where('pipeline_id', $deal_pipeline->id)
                    ->update(['position' => $index + 1]);
            }

            return response()->json(['code' => 200, 'message' => 'Stage order updated successfully.']);
        } catch (Exception $e) {
            Log::error("DealStage Order Update for Pipeline ID {$deal_pipeline->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to update stage order.'], 500);
        }
    }
}
