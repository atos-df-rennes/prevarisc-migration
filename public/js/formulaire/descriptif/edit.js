$(document).ready(function() {
    $('input[type="checkbox"]').on('click', function() {
        const elemToToggle = $('#'+this.name)
        if (elemToToggle.hasClass('hide')) {
            elemToToggle.show()
            elemToToggle.removeClass('hide')
        } else {
            elemToToggle.hide()
            elemToToggle.addClass('hide')
        }
    })

    $('.table-responsive').each(function() {
        if ($(this)[0].scrollWidth > $(this)[0].clientWidth) {
            $(this).find('.colonne-edition').each(function() {
                $(this).addClass('with-shadow')
            })
        } else {
            $(this).find('.table')[0].style.marginBottom = 0
        }
    })

    $('.table-responsive').on('scroll', function() {
        if ($(this)[0].scrollLeft < $(this)[0].scrollLeftMax) {
            $(this).find('.colonne-edition').each(function() {
                $(this).addClass('with-shadow')
            })
        } else {
            $(this).find('.colonne-edition').each(function() {
                $(this).removeClass('with-shadow')
            })
        }
    })
})