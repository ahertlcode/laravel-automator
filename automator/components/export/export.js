const exportPageController = function() {
    const exp = this;

    exp.exPage = () => {
        exp.exportPage();
    }
}

app.component('exportPage', {
    templateUrl: "../components/export/export.html",
    controller: exportPageController,
    bindings: {
        exportPage: "&"
    }
});