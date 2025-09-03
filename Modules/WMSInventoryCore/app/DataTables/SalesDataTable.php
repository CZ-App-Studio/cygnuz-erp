<?php

namespace Modules\WMSInventoryCore\app\DataTables;

use App\Helpers\FormattingHelper;
use Modules\WMSInventoryCore\Models\Sale;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('actions', function ($sale) {
                $showUrl = route('wmsinventorycore.sales.show', $sale->id);
                $editUrl = route('wmsinventorycore.sales.edit', $sale->id);

                $actions = '<div class="d-inline-block">';
                $actions .= '<a href="'.$showUrl.'" class="btn btn-sm btn-icon" title="'.__('View Details').'">';
                $actions .= '<i class="ti ti-eye"></i>';
                $actions .= '</a>';

                if ($sale->status === 'pending' || $sale->status === 'draft') {
                    $actions .= '<a href="'.$editUrl.'" class="btn btn-sm btn-icon" title="'.__('Edit').'">';
                    $actions .= '<i class="ti ti-pencil"></i>';
                    $actions .= '</a>';
                }

                if ($sale->status === 'pending') {
                    $actions .= '<button class="btn btn-sm btn-icon confirm-record" data-id="'.$sale->id.'" title="'.__('Confirm').'">';
                    $actions .= '<i class="ti ti-check"></i>';
                    $actions .= '</button>';
                }

                if ($sale->status === 'draft' || $sale->status === 'pending') {
                    $actions .= '<button class="btn btn-sm btn-icon delete-record" data-id="'.$sale->id.'" title="'.__('Delete').'">';
                    $actions .= '<i class="ti ti-trash"></i>';
                    $actions .= '</button>';
                }

                $actions .= '</div>';

                return $actions;
            })
            ->addColumn('customer', function ($sale) {
                return $sale->customer ? $sale->customer->name : '-';
            })
            ->addColumn('warehouse', function ($sale) {
                return $sale->warehouse ? $sale->warehouse->name : '-';
            })
            ->editColumn('date', function ($sale) {
                return FormattingHelper::formatDate($sale->date);
            })
            ->editColumn('total_amount', function ($sale) {
                return FormattingHelper::formatCurrency($sale->total_amount);
            })
            ->editColumn('status', function ($sale) {
                $statusColors = [
                    'draft' => 'secondary',
                    'pending' => 'warning',
                    'confirmed' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ];
                $statusColor = $statusColors[$sale->status] ?? 'secondary';

                return '<span class="badge bg-label-'.$statusColor.'">'.strtoupper($sale->status).'</span>';
            })
            ->editColumn('payment_status', function ($sale) {
                $statusColors = [
                    'unpaid' => 'danger',
                    'partially_paid' => 'warning',
                    'paid' => 'success',
                    'overdue' => 'dark',
                ];
                $statusColor = $statusColors[$sale->payment_status] ?? 'secondary';

                return '<span class="badge bg-label-'.$statusColor.'">'.strtoupper(str_replace('_', ' ', $sale->payment_status)).'</span>';
            })
            ->editColumn('fulfillment_status', function ($sale) {
                $statusColors = [
                    'pending' => 'warning',
                    'processing' => 'info',
                    'shipped' => 'primary',
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                ];
                $statusColor = $statusColors[$sale->fulfillment_status] ?? 'secondary';

                return '<span class="badge bg-label-'.$statusColor.'">'.strtoupper(str_replace('_', ' ', $sale->fulfillment_status)).'</span>';
            })
            ->rawColumns(['actions', 'status', 'payment_status', 'fulfillment_status'])
            ->filter(function ($query) {
                if (request()->has('customer_id') && request('customer_id') != '') {
                    $query->where('customer_id', request('customer_id'));
                }

                if (request()->has('warehouse_id') && request('warehouse_id') != '') {
                    $query->where('warehouse_id', request('warehouse_id'));
                }

                if (request()->has('status') && request('status') != '') {
                    $query->where('status', request('status'));
                }

                if (request()->has('payment_status') && request('payment_status') != '') {
                    $query->where('payment_status', request('payment_status'));
                }

                if (request()->has('fulfillment_status') && request('fulfillment_status') != '') {
                    $query->where('fulfillment_status', request('fulfillment_status'));
                }

                if (request()->has('date_from') && request('date_from') != '') {
                    $query->whereDate('date', '>=', request('date_from'));
                }

                if (request()->has('date_to') && request('date_to') != '') {
                    $query->whereDate('date', '<=', request('date_to'));
                }

                if (request()->has('sales_person_id') && request('sales_person_id') != '') {
                    $query->where('sales_person_id', request('sales_person_id'));
                }
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Sale $model)
    {
        return $model->newQuery()
            ->with(['customer', 'warehouse', 'salesPerson']);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('sales-table')
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
                'autoWidth' => false,
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
            Column::make('customer')->title(__('Customer'))->orderable(false),
            Column::make('warehouse')->title(__('Warehouse'))->orderable(false),
            Column::make('total_amount')->title(__('Total Amount')),
            Column::computed('status')->title(__('Status')),
            Column::computed('payment_status')->title(__('Payment')),
            Column::computed('fulfillment_status')->title(__('Fulfillment')),
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
     */
    protected function filename(): string
    {
        return 'Sales_'.date('YmdHis');
    }
}
