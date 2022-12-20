function workspaceCreatorPopup(...updatedCallbacks) {
    const state = {
        name: ""
    }

    BootstrapPopUp.open("Create Workspace", function (container, popup) {
        container.content(
            W2.html("div")
                .class("form-group")
                .content(
                    W2.html("label")
                        .content("Name"),
                    W2.html("input")
                        .attribute("type", "text")
                        .class("form-control")
                        .attribute("placeholder", "Enter the workspace's name..")
                        .event("change", (e) => {
                            state.name = e.target.value
                        })
                ),
            W2.html("button")
                .class("btn btn-primary")
                .content("Create")
                .event("click", async () => {
                    if (state.name.length > 0) {
                        const response = await jsonPostAction("/inventory/workspaces/create", {
                            name: state.name
                        })

                        if (response.ok) {
                            BoostrapToast.open("Success", `Successfully created workspace!`)
                            popup.close()
                        } else {
                            BoostrapToast.open("Error", `Failed to create workspace!`)
                        }

                        for (const updatedCallback of updatedCallbacks) {
                            updatedCallback()
                        }
                    }
                })
        )
    })
}

function workspaceSelector(...callbacks) {
    const state = {
        workspaces: [],
        currentWorkspace: null
    }

    async function loadWorkspaces() {
        const response = await fetch("/inventory/workspaces/list")
        if (response.ok) {
            const data = await response.json()
            state.workspaces = data

            if (state.currentWorkspace === null) {
                if (data.length > 0) {
                    //state.currentWorkspace = data[0]
                }
            }
        } else {
            BoostrapToast.open("Error", "Failed to load workspaces")
        }
    }

    const mount = W2.mount(state, (container, mount, state) => {
        container.content(
            W2.html("div")
                .class("card")
                .content(
                    W2.html("div")
                        .class("card-header d-flex flex-row align-items-baseline")
                        .content(
                            W2.html("h3")
                                .class("cart-title")
                                .content(`Select Workspace (${state.currentWorkspace ? state.currentWorkspace.name : ""})`),
                            W2.html("button")
                                .class("btn btn-success ml-auto")
                                .content(
                                    W2.html("i").class("fas fa-plus")
                                )
                                .event("click", () => {
                                    workspaceCreatorPopup(async ()=>{
                                        await loadWorkspaces()
                                        mount.update()
                                    })
                                })
                        ),
                    W2.html("div")
                        .class("card-body")
                        .content(
                            W2.html("ul")
                                .class("list-group")
                                .content((container) => {
                                    for (const workspace of state.workspaces) {
                                        container.content(
                                            W2.html("btn")
                                                .class("list-group-item list-group-item-action")
                                                .classIf(state.currentWorkspace && workspace.id === state.currentWorkspace.id, "active")
                                                .content(workspace.name)
                                                .event("click", () => {
                                                    for (const callback of callbacks) {
                                                        callback(workspace)
                                                    }
                                                    state.currentWorkspace = workspace
                                                    mount.update()
                                                })
                                        )
                                    }
                                })
                        )
                )
        )
    })

    loadWorkspaces().then(()=>mount.update())

    return mount
}