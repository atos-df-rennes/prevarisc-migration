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

    let beforeUnloadEnabled = true

    document.addEventListener('click', function(event) {
        if (event.target.hasAttribute('data-do-not-unload')) {
            beforeUnloadEnabled = false
        }
    })

    window.addEventListener('beforeunload', function(event) {
        if (beforeUnloadEnabled) {
            event.preventDefault()
        }
    })
})

document.addEventListener('DOMContentLoaded', () => {
    const showTocButton = document.getElementById('show-toc');

    if (showTocButton) {
        showTocButton.addEventListener('click', () => {
            $('#tocModal').modal('show');
        });
    }
});