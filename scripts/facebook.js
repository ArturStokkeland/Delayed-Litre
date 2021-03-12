let FBID = null;
let username;

let loggedInDiv = document.querySelector("#loggedIn");
let notLoggedInDiv = document.querySelector("#notLoggedIn");


window.fbAsyncInit = function() {

    FB.init({
        appId      : '369335574327552',
        cookie     : true,
        xfbml      : true,
        version    : 'v9.0'
    });

    FB.getLoginStatus(function(response) {
    
        if(response.status === "connected") {
            loginUser(response.authResponse.userID);
        }
        else {
            showLogin();
        }
    
    });

};



document.querySelector("#facebookLogin").addEventListener("click", function() {
    FB.login(function(response) {
        
        if (response.status === "connected") {
            loginUser(response.authResponse.userID);
        }
        else {
            showErrorModal("Error: Login might have been cancelled, please try again");
        }

    });
});

document.querySelector("#facebookLogout").addEventListener("click", function() {
    FB.logout(function(response) {
        FBID = null;
        showLogin();
    });
});

function loginUser(userID) {

    fetch(`endpoints/user.php?id=${userID}`)
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            myData = JSON.parse(data);
            FBID = myData.id;
            username = myData.name;
            showWebApp();
            getNewsFeed();
        }
        else {
            registerUser(userID);
        }
    })
    .catch((error) => {
        showErrorModal("Error: Something went wrong, please wait a bit and try again");
        showLogin();
    })
    
}

function registerUser(userID) {

    FB.api('/me',{fields: 'name,picture'}, function(response) {

        let formData = new FormData();
        formData.append("id", response.id);
        formData.append("name", response.name);
        formData.append("img", response.picture.data.url);

        fetch("endpoints/user.php", {
            method: "POST",
            body: formData
        })
        .then((res) => res.text())
        .then((data) => {
            if (data) {
                let myData = JSON.parse(data);
                FBID = myData.id;
                username = myData.name;
                showWebApp();
                loadLibrary(FBID);
            }
            else {
                showErrorModal("Error: Something went wrong, please wait a bit and try again");
                showLogin();
            }
        })
        .catch((error) => {
            showErrorModal("Error: Something went wrong, please wait a bit and try again");
            showLogin();
        })

    });

}

function showLogin() {
    loggedInDiv.classList.add("hidden");
    notLoggedInDiv.classList.remove("hidden");
}

function showWebApp() {
    notLoggedInDiv.classList.add("hidden");
    loggedInDiv.classList.remove("hidden");
}

function bypassFacebook() {
    if (document.querySelector("#superSecretPassword").value === "ci609") {
        fetch("endpoints/user.php?id=314159")
        .then((res) => res.text())
        .then((data) => {
            if (data) {
                let myData = JSON.parse(data);
                FBID = myData.id;
                username = myData.username
                showWebApp();
                loadLibrary(FBID);
            }
            else {
                showErrorModal("Error: user does not exist");
                showLogin();
            }
        })
        .catch((error) => showErrorModal("Error: Could not find data"))
    }
    else {
        console.log(document.querySelector("#superSecretPassword").value === "ci609");
    }
}

document.querySelector("#bypassForm").addEventListener("submit", function(event) {
    event.preventDefault();
    bypassFacebook;
});
document.querySelector("#submitPassword").addEventListener("click", bypassFacebook);
