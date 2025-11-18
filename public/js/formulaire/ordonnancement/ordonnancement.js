window.onload = function() {
    const listGrp = document.getElementsByClassName('grp')
    let originList = []

    const getListOrderInput = (idInputElem = null) => {
        let toReturn = []

        const tableRows = document.querySelectorAll('#' + idInputElem + ' tr')
        Array.from(tableRows).forEach(el => {
            toReturn.push({
                // Nouvelle position
                idx: Array.from(tableRows).indexOf(el),
                ID: el.id
            })
        })

        return toReturn
    }
    
    //Retourne la liste des entity qui ont changé d'index
    const compareAndRequest = (newList = []) => {
        newList.filter(elem => elem.ID !== originList[elem.idx].ID ).forEach(champ =>{
            setNewIdxRequest(champ)
        })
    }

    const setNewIdxRequest = (objData = {}) => {
        const url = document.location.href.includes('rubriques') ?
            "/admin/formulaires/champs/" + objData.ID + "/index/" + objData.idx
        :
            "/admin/formulaires/rubriques/" + objData.ID + "/index/" + objData.idx

        $.ajax({
            type: "post",
            url: url,
            datatype:'json'
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
                compareAndRequest(getListOrderInput(listGrp[i].id))
            },
            false
        )

        document.getElementById(listGrp[i].id).addEventListener(
            'dragstart',
            function() {
                originList = getListOrderInput(listGrp[i].id)
            },
            false
        )
    }
}