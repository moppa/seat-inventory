@extends('web::layouts.grids.12')

@section('title', "Stock Availability")
@section('page_header', "Stock Availability")


@section('full')
    @include("inventory::includes.status")

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter</h3>
        </div>
        <div class="card-body">

            <p class="alert alert-warning">
                This part of the plugin is no longer maintained and only here because the new dashboard isn't fully functional yet.
                Please start to use the new dashboard
            </p>

            <form action="{{ route("inventory.stockAvailability") }}" method="GET">

                <div class="form-group">
                    <label for="stock-location">Location</label>
                    <select
                            placeholder="enter the name of a location"
                            class="form-control basicAutoComplete"
                            autocomplete="off"
                            id="stock-location"
                            data-url="{{ route("inventory.legacyLocationSuggestions") }}"
                            name="location_id">
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @isset($stocks)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Stocks</h3>
            </div>
            <div class="card-body">
                <small class="text-muted">All stocks in {{ $location_id_text }}</small>
                @if($stocks->isEmpty())
                    <div class="alert alert-primary">
                        There are no stocks in {{ $location_id_text }}!
                    </div>
                @else
                    <div class="list-group mb-4 mt-2">
                        @foreach($stocks as $stock)
                            @include("inventory::includes.stocklink",["stock"=>$stock])
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Missing</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <p class="align-self-baseline">All items missing in {{ $location_id_text }}</p>
                    @include("inventory::includes.multibuy",["multibuy" => $missing_multibuy])
                </div>
                @if($stocks->isEmpty())
                    <p>
                        There are no missing items.
                    </p>
                @else
                    <div class="list-group mt-2">
                        @foreach($missing_items as $item)
                            <li class="list-group-item">
                                <img src="https://images.evetech.net/types/{{ $item->type_id }}/icon" height="24">
                                <span>
                                        {{ $item->amount }}x
                                        {{ $item->name() }}
                                </span>
                            </li>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    @endisset
@stop

@push('javascript')
    <script src="@inventoryVersionedAsset('inventory/js/bootstrap-autocomplete.js')"></script>

    <script>
        $('.basicAutoComplete').autoComplete({
            resolverSettings: {
                requestThrottling: 50
            },
            minLength: 0,
        });

        @if(isset($location_id)&&isset($location_id_text))
        $('#stock-location').autoComplete('set', {
            value: "{{ $location_id }}",
            text: "{{ $location_id_text }}"
        });
        @endif
    </script>
@endpush