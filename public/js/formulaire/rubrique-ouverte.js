function switchOpened(id_input){
    if ((document.getElementById("descriptif_rubrique_"+id_input+"_opened").value) === "1") {
        document.getElementById("descriptif_rubrique_"+id_input+"_opened").value = 0
    }
    else {
        document.getElementById("descriptif_rubrique_"+id_input+"_opened").value = 1
    }
}