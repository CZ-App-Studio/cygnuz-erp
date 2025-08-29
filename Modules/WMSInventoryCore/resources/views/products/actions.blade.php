<div class="d-inline-flex">
  <a href="{{ route('wmsinventorycore.products.show', $product->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
    <i class="bx bx-show"></i>
  </a>
  <a href="{{ route('wmsinventorycore.products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
    <i class="bx bx-edit"></i>
  </a>
  <button type="button" class="btn btn-sm btn-outline-danger delete-record" data-id="{{ $product->id }}" data-name="{{ $product->name }}" title="Delete">
    <i class="bx bx-trash"></i>
  </button>
</div>
