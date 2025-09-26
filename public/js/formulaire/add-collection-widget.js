document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.add-collection-widget').addEventListener('click', function (e) {
        const list = document.querySelector(this.getAttribute('data-list-selector'));
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
    });
});