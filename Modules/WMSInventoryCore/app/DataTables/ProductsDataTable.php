<?php

namespace Modules\WMSInventoryCore\app\DataTables;

use Modules\WMSInventoryCore\app\Models\Product;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\Helpers\FormattingHelper;

class ProductsDataTable extends DataTable
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
            ->addColumn('actions', function ($product) {
                return view('wmsinventorycore::products.actions', compact('product'));
            })
            ->editColumn('cost_price', function ($product) {
                return FormattingHelper::formatCurrency($product->cost_price);
            })
            ->editColumn('selling_price', function ($product) {
                return FormattingHelper::formatCurrency($product->selling_price);
            })
            ->editColumn('stock', function ($product) {
                return $product->inventory->sum('stock_level');
            })
            ->rawColumns(['actions']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \Modules\WMSInventoryCore\app\Models\Product $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Product $model)
    {
        return $model->newQuery()
            ->with(['category', 'unit', 'inventory']);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('products-table')
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
            Column::make('name'),
            Column::make('sku'),
            Column::make('barcode'),
            Column::make('category.name')->title('Category'),
            Column::make('unit.name')->title('Unit'),
            Column::computed('stock')->title('Stock'),
            Column::make('cost_price'),
            Column::make('selling_price'),
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
        return 'Products_' . date('YmdHis');
    }
}
