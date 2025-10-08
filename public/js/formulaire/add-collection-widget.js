document.addEventListener('DOMContentLoaded', function () {

    // Get the ul that holds the collection of tags
    const collectionHolder = document.querySelector('.remove-collection-widget');

    if(collectionHolder) {
        // add a delete link to all of the existing tag form li elements
        collectionHolder.querySelectorAll('li').forEach(
            (el) => addTagFormDeleteLink(el)
        );
    }

    document.getElementsByTagName('body').item(0).addEventListener('click', (e) => {
        var btnElement = e.target;

        if(!btnElement.classList.contains('add-collection-widget')){ // Si l'élément n'est pas un de ceux à traiter
            return;
        }
        const list = document.querySelector(btnElement.getAttribute('data-list-selector'));
        // Try to find the counter of the list or use the length of the list
        let counter = Number(list.dataset.widgetCounter) || list.children.length;

        // grab the prototype template
        let newWidget = list.getAttribute('data-prototype');
        // replace the "__name__" used in the id and name of the prototype
        // with a number that's unique to your emails
        // end name attribute looks like name="contact[emails][2]"
        newWidget = newWidget.replace(/__name__/g, counter);
        // Increase the counter
        counter++;
        // And store it, the length cannot be used if deleting widgets is allowed
        list.dataset.widgetCounter = counter;

        // create a new list element and add it to the list
        const container = document.createElement('div');
        container.innerHTML = list.getAttribute('data-widget-tags');
        const newElem = container.firstElementChild;
        newElem.innerHTML = newWidget;
        list.appendChild(newElem);

        if(list.classList.contains('remove-collection-widget')) {
            addTagFormDeleteLink(newElem);
        }
    })
});

function addTagFormDeleteLink(element) {
    const buttonWrapper = document.createElement('div');
    buttonWrapper.classList.add('col-sm-1');
    buttonWrapper.classList.add('col-sm-offset-1');

    buttonWrapper.innerHTML = '<button type="button" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></button>';

    element.firstElementChild.append(buttonWrapper);

    buttonWrapper.querySelector('button').addEventListener('click', function(e) {
        // remove the li for the tag form
        element.remove();
    });
}