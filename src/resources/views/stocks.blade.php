@extends('web::layouts.grids.12')

@section('title', "Title")
@section('page_header', "Title")


@section('full')
    @include("inventory::includes.status")

    <div class="card">
        <div class="card-body">
            <h1>
                Inventory Tracking
            </h1>

            <h2>
                Stocks
            </h2>

            @if($fittings->isEmpty())
                <div class="alert alert-primary">
                    You haven't added any stocks to monitor yet.
                </div>
            @else
                <div class="list-group">
                    @foreach($fittings as $stock)
                        <a href="{{ route("inventory.editStock",$stock->id) }}" class="list-group-item list-group-item-action">
                            <b>{{ $stock->name }}</b>
                            {{ $stock->location->name }}
                            @if($stock->fitting_plugin_fitting_id != null)
                                <span class="badge badge-primary">Fitting Plugin</span>
                            @endif
                            @include("inventory::includes.priority",["priority"=>$stock->priority])
                        </a>
                    @endforeach
                </div>
            @endif

            <h2 class="mt-4">Add Fit</h2>

            <ul class="nav nav-tabs" id="fitTypeTab" data-tabs="tabs">
                <li class="nav-item">
                    <button class="nav-link active" data-toggle="tab" href="#fit-text-tab-content" type="button">Fits
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-toggle="tab" href="#multibuy-text-tab-content" type="button">
                        Multibuy
                    </button>
                </li>
                @if($has_fitting_plugin)
                    <li class="nav-item">
                        <button class="nav-link" id="fit-plugin-tab" data-toggle="tab" href="#fit-plugin-tab-content"
                                type="button">Fitting Plugin
                        </button>
                    </li>
                @endif
            </ul>

            <div class="tab-content mt-4" id="fitTypeTabContent">

                {{-- EFT Fits --}}
                <div class="tab-pane show active" id="fit-text-tab-content">
                    <form action="{{ route("inventory.addStock") }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="fit-text">Fit</label>
                            <textarea id="fit-text" class="form-control monospace-font text-sm" rows="10"
                                      name="fit_text"
                                      placeholder="{{ "[Pacifier, 2022 Scanner]\n\nCo-Processor II\nCo-Processor II\nType-D Restrained Inertial Stabilizers\nInertial Stabilizers II" }}"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="fit-amount">Amount</label>
                            <input type="number" id="fit-amount" class="form-control" name="amount" value="1">
                        </div>

                        <div class="form-group">
                            <label for="fit-location">Location</label>
                            <select
                                    placeholder="enter the name of a location"
                                    class="form-control basicAutoComplete" type="text"
                                    autocomplete="off"
                                    id="fit-location"
                                    data-url="{{ route("inventory.locationSuggestions") }}"
                                    name="location_id">
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fit_priority">Priority</label>
                            <select name="priority" id="fit_priority" class="form-control">
                                <option value="0">Very Low</option>
                                <option value="1">Low</option>
                                <option value="2" selected>Normal</option>
                                <option value="3">Preferred</option>
                                <option value="4">Important</option>
                                <option value="5">Critical</option>
                            </select>
                        </div>

                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="fit_check-corporation-hangars"
                                class="form-check-input"
                                name="check_corporation_hangars"
                                checked>
                            <label for="fit_check-corporation-hangars">Check in corporation hangars</label>
                        </div>

                        <div class="form-check">
                            <input
                                    type="checkbox"
                                    id="fit_check-contracts"
                                    class="form-check-input"
                                    name="check_contracts"
                                    checked>
                            <label for="fit_check-contracts">Check contracts</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>

                    </form>
                </div>

                {{-- Multibuy --}}
                <div class="tab-pane" id="multibuy-text-tab-content">
                    <form action="{{ route("inventory.addStock") }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="stock-name">Name</label>
                            <input type="text" id="stock-name" class="form-control" name="name"
                                   placeholder="Enter a name...">
                        </div>

                        <div class="form-group">
                            <label for="multibuy-text">Multibuy</label>
                            <textarea id="multibuy-text" class="form-control monospace-font text-sm" rows="10"
                                      name="multibuy_text" placeholder=""></textarea>
                        </div>

                        <div class="form-group">
                            <label for="stock-amount">Amount</label>
                            <input type="number" id="stock-amount" class="form-control" name="amount" value="1">
                        </div>

                        <div class="form-group">
                            <label for="fit-location">Location</label>
                            <select
                                    placeholder="enter the name of a location"
                                    class="form-control basicAutoComplete" type="text"
                                    autocomplete="off"
                                    id="fit-location"
                                    data-url="{{ route("inventory.locationSuggestions") }}"
                                    name="location_id">
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="multibuy_priority">Priority</label>
                            <select name="priority" id="multibuy_priority" class="form-control">
                                <option value="0">Very Low</option>
                                <option value="1">Low</option>
                                <option value="2" selected>Normal</option>
                                <option value="3">Preferred</option>
                                <option value="4">Important</option>
                                <option value="5">Critical</option>
                            </select>
                        </div>

                        <div class="form-check">
                            <input
                                    type="checkbox"
                                    id="multibuy_check-corporation-hangars"
                                    class="form-check-input"
                                    name="check_corporation_hangars"
                                    checked>
                            <label for="multibuy_check-corporation-hangars">Check in corporation hangars</label>
                        </div>

                        <div class="form-check">
                            <input
                                    type="checkbox"
                                    id="multibuy_check-contracts"
                                    class="form-check-input"
                                    name="check_contracts"
                                    checked>
                            <label for="multibuy_check-contracts">Check contracts</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>

                    </form>
                </div>

                {{-- Plugin --}}
                @if($has_fitting_plugin)
                    <div class="tab-pane" id="fit-plugin-tab-content">
                        <form action="{{ route("inventory.addStock") }}" method="POST">
                            @csrf

                            <div class="form-group">
                                <label for="fit-plugin-fit">Fit</label>
                                <select
                                        placeholder="enter the name of the fit"
                                        class="form-control basicAutoComplete" type="text"
                                        autocomplete="off"
                                        id="fit-plugin-fit"
                                        data-url="{{ route("inventory.fittingPluginFittingsSuggestions") }}"
                                        name="fit_plugin_id">
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fit-amount">Amount</label>
                                <input type="number" id="fit-amount" class="form-control" name="amount" value="1">
                            </div>

                            <div class="form-group">
                                <label for="fit-location">Location</label>
                                <select
                                        placeholder="enter the name of a location"
                                        class="form-control basicAutoComplete" type="text"
                                        autocomplete="off"
                                        id="fit-location"
                                        data-url="{{ route("inventory.locationSuggestions") }}"
                                        name="location_id">
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="plugin_priority">Priority</label>
                                <select name="priority" id="plugin_priority" class="form-control">
                                    <option value="0">Very Low</option>
                                    <option value="1">Low</option>
                                    <option value="2" selected>Normal</option>
                                    <option value="3">Preferred</option>
                                    <option value="4">Important</option>
                                    <option value="5">Critical</option>
                                </select>
                            </div>

                            <div class="form-check">
                                <input
                                        type="checkbox"
                                        id="fitting_plugin_check-corporation-hangars"
                                        class="form-check-input"
                                        name="check_corporation_hangars"
                                        checked>
                                <label for="fitting_plugin_check-corporation-hangars">Check in corporation hangars</label>
                            </div>

                            <div class="form-check">
                                <input
                                        type="checkbox"
                                        id="fitting_plugin_check-contracts"
                                        class="form-check-input"
                                        name="check_contracts"
                                        checked>
                                <label for="fitting_plugin_check-contracts">Check contracts</label>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit</button>

                        </form>
                    </div>
                @endif


            </div>
        </div>
    </div>
@stop

@push('javascript')
    <script src="@inventoryVersionedAsset('inventory/js/bootstrap-autocomplete.js')"></script>

    <script>
        $('.basicAutoComplete').autoComplete({
            resolverSettings: {
                requestThrottling: 250
            },
            minLength: 0,
        });
    </script>
@endpush