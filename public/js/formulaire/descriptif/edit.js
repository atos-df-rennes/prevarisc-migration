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

    $(document).ready(function() {
        // Variable pour contrôler l'affichage du message beforeunload
        let allowUnload = false;

        $('input[type="checkbox"]').on('click', function () {
            const elemToToggle = $('#' + this.name)
            if (elemToToggle.hasClass('hide')) {
                elemToToggle.show()
                elemToToggle.removeClass('hide')
            } else {
                elemToToggle.hide()
                elemToToggle.addClass('hide')
            }
        })

        $('.table-responsive').each(function () {
            if ($(this)[0].scrollWidth > $(this)[0].clientWidth) {
                $(this).find('.colonne-edition').each(function () {
                    $(this).addClass('with-shadow')
                })
            } else {
                $(this).find('.table')[0].style.marginBottom = 0
            }
        })

        $('.table-responsive').on('scroll', function () {
            if ($(this)[0].scrollLeft < $(this)[0].scrollLeftMax) {
                $(this).find('.colonne-edition').each(function () {
                    $(this).addClass('with-shadow')
                })
            } else {
                $(this).find('.colonne-edition').each(function () {
                    $(this).removeClass('with-shadow')
                })
            }
        })

        let beforeUnloadEnabled = true;

        document.addEventListener('click', function(event) {
            const target = event.target.closest('[data-unload="false"]')
            if (target) {
                beforeUnloadEnabled = false;
            }
        })

        window.addEventListener('beforeunload', function(event) {
            if (beforeUnloadEnabled) {
                event.preventDefault()
                return ''
            }
        })
    })
})