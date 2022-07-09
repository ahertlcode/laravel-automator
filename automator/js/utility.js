function loadHtmlPage(page, target, scriptx=null) {
    var loc = page;
    if(scriptx !== null){
        $("#"+target).load(loc,function(){
            $.getScript(scriptx);
        });
    }else{
    $("#" + target).load(loc);
}
    window.scrollTo(0, 0);
}

function fillDropDown(data, drop){
    data.databases.forEach(function(db) {
    let valx = db.Database;
    let texti = db.Database;

    let option = $('<option/>');
    option.attr({ 'value': valx }).text(texti);
    $('#' + drop).append(option);
});
}

function showloading() {

}

function fillState(nation) {

}

function processform(fid) {
    var fm = {};
    var frmData = $("#" + fid).serializeArray();
    if (frmData.length > 0) {
        frmData.forEach(function(fData){
            fm[fData["name"]] = fData["value"];
        });
    }
    return fm;
}

function clearbox(ob) {
    var lbox = $("#" + ob + ' option').size();
    if (lbox > 1) {
        for (i = 1; i < lbox; i++) {
            $("#" + ob).removeItem(i);
        }
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";domain=;path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function local_store(arr, desc, actn) {
    var retn;
    if (typeof(Storage) !== undefined) {
        // Code for localStorage/sessionStorage.
        if (actn === "add") {
            //to create storage
            var stringifyd = JSON.stringify(arr);
            //save to localstorage
            if (localStorage.setItem(desc, stringifyd)) {
                retn = true;
            } else {
                retn = false;
            }
        } else if (actn === "get") {
            retn = localStorage.getItem(desc);
            if (retn != "{}") {
                retn = JSON.parse(retn);
            }
        } else if (actn === "remove") {
            if (localStorage.removeItem(desc)) {
                retn = true;
            } else {
                retn = false;
            }
        }
    } else {
        //Sorry! No Web Storage support..
    }
    return retn;
}

function getRandomNumber(start = 0, end = 1000) {
    var num = Math.floor(Math.random() * end);
    return num;
}

function inArrayX(target, iarray) {
    var hasValue = false;
    asize = iarray.length;
    if (typeof(asize) === undefined && (typeof(iarray) !== null || typeof(iarray) !== undefined)) {
        array = [];
        array.push(iarray);
        iarray = array;
        asize = 1;
    }
    var ArrLen = iarray.map(function(lk) {
        return Object.keys(lk).length;
    });
    if (asize === 1 && !$.isArray(ArrLen)) {
        for (var index = 0; index < ArrLen; index++) {
            var cur = iarray[asize - 1][index];
            if (cur === target) {
                hasValue = true;
            }
        }
    } else {
        i = 1;
        iarray.forEach(function(obj) {
            if (obj[i] === target) {
                hasValue = true;
            }
            i += 1;
        });
    }
    return hasValue;
}

function previewImage(file, prevwin) {
    var msg;
    $(".thumbnail").remove();
    var galleryId = prevwin;
    var gallery = document.getElementById(galleryId);
    var imageType = /image.*/;
    if (!file.type.match(imageType)) {
        msg = "File Type must be an image";
        notify(msg, "w3-orange", "2000");
        throw "";
    }
    if (file.name.length > 50) {
        msg = "Please rename your file! choose a shorter filename not more that 30 character long";
        notify(msg, "w3-orange", "2000");
        throw "";
    }
    if (file.size > 102400) {
        msg = "File too large. File size should not exceed 500KB. Scale your image file and try again.";
        notify(msg, "w3-orange", "2000");
        throw "";
    }

    var thumb = document.createElement("div");
    thumb.classList.add('thumbnail'); // Add the class thumbnail to the created div
    var img = document.createElement("img");
    img.file = file;
    thumb.appendChild(img);
    gallery.appendChild(thumb);
    // Using FileReader to display the image content
    var reader = new FileReader();
    reader.onload = (function(aImg) { return function(e) { aImg.src = e.target.result; }; })(img);
    reader.readAsDataURL(file);
}


function show_alert(msg){
    alert(msg);
}

function logError(ErrObj) {
    console.log(ErrObj);
}

function toggle_display(id=null, act=null){
    hideall();
    showthis(id, act);
}

function hideall(){
    $(".display").removeClass("display-show");
    $(".display").addClass("display-hide");
}

function showthis(obj, actn=null){
    if (actn == "reload") window.location.reload();
    $("#"+obj).addClass("display-show");
}

function page_reload(){
    window.location.reload(true);
}