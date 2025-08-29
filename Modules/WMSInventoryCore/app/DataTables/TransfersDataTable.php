<?php

namespace Modules\WMSInventoryCore\app\DataTables;

use Modules\WMSInventoryCore\app\Models\Transfer;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\Helpers\FormattingHelper;

class TransfersDataTable extends DataTable
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
            ->addColumn('actions', function ($transfer) {
                return view('wmsinventorycore::transfers.actions', compact('transfer'));
            })
            ->editColumn('date', function ($transfer) {
                return FormattingHelper::formatDate($transfer->date);
            })
            ->editColumn('shipping_cost', function ($transfer) {
                return FormattingHelper::formatCurrency($transfer->shipping_cost);
            })
            ->editColumn('status', function ($transfer) {
                return view('wmsinventorycore::transfers.status', compact('transfer'));
            })
            ->rawColumns(['actions', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \Modules\WMSInventoryCore\app\Models\Transfer $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Transfer $model)
    {
        return $model->newQuery()
            ->with(['sourceWarehouse', 'destinationWarehouse']);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('transfers-table')
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
            Column::make('id'),
            Column::make('date'),
            Column::make('code'),
            Column::make('reference_no')->title('Reference'),
            Column::make('source_warehouse.name')->title('From'),
            Column::make('destination_warehouse.name')->title('To'),
            Column::make('shipping_cost'),
            Column::computed('status'),
            Column::computed('actions')
                ->exportable(false)
                ->printable(false)
                ->width(60)
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
        return 'Transfers_' . date('YmdHis');
    }
}
