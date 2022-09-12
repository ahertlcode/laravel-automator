const toolBarController = function($scope) {
    const lbar = this;
    
    lbar.ishow = () => {
        window.location.href = lbar.addNew;
    }

    lbar.upload = () => {
        window.location.href = lbar.uploadPage;
    }

    lbar.export = () => {
        lbar.exportPage();
    }

    lbar.search = () => {
        lbar.searchBar();
    }
}

app.component('toolBar', {
    templateUrl: "../components/toolbar/toolbar.html",
    controller: toolBarController,
    bindings: {
        addNew: "=",
        uploadPage: "=",
        exportPage: "&",
        searchBar: "&",
        searchTerm: "="

    }
});