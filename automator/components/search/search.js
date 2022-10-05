const searchBarController = function($scope) {
    const sb = this;

    sb.dosearch = (e) => {
        sb.doSearch = e.target.value;
    }
}

app.component('searchBar', {
    templateUrl: "../components/search/search.html",
    controller: searchBarController,
    bindings: {
        searchBar: "&",
        searchTerm: "=",
        doSearch: "="
    }
})
