const searchBarController = function($scope) {
    const sb = this;

    sb.dosearch = () => {
        sb.searchBar();
    }
}

app.component('searchBar', {
    templateUrl: "../components/search/search.html",
    controller: searchBarController,
    bindings: {
        searchBar: "&",
        searchTerm: "="
    }
})