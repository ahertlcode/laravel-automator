const uploadPageController = function() {
    const upd = this;

    upd.upPage = () => {
        upd.uploadPage();
    }
}

app.component('uploadPage', {
    templateUrl: "../components/upload/upload.html",
    controller: uploadPageController,
    bindings: {
        uploadPage: "&"
    }
})