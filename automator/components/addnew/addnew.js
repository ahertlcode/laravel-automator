const addNewController = function() {
    const adn = this;

    adn.loadPage = () => {
        adn.addNew();
    }
}

app.component('addNew', {
    templateUrl: "../components/addnew/addnew.html",
    controller: addNewController,
    bindings: {
        addNew: "&"
    }
})