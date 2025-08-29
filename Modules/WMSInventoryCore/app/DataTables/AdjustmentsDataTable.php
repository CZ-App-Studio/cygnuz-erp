<?php

namespace Modules\WMSInventoryCore\app\DataTables;

use Modules\WMSInventoryCore\app\Models\Adjustment;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\Helpers\FormattingHelper;

class AdjustmentsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('actions', function ($adjustment) {
                $showUrl = route('wmsinventorycore.adjustments.show', $adjustment->id);
                $editUrl = route('wmsinventorycore.adjustments.edit', $adjustment->id);

                $actions = '<div class="d-inline-block">';
                $actions .= '<a href="' . $showUrl . '" class="btn btn-sm btn-icon" title="' . __('View Details') . '">';
                $actions .= '<i class="ti ti-eye"></i>';
                $actions .= '</a>';

                if ($adjustment->status === 'pending') {
                    $actions .= '<a href="' . $editUrl . '" class="btn btn-sm btn-icon" title="' . __('Edit') . '">';
                    $actions .= '<i class="ti ti-pencil"></i>';
                    $actions .= '</a>';
                    $actions .= '<button class="btn btn-sm btn-icon approve-record" data-id="' . $adjustment->id . '" title="' . __('Approve') . '">';
                    $actions .= '<i class="ti ti-check"></i>';
                    $actions .= '</button>';
                    $actions .= '<button class="btn btn-sm btn-icon delete-record" data-id="' . $adjustment->id . '" title="' . __('Delete') . '">';
                    $actions .= '<i class="ti ti-trash"></i>';
                    $actions .= '</button>';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->addColumn('warehouse', function ($adjustment) {
                return $adjustment->warehouse ? $adjustment->warehouse->name : '-';
            })
            ->addColumn('adjustment_type', function ($adjustment) {
                return $adjustment->adjustmentType ? $adjustment->adjustmentType->name : '-';
            })
            ->editColumn('date', function ($adjustment) {
                return FormattingHelper::formatDate($adjustment->date);
            })
            ->editColumn('total_amount', function ($adjustment) {
                return FormattingHelper::formatCurrency($adjustment->total_amount);
            })
            ->editColumn('status', function ($adjustment) {
                $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'cancelled' => 'danger'
                ];
                $statusColor = $statusColors[$adjustment->status] ?? 'secondary';

                return '<span class="badge bg-label-' . $statusColor . '">' . strtoupper($adjustment->status) . '</span>';
            })
            ->rawColumns(['actions', 'status'])
            ->filter(function ($query) {
                if (request()->has('warehouse_id') && request('warehouse_id') != '') {
                    $query->where('warehouse_id', request('warehouse_id'));
                }

                if (request()->has('adjustment_type_id') && request('adjustment_type_id') != '') {
                    $query->where('adjustment_type_id', request('adjustment_type_id'));
                }

                if (request()->has('status') && request('status') != '') {
                    $query->where('status', request('status'));
                }

                if (request()->has('date_from') && request('date_from') != '') {
                    $query->whereDate('date', '>=', request('date_from'));
                }

                if (request()->has('date_to') && request('date_to') != '') {
                    $query->whereDate('date', '<=', request('date_to'));
                }
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @param \Modules\WMSInventoryCore\app\Models\Adjustment $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Adjustment $model)
    {
        return $model->newQuery()
            ->with(['warehouse', 'adjustmentType']);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('adjustments-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(0, 'desc')
            ->buttons(
                Button::make('export'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            )
            ->parameters([
                'responsive' => true,
                'autoWidth' => false
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('id')->title(__('ID')),
            Column::make('date')->title(__('Date')),
            Column::make('code')->title(__('Code')),
            Column::make('warehouse')->title(__('Warehouse'))->orderable(false),
            Column::make('adjustment_type')->title(__('Type'))->orderable(false),
            Column::make('total_amount')->title(__('Total')),
            Column::computed('status')->title(__('Status')),
            Column::computed('actions')
                ->title(__('Actions'))
                ->exportable(false)
                ->printable(false)
                ->width(120)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Adjustments_' . date('YmdHis');
    }
}
