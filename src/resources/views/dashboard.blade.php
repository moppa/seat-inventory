@extends('web::layouts.app')

@section('title', "Inventory Dashboard")
@section('page_header', "Inventory Dashboard")


@section('content')
    <div id="content-target"></div>
@stop

@push("javascript")
    <script src="@inventoryVersionedAsset('inventory/js/w2.js')"></script>
    <script src="@inventoryVersionedAsset('inventory/js/select2w2.js')"></script>
    <script src="@inventoryVersionedAsset('inventory/js/bootstrapW2.js')"></script>


    <script>

        //TODO: load them from the server
        async function getStockPriorities() {
            return [
                {
                    priority: 5,
                    name: "Critical"
                },
                {
                    priority: 4,
                    name: "Important"
                },
                {
                    priority: 3,
                    name: "Preferred"
                },
                {
                    priority: 2,
                    name: "Normal"
                },
                {
                    priority: 1,
                    name: "Low"
                },
                {
                    priority: 0,
                    name: "Very Low"
                },
            ]
        }


        async function jsonPostAction(url, data) {
            return await fetch(url, {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data),
            })
        }

        async function jsonGetAction(url, data) {
            url = new URL(url)
            //stackoverflow many thanks
            Object.keys(data).forEach(key => url.searchParams.append(key, data[key]))
            return await fetch(url, {
                method: "GET",
            })
        }


        class LocationFilterComponent extends W2.W2Component {
            locationListeners
            id

            constructor(options) {
                super();

                this.id = W2.getID("locationFilterSelect", true)

                this.locationListeners = options.locationListeners || []
            }

            locationSelected(selection) {
                let locationID = null
                if (selection) {
                    locationID = selection.id
                }

                for (const locationListener of this.locationListeners) {
                    locationListener(locationID)
                }
            }

            render(container) {
                const card = W2
                    .html("div")
                    .class("card")
                    .content(
                        W2.html("div")
                            .class("card-body")
                            .content(
                                W2.html("label")
                                    .attribute("for", this.id)
                                    .content("Location")
                            )
                            .content(
                                select2Component({
                                    select2: {
                                        placeholder: "All locations",
                                        ajax: {
                                            url: "{{ route("inventory.locationLookup") }}"
                                        },
                                        allowClear: true,
                                    },
                                    id: this.id,
                                    selectionListeners: [
                                        (...args) => this.locationSelected(...args)
                                    ]
                                })
                            )
                            .content(
                                W2.html("small")
                                    .class("text-muted")
                                    .content("Only show categories containing stocks at a specific location.")
                            )
                    )

                container.content(card)
            }
        }

        function confirmButtonComponent(text, callback) {
            const state = {
                firstStep: true
            }
            return W2.mount(state, (container, mount, state) => {
                if (state.firstStep) {
                    container.content(
                        W2.html("button")
                            .class("btn btn-danger")
                            .content(text)
                            .event("click", () => {
                                state.firstStep = false
                                mount.update()
                            })
                    )
                } else {
                    container.content(
                        W2.html("div")
                            .class("btn-group")
                            .content(
                                W2.html("button")
                                    .class("btn btn-primary")
                                    .content("Cancel")
                                    .event("click", () => {
                                        state.firstStep = true
                                        mount.update()
                                    })
                            )
                            .content(
                                W2.html("button")
                                    .class("btn btn-warning")
                                    .content("Confirm")
                                    .event("click", () => {
                                        callback()
                                        state.firstStep = true
                                        mount.update()
                                    })
                            )
                    )
                }
            })
        }

        async function lookupName(id,url) {
            const response = await jsonGetAction(url,{
                id
            })

            if(!response.ok){
                throw new Error("Server responded with an error!")
            }

            const data = await response.json()

            if(data.results.length < 1){
                throw new Error("Couldn't find the requested doctrine!")
            }

            return data.results[0].text
        }

        function editCategoryPopUp(app, category) {
            BootstrapPopUp.open(category.id ? "Edit Category" : "Create Category", (container, popup) => {
                let filters = category.filters || ""
                try {
                    filters = JSON.parse(filters)
                } catch (e) {
                    filters = []
                    BoostrapToast.open("Category","Failed to parse complete category data")
                }
                const filterLocations = []
                const filterDoctrines = []
                for (const filter of filters) {
                    if(filter.type === "location"){
                        const data = {
                            id: filter.id,
                            text: "Loading name..."
                        }

                        filterLocations.push(data)

                        lookupName(filter.id,"{{ route("inventory.locationLookup") }}").then((name)=>{
                            data.text = name
                        }).catch((e)=>{
                            data.text = "Failed to load name"
                        })
                    } else if (filter.type === "doctrine"){
                        const data = {
                            id: filter.id,
                            text: "Loading name..."
                        }

                        filterDoctrines.push(data)

                        lookupName(filter.id,"{{ route("inventory.doctrineLookup") }}").then((name)=>{
                            data.text = name
                        }).catch((e)=>{
                            data.text = "Failed to load name"
                        })
                    }
                }


                const state = {
                    name: category.name || "",
                    message: null,
                    stocks: category.stocks.map((stock)=>{
                        stock.manually_added = stock.pivot.manually_added
                        stock.category_eligible = stock.pivot.category_eligible
                        return stock
                    }) || [],
                    stocksExpanded: false,
                    filtersExpanded: false,
                    generalExpanded: true,
                    filterLocations: filterLocations,
                    filterDoctrines: filterDoctrines,
                }

                const mount = W2.mount(state, (container, mount, state) => {
                    container
                        .content(

                            //general settings
                            W2.html("div")
                                .class("card")
                                .content(
                                    W2.html("div")
                                        .class("card-body")
                                        .content(
                                            //head with expand/collapse
                                            W2.html("div")
                                                .class("d-flex align-items-baseline")
                                                .content(
                                                    W2.html("h6")
                                                        .content("General"),
                                                    W2.html("button")
                                                        .class("btn btn-primary ml-auto")
                                                        .contentIf(state.generalExpanded,"Collapse")
                                                        .contentIf(!state.generalExpanded,"Expand")
                                                        .event("click",()=>{
                                                            state.generalExpanded = !state.generalExpanded
                                                            mount.update()
                                                        })
                                                )
                                        )
                                        //actual general settings
                                        .contentIf(state.generalExpanded,
                                            W2.html("label")
                                                .attribute("for", W2.getID("editCategoryNameLabel", true))
                                                .content("Category Name"),
                                            W2.html("input")
                                                .class("form-control")
                                                .attribute("type", "text")
                                                .id(W2.getID("editCategoryNameLabel"))
                                                .attribute("placeholder", "Enter the category name")
                                                .attribute("value", state.name)
                                                .event("change", (e) => {
                                                    state.name = e.target.value
                                                }),
                                            (container) => {
                                                if (state.message) {
                                                    container.content(
                                                        W2.html("small").class("text-danger").content(state.message)
                                                    )
                                                }
                                            }
                                        )
                                )
                        )

                    //stock list+manual addition
                    container.content(
                        W2.html("div")
                            .class("card")
                            .content(
                                W2.html("div")
                                    .class("card-body")
                                    .content(
                                        W2.html("div")
                                            .class("d-flex align-items-baseline")
                                            .content(
                                                W2.html("h6")
                                                    .content("Stocks"),
                                                W2.html("button")
                                                    .class("btn btn-primary ml-auto")
                                                    .contentIf(state.stocksExpanded,"Collapse")
                                                    .contentIf(!state.stocksExpanded,"Expand")
                                                    .event("click",()=>{
                                                        state.stocksExpanded = !state.stocksExpanded
                                                        mount.update()
                                                    })
                                            ),
                                    )
                                    //stocks when expanded
                                    .contentIf(state.stocksExpanded,
                                        //only add margin if expanded, use a dummy for this
                                        W2.html("div").class("mt-2"),
                                        //manual addition select2
                                        select2Component({
                                            select2: {
                                                placeholder: "Manually add stock",
                                                ajax: {
                                                    url: "{{ route("inventory.stockSuggestion") }}",
                                                    data: function (params) {
                                                        return {
                                                            term: params.term,
                                                        }
                                                    },
                                                    processResults: (data) => {
                                                        return {
                                                            results: data.results.filter((data) => {

                                                                const includedIDs = state.stocks
                                                                    //remove automatically added stock so that they still can be added
                                                                    .filter((entry)=>entry.manually_added)
                                                                    //only get the id
                                                                    .map((entry) => entry.id)

                                                                //not in manually added stocks
                                                                return !includedIDs.includes(data.id.id)
                                                            })
                                                        }
                                                    }
                                                },
                                                allowClear: true,
                                            },
                                            selectionListeners: [
                                                (data) => {
                                                    const stock = data.id

                                                    //if it is an automated stock that being added, we have to change instead of add it
                                                    const existingStocks = state.stocks.filter((entry)=>entry.id === stock.id)
                                                    if(existingStocks.length > 0){
                                                        //it's a automated stock, switch it to a manual one
                                                        for (const existingStock of existingStocks) {
                                                            existingStock.manually_added = true
                                                        }
                                                    } else {
                                                        //its a new stock

                                                        //the api doesn't include the data from the pivots, add them
                                                        stock.manually_added = true
                                                        stock.category_eligible = false

                                                        state.stocks.push(stock)
                                                    }

                                                    mount.update()
                                                }
                                            ]
                                        }),
                                        //stock list
                                        (container) => {
                                                if (state.stocks.length > 0) {
                                                    container.content(
                                                        W2.html("ul")
                                                            .class("list-group list-group-flush mt-2")
                                                            .content((container) => {
                                                                for (const stock of state.stocks) {
                                                                    container.content(
                                                                        W2.html("li")
                                                                            .class("list-group-item d-flex align-items-baseline justify-content-between")
                                                                            .style("padding-right", "0")
                                                                            .content(stock.name)

                                                                            //remove button
                                                                            .contentIf(stock.manually_added,
                                                                                W2.html("button")
                                                                                    .class("btn btn-outline-danger")
                                                                                    .content("Remove")
                                                                                    .event("click", () => {
                                                                                        //TODO reset to automtic
                                                                                        state.stocks = state.stocks.filter((e)=>{
                                                                                            //find the current stock
                                                                                            if(e.id === stock.id){
                                                                                                //if it was originally automatic, set it back to automatic
                                                                                                if(e.category_eligible){
                                                                                                    e.manually_added = false
                                                                                                } else {
                                                                                                    //originally manual, remove it form the list
                                                                                                    return false
                                                                                                }
                                                                                            }
                                                                                            return true
                                                                                        })
                                                                                        mount.update()
                                                                                    })
                                                                            )

                                                                            //automated message
                                                                            .contentIf(!stock.manually_added,
                                                                                W2.html("button")
                                                                                    .class("btn btn-outline-secondary")
                                                                                    .content("Make Permanent")
                                                                                    .event("click",()=>{
                                                                                        stock.manually_added = true
                                                                                        mount.update()
                                                                                    })
                                                                            )
                                                                    )
                                                                }
                                                            })
                                                    )
                                                } else {
                                                    container.content(
                                                        W2.html("p")
                                                            .class("mt-3")
                                                            .content("You haven't added any stock to this category or the filters didn't get applied yet")
                                                    )
                                                }
                                            }
                                    )
                            )
                    )

                    //filters
                    container.content(
                        W2.html("div")
                            .class("card")
                            .content(
                                W2.html("div")
                                    .class("card-body")
                                    .content(
                                        W2.html("div")
                                            .class("d-flex align-items-baseline")
                                            .content(
                                                W2.html("h6")
                                                    .content("Filters"),
                                                W2.html("button")
                                                    .class("btn btn-primary ml-auto")
                                                    .contentIf(state.filtersExpanded,"Collapse")
                                                    .contentIf(!state.filtersExpanded,"Expand")
                                                    .event("click",()=>{
                                                        state.filtersExpanded = !state.filtersExpanded
                                                        mount.update()
                                                    })
                                            )
                                    )
                                    .contentIf(state.filtersExpanded,
                                        //location filter
                                        W2.html("label")
                                            .content("Locations"),
                                        select2Component({
                                            select2: {
                                                placeholder: "Select Locations",
                                                ajax: {
                                                    url: "{{ route("inventory.locationLookup") }}"
                                                },
                                                dropdownParent: popup.jQuery,
                                                multiple: true,
                                                allowClear: true,
                                            },
                                            selectionListeners: [
                                                (selection) => {
                                                    if(selection) {
                                                        const data = {
                                                            id: selection.id,
                                                            text: selection.text
                                                        }
                                                        state.filterLocations.push(data)
                                                    } else {
                                                        state.filterLocations = []
                                                    }

                                                    mount.update()
                                                }
                                            ],
                                            unselectListeners: [
                                                (selection)=>{
                                                    const id = parseInt(selection.id)
                                                    state.filterLocations = state.filterLocations.filter((e)=>e.id!==id)
                                                    mount.update()
                                                }
                                            ],
                                            selection: state.filterLocations
                                        }),
                                        //doctrine filter
                                        W2.html("label")
                                            .content("Doctrines (from seat-fitting)")
                                            .class("mt-2"),
                                        select2Component({
                                            select2: {
                                                placeholder: "Select Doctrines",
                                                ajax: {
                                                    url: "{{ route("inventory.doctrineLookup") }}"
                                                },
                                                dropdownParent: popup.jQuery,
                                                multiple: true,
                                                allowClear: true,
                                            },
                                            selectionListeners: [
                                                (selection) => {
                                                    if(selection) {
                                                        const data = {
                                                            id: selection.id,
                                                            text: selection.text
                                                        }
                                                        state.filterDoctrines.push(data)
                                                    } else {
                                                        state.filterDoctrines = []
                                                    }

                                                    mount.update()
                                                }
                                            ],
                                            unselectListeners: [
                                                (selection)=>{
                                                    const id = parseInt(selection.id)
                                                    state.filterDoctrines = state.filterDoctrines.filter((e)=>e.id!==id)
                                                    mount.update()
                                                }
                                            ],
                                            selection: state.filterDoctrines
                                        })
                                    )
                            )
                    )


                        //button bar at the bottom
                    container.content(
                        W2.html("div")
                            .class("d-flex flex-row")
                            .content(
                                (container) => {
                                    //delete button
                                    if (category.id) {
                                        container.content(
                                            confirmButtonComponent("Delete", async () => {
                                                popup.close()

                                                const response = await jsonPostAction("{{ route("inventory.deleteCategory") }}", {
                                                    id: category.id
                                                })

                                                if (!response.ok) {
                                                    BoostrapToast.open("Category", "Failed to delete the category")
                                                } else {
                                                    BoostrapToast.open("Category", "Successfully deleted the category")
                                                }

                                                app.categoryList.state.loadData()
                                            })
                                        )
                                    }
                                }
                            )
                            .content(
                                //close button
                                W2.html("button")
                                    .class("btn btn-secondary mr-1 ml-auto")
                                    .content("Close")
                                    .event("click", () => popup.close())
                            )
                            .content(
                                //save button
                                W2.html("button")
                                    .class("btn btn-primary")
                                    .content("Save")
                                    .event("click", async () => {
                                        if (state.name && state.name.length > 0) {
                                            //name field is not empty, save the category

                                            popup.close()

                                            //filters
                                            const filters = []
                                            //location filter
                                            for (const location of state.filterLocations) {
                                                filters.push({
                                                    type: "location",
                                                    id: location.id
                                                })
                                            }
                                            //doctrine filter
                                            for (const doctrine of state.filterDoctrines) {
                                                filters.push({
                                                    type: "doctrine",
                                                    id: doctrine.id
                                                })
                                            }

                                            const data = {
                                                id: category.id,
                                                name: state.name,
                                                stocks: state.stocks.map((e) => {
                                                    return {
                                                        id: e.id,
                                                        manually_added: e.manually_added
                                                    }
                                                }),
                                                filters: filters,
                                            }

                                            const response = await jsonPostAction("{{ route("inventory.saveCategory") }}", data)

                                            if (!response.ok) {
                                                BoostrapToast.open("Category", "Failed to save the category")
                                            } else {
                                                BoostrapToast.open("Category", "Successfully saved category")
                                            }

                                            app.categoryList.state.loadData()

                                        } else {
                                            //name field is empty
                                            state.message = "Please enter a valid name!"
                                            mount.update()
                                        }
                                    })
                            )
                    )
                })

                container.content(mount)
            })
        }

        function stockCardPropertyEntry(name, value, style = null) {
            let effectiveText = value
            let addToolTip = false
            if (effectiveText.length >= 20) {
                effectiveText = effectiveText.substring(0, 20)
                addToolTip = true
            }

            return W2.html("li")
                .class("list-group-item")
                .classIf(style, `list-group-item-${style}`)
                .content(name)
                .content(
                    tooltipComponent(
                        W2.html("b")
                            .class("float-right")
                            .content(effectiveText),
                        addToolTip ? value : ""
                    )
                )
        }

        function stockCardComponent(app, stock) {
            const available = stock.available_on_contracts + stock.available_in_hangars

            let availabilityColor = null
            if (available === 0) {
                availabilityColor = "danger"
            } else if (available < stock.warning_threshold) {
                availabilityColor = "warning"
            }

            return W2.html("div")
                .class("card m-1")
                .style("width", "16rem")
                .content(
                    //card header
                    W2.html("div")
                        .class("card-header d-flex align-items-baseline")
                        .style("padding-right", "0.75rem")
                        .content(
                            W2.html("h5")
                                .class("card-title mr-auto")
                                .content(
                                    W2.html("a")
                                        .attribute("href", `/inventory/stocks/view/${stock.id}`)
                                        .content(stock.name)
                                )
                        )
                        .content(
                            W2.html("i")
                                .class("fas fa-pen text-primary")
                                .style("cursor","pointer")
                                .event("click",()=>{
                                    editStockPopUp(app,stock)
                                })
                        )
                )
                //card body
                .content(
                    W2.html("img")
                        .attribute("src", `/inventory/stocks/icon/${stock.id}`)
                        .attribute("loading", "lazy")
                        .attribute("alt", `Icons of the most important items in ${stock.name}`)
                        .style("width", "100%")
                )
                .content(
                    W2.html("ul")
                        .class("list-group list-group-flush")
                        .content(stockCardPropertyEntry("Location", stock.location.name))
                        .content(stockCardPropertyEntry("Priority", stock.priority))
                        .content(stockCardPropertyEntry("Planned", stock.amount))
                        .content(stockCardPropertyEntry("Warning Threshold", stock.warning_threshold))
                        .content(stockCardPropertyEntry("Available", available, availabilityColor))
                        .content(stockCardPropertyEntry("Contracts", stock.available_on_contracts))
                        .content(stockCardPropertyEntry("Corporation Hangar", stock.available_in_hangars))
                )
        }

        function categoryComponent(app, category, collapsed, toggleCollapse) {
            return W2.html("div")
                .class("card")
                .content(
                    W2.html("div")
                        .class("card-body")
                        .content(
                            //header
                            W2.html("div")
                                .class("d-flex align-items-baseline")
                                .content(
                                    W2.html("h5")
                                        .class("card-title flex-grow-1")
                                        .content(category.name)
                                )
                                .content(
                                    W2.html("button")
                                        .class("btn btn-secondary mx-1")
                                        .content(
                                            W2.html("i").class("fas fa-pen")
                                        )
                                        .event("click", () => editCategoryPopUp(app, category))
                                )
                                .contentIf(
                                    toggleCollapse,//only show expand button if expanding is supported
                                    W2.html("button")
                                        .class("btn btn-primary")
                                        .content(collapsed ? "Expand" : "Collapse")
                                        .event("click", (e) => {
                                            e.preventDefault()
                                            toggleCollapse(category.id)
                                        })
                                )
                        )
                        .contentIf(
                            //stock cards
                            !collapsed,
                            W2.html("div")
                                .class("d-flex flex-wrap")
                                .content((container) => {
                                    if (category.stocks.length < 1) {
                                        container.content(W2.html("span").content("You haven't added any stock to this category."))
                                    }
                                    for (const stock of category.stocks) {
                                        container.content(stockCardComponent(app, stock))
                                    }
                                })
                        )
                )
        }

        function categoryListComponent(app) {
            class CategoryListState extends W2.W2MountState {
                categories
                location
                collapsed
                defaultCollapseState

                constructor() {
                    super();
                    this.categories = null
                    this.location = null
                    this.collapsed = {}
                    this.defaultCollapseState = true
                    this.loadData()
                }

                setLocation(location) {
                    this.location = location
                    this.loadData()
                }

                toggleCollapse(id) {
                    this.collapsed[id] = !this.collapsed[id]
                    this.stateChanged()
                }

                collapseAll() {
                    this.collapsed = {}
                    this.defaultCollapseState = true
                    this.stateChanged()
                }

                expandAll() {
                    this.defaultCollapseState = false
                    for (const category of this.categories) {
                        this.collapsed[category.id] = false
                    }
                    this.stateChanged()
                }

                isCollapsed(id) {
                    const state = this.collapsed[id]

                    if (state === undefined) {
                        this.collapsed[id] = this.defaultCollapseState
                        return this.defaultCollapseState
                    }

                    return state
                }

                async loadData() {
                    let url = "{{ route("inventory.getCategories") }}"
                    if (this.location) {
                        url = `{{ route("inventory.getCategories") }}?location=${this.location}`
                    }

                    const response = await fetch(url)
                    if (!response.ok) {
                        BoostrapToast.open("Categories", "Failed to load category data")
                        this.categoryData = null
                    }

                    this.categories = await response.json()

                    this.stateChanged()
                }
            }

            const state = new CategoryListState()

            return W2.mount(state, (container, mount, state) => {
                if (state.categories) {
                    for (const category of state.categories) {
                        container.content(
                            categoryComponent(
                                app,
                                category,
                                state.isCollapsed(category.id),
                                (id) => state.toggleCollapse(id)
                            )
                        )
                    }
                } else {
                    container.content("Loading...")
                }
            })
        }

        //stock creation and edit button
        async function editStockPopUp(app, stock) {
            const priorities = await getStockPriorities()

            const multibuy_placeholder = "Co - Processor II 2\nDrone Damage Amplifier II 1\nTristan 3"
            const fit_placeholder = "[Pacifier, 2022 Scanner]\n\nCo-Processor II\nCo-Processor II\nType-D Restrained Inertial Stabilizers\nInertial Stabilizers II"

            //create popup
            BootstrapPopUp.open(stock.name || "New Stock", (container, popup) => {

                let location = null
                //convert the location to a select2 compatible object
                if (stock.location) {
                    location = {
                        id: stock.location.id || null,
                        text: stock.location.name || null
                    }
                }

                //ui state
                const state = {
                    type: stock.fitting_plugin_fitting_id ? "plugin" : "multibuy",
                    amount: stock.amount || 1,
                    warning_threshold: stock.warning_threshold || 1,
                    location, //conversion from json see above
                    priority: stock.priority || 1,
                    checkHangars: stock.check_corporation_hangars !== undefined ? Boolean(stock.check_corporation_hangars) : true,
                    checkContracts: stock.check_contracts !== undefined ? Boolean(stock.check_contracts) : true,
                    multibuy: "", //for existing stocks, the data is loaded after the ui code, as it needs access to the mount
                    fit: "",
                    name: stock.name || "",
                    pluginFit: stock.fitting_plugin_fitting_id ? {
                        id: stock.fitting_plugin_fitting_id,
                        text: stock.name // should be synchrnoized with the fitting name
                    } : null,

                    invalidLocation: false,
                    invalidFit: false,
                    invalidName: false,
                    invalidPluginFit: false
                }

                //render stock creation popup content in a mount
                const mount = W2.mount(state, (container, mount, state) => {

                    //type selection
                    container.content(
                        W2.html("div")
                            .class("form-group")
                            .content(
                                W2.html("label")
                                    .attribute("for", W2.getID("editStockSelectType", true))
                                    .content("Stock Type"),
                                W2.html("select")
                                    .class("form-control")
                                    .content(
                                        //add type options
                                        W2.html("option")
                                            .content("Multibuy")
                                            .attribute("value", "multibuy")
                                            .attributeIf(state.type === "multibuy", "selected", true),
                                        W2.html("option")
                                            .content("Fit")
                                            .attribute("value", "fit")
                                            .attributeIf(state.type === "fit", "selected", true),
                                        W2.html("option")
                                            .content("Fitting Plugin (requires seat-fitting to be installed)")
                                            .attribute("value", "plugin")
                                            .attributeIf(state.type === "plugin", "selected", true)
                                    )
                                    .event("change", (e) => {
                                        //update the state and rerender
                                        state.type = e.target.value
                                        mount.update()
                                    })
                            )
                    )

                    //we have a multibuy
                    if (state.type === "multibuy") {
                        container.content(
                            //textarea
                            W2.html("div")
                                .class("form-group")
                                .content(
                                    W2.html("label")
                                        .attribute("for", W2.getID("editStockMultibuy", true))
                                        .content("Multibuy"),
                                    W2.html(
                                        W2.html("textarea")
                                            .class("form-control")
                                            .id(W2.getID("editStockMultibuy"))
                                            .attribute("placeholder", multibuy_placeholder)
                                            .attribute("rows", 8)
                                            .content(state.multibuy)
                                            .event("change", (e) => {
                                                state.multibuy = e.target.value
                                                //no need to update the ui
                                            })
                                    )
                                ),
                            //name
                            W2.html("div")
                                .class("form-group")
                                .content(
                                    W2.html("label")
                                        .attribute("for", W2.getID("editStockName", true))
                                        .content("Name"),
                                    W2.html(
                                        W2.html("input")
                                            .class("form-control")
                                            .classIf(state.invalidName, "is-invalid")
                                            .id(W2.getID("editStockName"))
                                            .attribute("type", "text")
                                            .attribute("placeholder", "Enter a name...")
                                            .attribute("value", state.name)
                                            .event("change", (e) => {
                                                state.name = e.target.value
                                                //update UI if it is valid now
                                                if (state.name.length > 0) {
                                                    state.invalidname = false
                                                    mount.update()
                                                }
                                            })
                                    )
                                )
                        )
                    }
                    //it is a fit
                    else if (state.type === "fit") {
                        container.content(
                            W2.html("div")
                                .class("form-group")
                                .content(
                                    W2.html("label")
                                        .attribute("for", W2.getID("editStockFit", true))
                                        .content("Fit"),
                                    W2.html(
                                        W2.html("textarea")
                                            .class("form-control")
                                            .classIf(state.invalidFit, "is-invalid")
                                            .id(W2.getID("editStockFit"))
                                            .attribute("placeholder", fit_placeholder)
                                            .attribute("rows", 8)
                                            .content(state.fit)
                                            .event("change", (e) => {
                                                state.fit = e.target.value

                                                if (state.fit.length > 0) {
                                                    state.invalidFit = false
                                                }

                                                mount.update()
                                            })
                                    )
                                )
                        )
                    }
                    //it is a fit from the fitting plugin
                    else if (state.type === "plugin") {
                        container.content(
                            W2.html("div")
                                .class("form-group")
                                .content(
                                    W2.html("label")
                                        .attribute("for", W2.getID("editStockPlugin", true))
                                        .content("Fitting Plugin"),
                                    select2Component({
                                        select2: {
                                            placeholder: "Select a fit",
                                            ajax: {
                                                url: "{{ route("inventory.fittingsLookup") }}"
                                            },
                                            allowClear: true,
                                            dropdownParent: popup.jQuery
                                        },
                                        selectionListeners: [
                                            (selection) => {
                                                state.pluginFit = selection
                                                state.invalidPluginFit = false
                                                mount.update()
                                            }
                                        ],
                                        id: W2.getID("editStockPlugin"),
                                        selection: state.pluginFit
                                    }),
                                )
                                .contentIf(state.invalidPluginFit,
                                    W2.html("small")
                                        .class("text-danger")
                                        .content("Please select a fit")
                                )
                        )
                    }

                    //data required for any kind of stock
                    //amount
                    container.content(
                        W2.html("div")
                            .class("form-group")
                            .content(
                                W2.html("label")
                                    .attribute("for", W2.getID("editStockAmount", true))
                                    .content("Amount"),
                                W2.html("input")
                                    .class("form-control")
                                    .id(W2.getID("editStockAmount"))
                                    .attribute("type", "number")
                                    .attribute("value", state.amount)
                                    .event("change", (e) => {
                                        //update the state and rerender
                                        state.amount = e.currentTarget.value
                                        //no need to update the ui
                                    })
                            )
                    )
                    //warning threshold
                    container.content(
                        W2.html("div")
                            .class("form-group")
                            .content(
                                W2.html("label")
                                    .attribute("for", W2.getID("editStockWarningThreshold", true))
                                    .content("Warning Threshold"),
                                W2.html("input")
                                    .class("form-control")
                                    .id(W2.getID("editStockWarningThreshold"))
                                    .attribute("type", "number")
                                    .attribute("value", state.warning_threshold)
                                    .event("change", (e) => {
                                        //update the state and rerender
                                        state.warning_threshold = e.currentTarget.value
                                        //no need to update the ui
                                    })
                            )
                    )
                    //location
                    container.content(
                        W2.html("div")
                            .class("form-group")
                            .content(
                                //label
                                W2.html("label")
                                    .attribute("for", W2.getID("editStockLocation", true))
                                    .content("Location"),
                            )
                            .content(
                                select2Component({
                                    select2: {
                                        placeholder: "All locations",
                                        ajax: {
                                            url: "{{ route("inventory.locationLookup") }}"
                                        },
                                        allowClear: true,
                                        dropdownParent: popup.jQuery
                                    },
                                    selectionListeners: [
                                        (selection) => {
                                            if (selection) {
                                                //set location
                                                state.location = selection
                                            }
                                            state.invalidLocation = false
                                            //update ui to switch location selection stage
                                            mount.update()
                                        }
                                    ],
                                    id: W2.getID("editStockLocation"),
                                    selection: state.location
                                })
                            )
                            .contentIf(state.invalidLocation,
                                W2.html("small")
                                    .class("text-danger")
                                    .content("Please select a location")
                            )
                    )
                    //priority
                    container.content(
                        W2.html("div")
                            .class("form-group")
                            .content(
                                W2.html("label")
                                    .attribute("for", W2.getID("editStockPriority", true))
                                    .content("Priority"),
                                W2.html("select")
                                    .class("form-control")
                                    .id(W2.getID("editStockPriority"))
                                    //add options
                                    .content((container) => {
                                        //add one entry for each option
                                        for (const priority of priorities) {
                                            container.content(
                                                W2.html("option")
                                                    .content(priority.name)
                                                    .attribute("value", priority.priority)
                                                    .attributeIf(state.priority === priority.priority, "selected", true)
                                            )
                                        }
                                    }),
                            )
                            .event("change", (e) => {
                                //update the state and rerender
                                state.priority = parseInt(e.target.value)
                                //no need to update the ui
                            })
                    )
                    //source checks
                    container.content(
                        W2.html("div")
                            .class("form-group")
                            .content(
                                W2.html("label").content("Item Source Settings"),
                                //check contracts
                                W2.html("div")
                                    .class("form-check")
                                    .content(
                                        W2.html("input")
                                            .attribute("type", "checkbox")
                                            .id(W2.getID("editStockCheckContracts", true))
                                            .class("form-check-input")
                                            .attributeIf(state.checkContracts, "checked", true)
                                            .event("change", (e) => {
                                                state.checkContracts = e.target.checked
                                                //no need to update the ui
                                            }),
                                        W2.html("label")
                                            .attribute("for", W2.getID("editStockCheckContracts"))
                                            .class("form-check-label")
                                            .content("Check Contracts")
                                    ),
                                //check hangars
                                W2.html("div")
                                    .class("form-check")
                                    .content(
                                        W2.html("input")
                                            .attribute("type", "checkbox")
                                            .id(W2.getID("editStockCheckHangars", true))
                                            .class("form-check-input")
                                            .attributeIf(state.checkHangars, "checked", true)
                                            .event("change", (e) => {
                                                state.checkHangars = e.target.checked
                                                //no need to update the ui
                                            }),
                                        W2.html("label")
                                            .attribute("for", W2.getID("editStockCheckHangars"))
                                            .class("form-check-label")
                                            .content("Check Hangars")
                                    )
                            ),
                    )

                    //add bottom button bar
                    container.content(
                        //flexbox container for buttons
                        W2.html("div")
                            .class("d-flex")

                            //delete button
                            //only show the stock delete button if we edit one
                            .contentIf(stock.id,
                                confirmButtonComponent("Delete", async () => {

                                    //make deletion request
                                    const response = await jsonPostAction("{{ route("inventory.deleteStock") }}", {
                                        id: stock.id
                                    })

                                    //check response status
                                    if (response.ok) {
                                        BoostrapToast.open("Stock", "Successfully deleted the stock")
                                    } else {
                                        BoostrapToast.open("Stock", "Failed to delete the stock")
                                    }

                                    //reload categories
                                    app.categoryList.state.loadData()
                                })
                            )

                            //close button
                            .content(
                                W2.html("button")
                                    .class("btn btn-secondary ml-auto")
                                    .content("Close")
                                    .event("click", () => {
                                        //close popup when close button is pressed
                                        popup.close()
                                    })
                            )

                            //save button
                            .content(
                                W2.html("button")
                                    .class("btn btn-primary ml-1")
                                    .content("Save")
                                    .event("click", async () => {
                                        //save the stock

                                        let invalidData = false

                                        if (state.location === null) {
                                            invalidData = true
                                            state.invalidLocation = true
                                        } else {
                                            state.invalidLocation = false
                                        }

                                        if (state.type === "fit" && state.fit.length === 0) {
                                            state.invalidFit = true
                                            invalidData = true
                                        } else {
                                            state.invalidFit = false
                                        }

                                        if (state.type === "multibuy" && state.name.length === 0) {
                                            state.invalidName = true
                                            invalidData = true
                                        } else {
                                            state.invalidName = false
                                        }

                                        if(state.type === "plugin" && !state.pluginFit){
                                            state.invalidPluginFit = true
                                            invalidData = true
                                        } else {
                                            state.invalidPluginFit = false
                                        }

                                        //update for validation
                                        mount.update()

                                        if (invalidData) {
                                            return
                                        }

                                        const data = {
                                            id: stock.id,
                                            location: state.location.id,
                                            amount: state.amount,
                                            warning_threshold: state.warning_threshold,
                                            priority: state.priority,
                                            check_contracts: state.checkContracts,
                                            check_hangars: state.checkHangars
                                        }
                                        if (state.type === "fit") {
                                            data.fit = state.fit
                                        } else if (state.type === "multibuy") {
                                            data.multibuy = state.multibuy
                                            data.name = state.name
                                        } else if(state.type === "plugin"){
                                            data.plugin_fitting_id = state.pluginFit.id
                                        }

                                        const response = await jsonPostAction("{{ route("inventory.saveStock") }}", data)

                                        //check response status
                                        if (response.ok) {
                                            BoostrapToast.open("Stock", "Successfully saved the stock")
                                        } else {
                                            BoostrapToast.open("Stock", "Failed to safe the stock")
                                        }

                                        //reload categories
                                        app.categoryList.state.loadData()

                                        //if it is saved, close the popup
                                        if (response.ok) {
                                            popup.close()
                                        } else {
                                            mount.update()
                                        }
                                    })
                            )
                    )
                })

                async function loadMultibuy(id) {
                    const response = await jsonPostAction("{{ route("inventory.exportMultibuy") }}", {
                        stocks: [id]
                    })

                    if(!response.ok){
                        BoostrapToast.open("Stock","Failed to load items")
                        return
                    }

                    const data = await response.json()
                    state.multibuy = data.multibuy
                    mount.update()
                }

                //load items as multibuy if it is an existing stock
                if(stock.id){
                    loadMultibuy(stock.id)
                }

                container.content(mount)
            })
        }

        function toolButtonPanelComponent(app) {
            return W2.html("div")
                .class("d-flex flex-row align-items-center mb-3")
                .content(
                    W2.html("button")
                        .class("btn btn-success ml-auto")
                        .content(W2.html("i").class("fas fa-sync")," Update")
                        .event("click", (e) => {
                            e.target.blur()
                            app.categoryList.state.loadData()
                        })
                )
                .content(
                    W2.html("button")
                        .class("btn btn-secondary ml-1")
                        .content("Collapse All")
                        .event("click", () => {
                            app.categoryList.state.collapseAll()
                        })
                )
                .content(
                    W2.html("button")
                        .class("btn btn-secondary ml-1")
                        .content("Expand All")
                        .event("click", () => {
                            app.categoryList.state.expandAll()
                        })
                )
                .content(
                    W2.html("button")
                        .class("btn btn-primary ml-1")
                        .content(
                            W2.html("i").class("fas fa-plus"),
                            " Stock"
                        )
                        .event("click", () => {
                            editStockPopUp(app, {})
                        })
                )
                .content(
                    W2.html("button")
                        .class("btn btn-primary ml-1")
                        .content(
                            W2.html("i").class("fas fa-plus"),
                            " Category"
                        )
                        .event("click", () => {
                            editCategoryPopUp(app, {})
                        })
                )
        }

        class App {
            categoryList
            locationFilter

            constructor() {
                this.categoryList = categoryListComponent(this)

                this.locationFilter = new LocationFilterComponent({
                    locationListeners: [(location) => {
                        this.categoryList.state.setLocation(location)
                    }]
                })
            }

            render() {
                return W2.emptyHtml()
                    .content(this.locationFilter.mount())
                    .content(toolButtonPanelComponent(this))
                    .content(this.categoryList)
            }
        }

        new App().render().addInto("content-target")
    </script>
@endpush



@push("head")
    <style>
        .stock-list-entry:hover {
            background-color: #eee;
        }

        .select2-container {
            width: 100% !important;
        }

        .toast {
            background-color: white;
        }
    </style>
@endpush