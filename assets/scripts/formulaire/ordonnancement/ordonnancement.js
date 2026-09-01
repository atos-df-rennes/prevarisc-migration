window.onload = function() {
    const listGrp = document.getElementsByClassName('grp')
    let pendingRequests = 0
    const debounceTimers = {}

    // @fixme: remplacer par le composant toast en conservant le comportement complete de l'Ajax
    const indicator = document.createElement('div')
    indicator.style.cssText = 'display:none; position:fixed; bottom:20px; right:20px; z-index:9999;'
    indicator.innerHTML =
        '<div class="alert alert-info" style="margin:0; padding:8px 15px;">' +
            '<span class="glyphicon glyphicon-refresh ordo-spin"></span>' +
            ' Sauvegarde en cours\u2026' +
        '</div>'
    document.body.appendChild(indicator)

    const style = document.createElement('style')
    style.textContent = '@keyframes ordo-spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } } .ordo-spin { display:inline-block; animation:ordo-spin 1s linear infinite; }'
    document.head.appendChild(style)

    window.addEventListener('beforeunload', function(e) {
        if (pendingRequests > 0) {
            e.preventDefault()
            e.returnValue = ''
        }
    })

    const getListOrderInput = (idInputElem = null) => {
        let toReturn = []

        const tableRows = document.querySelectorAll('#' + idInputElem + ' tr')
        Array.from(tableRows).forEach(el => {
            toReturn.push({
                idx: Array.from(tableRows).indexOf(el),
                ID: el.id
            })
        })

        return toReturn
    }

    const updateAllRequest = (newList = []) => {
        newList.forEach(champ => {
            setNewIdxRequest(champ)
        })
    }

    const setNewIdxRequest = (objData = {}) => {
        const url = document.location.href.includes('rubriques') ?
            "/admin/formulaires/champs/" + objData.ID + "/index/" + objData.idx
        :
            "/admin/formulaires/rubriques/" + objData.ID + "/index/" + objData.idx

        pendingRequests++
        indicator.style.display = 'block'

        $.ajax({
            type: "post",
            url: url,
            dataType: 'json',
            complete: function() {
                pendingRequests--
                if (pendingRequests === 0) {
                    indicator.style.display = 'none'
                }
            }
        })
    }

    for (let i = 0; i < listGrp.length; i++) {
        new Sortable.create(document.getElementById(listGrp[i].id), {
            handle: '.tdMove',
            group: listGrp[i].id,
            animation: 100
        })

        document.getElementById(listGrp[i].id).addEventListener(
            'dragend',
            function() {
                const listId = listGrp[i].id
                const snapshot = getListOrderInput(listId)
                clearTimeout(debounceTimers[listId])
                debounceTimers[listId] = setTimeout(function() {
                    updateAllRequest(snapshot)
                }, 400)
            },
            false
        )
    }
}