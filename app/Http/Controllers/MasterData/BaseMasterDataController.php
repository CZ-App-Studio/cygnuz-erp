<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AddonService\AddonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

abstract class BaseMasterDataController extends Controller
{
    protected AddonService $addonService;

    protected string $viewPrefix = '';

    protected string $routePrefix = '';

    protected string $pageTitle = '';

    protected string $pageDescription = '';

    protected string $pageIcon = '';

    protected string $modelClass = '';

    protected array $searchableFields = [];

    protected array $fillableFields = [];

    protected array $validationRules = [];

    protected bool $hasImportExport = false;

    protected string $exportType = 'master-data';

    public function __construct(AddonService $addonService)
    {
        $this->addonService = $addonService;
        $this->hasImportExport = $this->addonService->isAddonEnabled('DataImportExport');
        $this->initializeController();
    }

    /**
     * Initialize controller-specific settings
     */
    abstract protected function initializeController(): void;

    /**
     * Display a listing of the resource
     */
    public function index(): View
    {
        $data = [
            'pageTitle' => $this->pageTitle,
            'pageDescription' => $this->pageDescription,
            'pageIcon' => $this->pageIcon,
            'hasImportExport' => $this->hasImportExport,
            'exportType' => $this->exportType,
            'urls' => $this->getUrls(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'permissions' => $this->getPermissions(),
        ];

        return view($this->getViewName('index'), $data);
    }

    /**
     * Get data for DataTables
     */
    public function datatable(Request $request): JsonResponse
    {
        $model = new $this->modelClass;
        $query = $model->newQuery();

        // Apply any custom query modifications
        $query = $this->customizeQuery($query, $request);

        return DataTables::of($query)
            ->addColumn('actions', function ($record) {
                return $this->getActionButtons($record);
            })
            ->editColumn('created_at', function ($record) {
                return $record->created_at?->format('M d, Y H:i') ?? '-';
            })
            ->editColumn('updated_at', function ($record) {
                return $record->updated_at?->format('M d, Y H:i') ?? '-';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource
     */
    public function create(): View
    {
        $data = [
            'pageTitle' => __('Add New :item', ['item' => $this->getSingularTitle()]),
            'pageDescription' => $this->pageDescription,
            'pageIcon' => $this->pageIcon,
            'hasImportExport' => $this->hasImportExport,
            'exportType' => $this->exportType,
            'urls' => $this->getUrls(),
            'breadcrumbs' => $this->getBreadcrumbs('create'),
            'record' => new $this->modelClass,
            'formAction' => route($this->routePrefix.'.store'),
            'formMethod' => 'POST',
        ];

        return view($this->getViewName('form'), $data);
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->getValidationRules());

        $record = new $this->modelClass;
        $record->fill($this->prepareData($validated));
        $record->save();

        $message = __(':item created successfully', ['item' => $this->getSingularTitle()]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $record,
            ]);
        }

        return redirect()->route($this->routePrefix.'.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource
     */
    public function show($id): View
    {
        $record = $this->findRecord($id);

        $data = [
            'pageTitle' => __('View :item', ['item' => $this->getSingularTitle()]),
            'pageDescription' => $this->pageDescription,
            'pageIcon' => $this->pageIcon,
            'hasImportExport' => $this->hasImportExport,
            'exportType' => $this->exportType,
            'urls' => $this->getUrls(),
            'breadcrumbs' => $this->getBreadcrumbs('show'),
            'record' => $record,
        ];

        return view($this->getViewName('show'), $data);
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit($id): View
    {
        $record = $this->findRecord($id);

        $data = [
            'pageTitle' => __('Edit :item', ['item' => $this->getSingularTitle()]),
            'pageDescription' => $this->pageDescription,
            'pageIcon' => $this->pageIcon,
            'hasImportExport' => $this->hasImportExport,
            'exportType' => $this->exportType,
            'urls' => $this->getUrls(),
            'breadcrumbs' => $this->getBreadcrumbs('edit'),
            'record' => $record,
            'formAction' => route($this->routePrefix.'.update', $record->id),
            'formMethod' => 'PUT',
        ];

        return view($this->getViewName('form'), $data);
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $record = $this->findRecord($id);
        $validated = $request->validate($this->getValidationRules($record));

        $record->fill($this->prepareData($validated));
        $record->save();

        $message = __(':item updated successfully', ['item' => $this->getSingularTitle()]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $record,
            ]);
        }

        return redirect()->route($this->routePrefix.'.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy($id): JsonResponse|RedirectResponse
    {
        $record = $this->findRecord($id);
        $record->delete();

        $message = __(':item deleted successfully', ['item' => $this->getSingularTitle()]);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return redirect()->route($this->routePrefix.'.index')
            ->with('success', $message);
    }

    /**
     * Get action buttons for DataTable
     */
    protected function getActionButtons($record): string
    {
        $actions = [];

        if ($this->canView()) {
            $actions[] = [
                'label' => __('View'),
                'icon' => 'bx bx-show',
                'url' => route($this->routePrefix.'.show', $record->id),
                'class' => 'btn-outline-info',
            ];
        }

        if ($this->canEdit()) {
            $actions[] = [
                'label' => __('Edit'),
                'icon' => 'bx bx-edit',
                'url' => route($this->routePrefix.'.edit', $record->id),
                'class' => 'btn-outline-primary',
            ];
        }

        if ($this->canDelete()) {
            $actions[] = [
                'label' => __('Delete'),
                'icon' => 'bx bx-trash',
                'url' => route($this->routePrefix.'.destroy', $record->id),
                'class' => 'btn-outline-danger delete-record',
            ];
        }

        return view('components.datatable-actions', [
            'id' => $record->id,
            'actions' => $actions,
        ])->render();
    }

    /**
     * Customize query for DataTable
     */
    protected function customizeQuery($query, Request $request)
    {
        // Apply search if searchable fields are defined
        if (! empty($this->searchableFields) && $request->has('search') && $request->search['value']) {
            $searchTerm = $request->search['value'];
            $query->where(function ($q) use ($searchTerm) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }

    /**
     * Prepare data before saving
     */
    protected function prepareData(array $data): array
    {
        // Remove any fields that shouldn't be mass assigned
        return array_intersect_key($data, array_flip($this->fillableFields));
    }

    /**
     * Get validation rules
     */
    protected function getValidationRules($record = null): array
    {
        return $this->validationRules;
    }

    /**
     * Find record by ID
     */
    protected function findRecord($id)
    {
        $model = new $this->modelClass;

        return $model->findOrFail($id);
    }

    /**
     * Get view name
     */
    protected function getViewName(string $view): string
    {
        return $this->viewPrefix.'.'.$view;
    }

    /**
     * Get URLs for JavaScript
     */
    protected function getUrls(): array
    {
        return [
            'index' => route($this->routePrefix.'.index'),
            'create' => route($this->routePrefix.'.create'),
            'datatable' => route($this->routePrefix.'.datatable'),
            'store' => route($this->routePrefix.'.store'),
        ];
    }

    /**
     * Get breadcrumbs
     */
    protected function getBreadcrumbs(string $action = 'index'): array
    {
        $breadcrumbs = [
            ['title' => $this->pageTitle, 'url' => route($this->routePrefix.'.index')],
        ];

        switch ($action) {
            case 'create':
                $breadcrumbs[] = ['title' => __('Add New'), 'url' => ''];
                break;
            case 'edit':
                $breadcrumbs[] = ['title' => __('Edit'), 'url' => ''];
                break;
            case 'show':
                $breadcrumbs[] = ['title' => __('View'), 'url' => ''];
                break;
        }

        return $breadcrumbs;
    }

    /**
     * Get permissions
     */
    protected function getPermissions(): array
    {
        return [
            'can_view' => $this->canView(),
            'can_create' => $this->canCreate(),
            'can_edit' => $this->canEdit(),
            'can_delete' => $this->canDelete(),
        ];
    }

    /**
     * Permission checks (override in child classes)
     */
    protected function canView(): bool
    {
        return true;
    }

    protected function canCreate(): bool
    {
        return true;
    }

    protected function canEdit(): bool
    {
        return true;
    }

    protected function canDelete(): bool
    {
        return true;
    }

    /**
     * Get singular title for messages
     */
    protected function getSingularTitle(): string
    {
        return rtrim($this->pageTitle, 's');
    }
}
