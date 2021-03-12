// Navigation
let SPAPages = [
    document.querySelector("#wall"),
    document.querySelector("#library"),
    document.querySelector("#search"),
    document.querySelector("#upload"),
    document.querySelector("#settings"),
    document.querySelector("#loading"),
    document.querySelector("#error")
]

document.querySelector("#wallLink").addEventListener("click", function() { getNewsFeed(); });
document.querySelector("#libraryLink").addEventListener("click", function() { loadLibrary(FBID); });
document.querySelector("#searchLink").addEventListener("click", function() { searchForUsers(); });
document.querySelector("#uploadLink").addEventListener("click", function() { showPage("#upload") });
document.querySelector("#settingsLink").addEventListener("click", function() { showPage("#settings") });

function showPage(pageID) {

    SPAPages.forEach(element => {
        
        if (!element.classList.contains("hidden")) {
            element.classList.add("hidden");
        }

    });

    pageToShow = document.querySelector(pageID);
    if (pageToShow.classList.contains("hidden")) {

        pageToShow.classList.remove("hidden");

    }

}

//Uploading images

let imageUpload = document.querySelector("#imageInput");
let imageDescription = document.querySelector("#imageDescriptionInput");

document.querySelector("#imageUploadButton").addEventListener("click", function() {

    if (!imageUpload.files[0]) {
        showErrorModal("Error: Please select image to upload");
        return;
    }
    if (imageDescription.value === "") {
        showErrorModal("Error: Please write a description");
        return;
    }
    if (imageUpload.files[0].size > 500000) {
        showErrorModal("Error: Image too large, please use an image that is less than 500kb");
        return;
    }

    showPage("#loading");
    
    let formData = new FormData();
    formData.append("uid", FBID);
    formData.append("file", imageUpload.files[0]);
    formData.append("description", imageDescription.value);

    fetch("endpoints/image.php", {
        method: "POST",
        body: formData
    })
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            imageUpload.value = null;
            imageDescription.value = "";
            showPage("#upload");
            let myData = JSON.parse(data);
            console.log(myData);
            openModal(FBID, username, myData);
        }
        else {
            showErrorModal("Error: Something went wrong");
            showPage("#upload");
        }
    })
    .catch((error) => showErrorModal("Error: Something went wrong"))

});

//Search for users
let searchForm = document.querySelector("#searchForm");
let mySearchText = document.querySelector("#searchText");
let searchResultContainer = document.querySelector("#searchResult");

searchForm.addEventListener("submit", function(event) {

    event.preventDefault();
    searchForUsers();

});

function searchForUsers() {

    showPage("#loading");

    if (mySearchText.value === "") {
        writeErrorMessage("Please enter something in the search field to search");
        return;
    }
    
    fetch(`endpoints/user.php?name=${mySearchText.value}`)
    .then((res) => res.text())
    .then((data) => { 
        if (data) {
            let jsonData = JSON.parse(data);
            clearChildElements(searchResultContainer);
            for (user of jsonData.users) { 
                populateSearchResult(user); 
            }
            showPage("#search")
        }
        else {
            writeErrorMessage("No users found");
        }
    })
    .catch((error) => {
        writeErrorMessage("Something went wrong");
    });

}

function populateSearchResult(user) {

    let myDiv = document.createElement("div");
    myDiv.classList.add("flexboxSearchContainer");
    myDiv.classList.add("profileSearchHover")
    let myImg = document.createElement("img");
    myImg.setAttribute("alt", "user profile picture");
    myImg.setAttribute("src", `images/profile/${user.id}${user.profileFiletype}`);
    myImg.classList.add("flexboxSearchImage");
    myDiv.appendChild(myImg);
    let myP = document.createElement("p");
    myP.classList.add("profileNameHover");
    let myStrong = document.createElement("strong");
    myStrong.textContent = user.name;
    myP.appendChild(myStrong);
    myDiv.appendChild(myP);

    myDiv.addEventListener("click", function() {
        loadLibrary(user.id);
    });

    searchResultContainer.appendChild(myDiv);

}

//News Feed
let myWall = document.querySelector("#wall");
function getNewsFeed() {

    showPage("#loading");

    fetch(`endpoints/follow.php?uid=${FBID}`)
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            clearChildElements(myWall);
            let myData = JSON.parse(data);
            for (image of myData.images) {
                createFeedElement(image);
            }
            showPage("#wall");
        }
        else {
            writeErrorMessage("You are not following anyone with uploaded images");
        }
    })
    .catch((error) => writeErrorMessage("Something went wrong when fetching your news feed, please try again"))
}

function createFeedElement(image) {
    
    let myDiv = document.createElement("div");
    myDiv.classList.add("newsPost");
    let myProfileImg = document.createElement("img");
    myProfileImg.classList.add("newsProfilePicture");
    myProfileImg.setAttribute("alt", "user profile picture");
    myProfileImg.setAttribute("src", `images/profile/${image.posterID}${image.profileFiletype}`)
    myDiv.appendChild(myProfileImg);
    let myP = document.createElement("p");
    let myStrong = document.createElement("strong");
    myStrong.textContent = image.posterName;
    myP.appendChild(myStrong);
    myDiv.appendChild(myP);
    let myBR = document.createElement("br");
    myDiv.appendChild(myBR);
    let myImgDiv = document.createElement("div");
    myImgDiv.classList.add("newsPictureContainer");
    let myImg = document.createElement("img");
    myImg.classList.add("newsPicture");
    myImg.setAttribute("alt", "image uploaded by user");
    myImg.setAttribute("src", `images/uploaded/${image.imageID}${image.filetype}`);
    myImgDiv.appendChild(myImg);
    myDiv.appendChild(myImgDiv);
    myWall.appendChild(myDiv);

    myImgDiv.addEventListener("click", function() {            
        let myImage = JSON.parse(`{ "id" : "${image.imageID}", "filetype" : "${image.filetype}" }`);
        openModal(image.posterID, image.posterName, myImage);
    });

}

//load library
let imageContainer = document.querySelector("#libraryGallery");
let followButtons = document.querySelector("#followButtons");
let followButton = document.querySelector("#follow");
let unfollowButton = document.querySelector("#unfollow");
let targetID;
function loadLibrary(IDToLoad) {

    showPage("#loading");

    fetch(`endpoints/image.php?uid=${IDToLoad}`)
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            let myData = JSON.parse(data);
            populateLibrary(myData);
            showPage("#library");
        }
        else {
            populateEmptyLibrary(IDToLoad);
        }
    })
    .catch((error) => showErrorModal("Error: Something went wrong, please try again1"));

}

function populateLibrary(data) { 

    populateLibraryUserInfo(data.uid, data.name, data.profileFiletype);

    clearChildElements(imageContainer);
    for (image of data.images) {
        createImageElement(data.uid, data.name, image);
    }

}

function populateEmptyLibrary(IDToLoad) {

    fetch(`endpoints/user.php?id=${IDToLoad}`)
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            let myData = JSON.parse(data);
            populateLibraryUserInfo(myData.id, myData.name, myData.profileFiletype);
            showPage("#library");
        }
        else {
            writeErrorMessage("User not found");
        }
    })
    .catch((error) => showErrorModal("Error: Something went wrong, please try again2"));

    clearChildElements(imageContainer);

    let myh1 = document.createElement("h1");
    myh1.textContent = "This user has no images yet";
    imageContainer.appendChild(myh1);

}

function populateLibraryUserInfo(id, name, filetype) {

    document.querySelector("#userProfilePicture").src = `images/profile/${id}${filetype}`;
    document.querySelector("#userName").textContent = name;
    if (id === FBID) {
        followButtons.classList.add("hidden");
    }
    else {
        followButtons.classList.remove("hidden");
        targetID = id;
        fetch(`endpoints/follow.php?uid=${FBID}&targetid=${targetID}`)
        .then((res) => {
            if (res.status === 200) {
                unfollowButton.classList.remove("hidden");
                followButton.classList.add("hidden");
            }
            else if (res.status === 204) {
                followButton.classList.remove("hidden");
                unfollowButton.classList.add("hidden");
            }
        })
        .catch((error) => showErrorModal("Error: Could not find follower information"));
    }

}

function createImageElement(uid, name, image) {

    let myDiv = document.createElement("div");
    myDiv.classList.add("flexboxImageContainer");
    let myImg = document.createElement("img");
    myImg.setAttribute("alt", "Image uploaded by user");
    myImg.setAttribute("src", `images/uploaded/${image.id}${image.filetype}`);
    myImg.classList.add("flexboxImage");
    myDiv.appendChild(myImg);

    myDiv.addEventListener("click", function() {
        openModal(uid, name, image);
    });

    imageContainer.appendChild(myDiv);

}

followButton.addEventListener("click", function() {

    let formData = new FormData();
    formData.append("uid", FBID);
    formData.append("targetid", targetID);

    fetch("endpoints/follow.php", {
        method: "POST",
        body: formData
    })
    .then((res) => {
        if (res.status === 201) {
            followButton.classList.add("hidden");
            unfollowButton.classList.remove("hidden");
        }
        else {
            showErrorModal("Error: Something went wrong, please try again");
        }
    })
    .catch((error) => showErrorModal("Error: Something went wrong, please try again"))
    
});

unfollowButton.addEventListener("click", function() {

    let formData = new URLSearchParams(); //URLSearchParams uses x-www-form-urlencoded which is necessary for this part of the API (FormData uses form-data which is incompatible with the backend code)
    formData.append("uid", FBID);
    formData.append("targetid", targetID);

    fetch("endpoints/follow.php", {
        method: "DELETE",
        body: formData
    })
    .then((res) => {
        if (res.status === 200) {
            unfollowButton.classList.add("hidden");
            followButton.classList.remove("hidden");
        }
        else {
            showErrorModal("Error: Something went wrong, please try again")
        }
    })
    .catch((error) => showErrorModal("Error: Something went wrong, please try again"))
    
});


//Modal logic
let myModal = document.querySelector("#modal");
let commentBox = document.querySelector("#modalComments");
let openImageID;

function openModal(uid, name, image) {
    openImageID = image.id;
    document.querySelector("#modalImage").src = `images/uploaded/${image.id}${image.filetype}`;
    document.querySelector("#modalPosterName").textContent = name;

    fetch(`endpoints/user.php?id=${uid}`)
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            let myData = JSON.parse(data);
            document.querySelector("#modalPosterImage").src = `images/profile/${uid}${myData.profileFiletype}`;
        }
        else {
            showErrorModal("Error: User does not exist");
            return;
        }
    })
    .catch((error) => {
        showErrorModal("Error: Could not find user");
        return;
    })

    clearChildElements(commentBox);

    fetch(`endpoints/comment.php?iid=${image.id}`)
    .then((res) => res.text())
    .then((data) => { 
        if (data) {
            let myData = JSON.parse(data);
            for (comment of myData.comments) {
                insertComment(comment);
            }
        }
    })
    .catch((error) => {
        showErrorModal("Error: Could not load comments");
        return;
    })

    document.querySelector("#modalWriteComment").value = "";

    myModal.classList.remove("hidden");

}

function insertComment(comment, filetype) {

    let myDiv = document.createElement("div");
    myDiv.classList.add("commentContainer");
    let myImg = document.createElement("img");
    myImg.setAttribute("alt", "user profile picture");
    filetype == null ? myImg.setAttribute("src", `images/profile/${comment.uid}${comment.profileFiletype}`) : myImg.setAttribute("src", `images/profile/${comment.uid}${filetype}`);
    myDiv.appendChild(myImg);
    let myP = document.createElement("p");
    let myStrong = document.createElement("strong");
    myStrong.textContent = comment.name ? comment.name : username;
    myP.appendChild(myStrong);
    let mySpan = document.createElement("span");
    mySpan.textContent = `: ${comment.comment}`;
    myP.appendChild(mySpan);
    myDiv.appendChild(myP);

    commentBox.appendChild(myDiv);

}

document.querySelector("#modalPostCommentButton").addEventListener("click", submitComment);

let commentForm = document.querySelector("#commentForm");
let myCommentText = document.querySelector("#modalWriteComment");

commentForm.addEventListener("submit", function(event) {

    event.preventDefault();
    submitComment();

});

function submitComment() {

    if (myCommentText.value === "") {
        showErrorModal("Error: Please write a comment before posting");
        return;
    }

    let formData = new FormData();
    formData.append('uid', FBID);
    formData.append('iid', openImageID);
    formData.append('comment', myCommentText.value);

    fetch("endpoints/comment.php", {
        method: "POST",
        body: formData
    })
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            let myData = JSON.parse(data);
            fetch(`endpoints/user.php?id=${myData.uid}`)
            .then((res) => res.text())
            .then((userData) => {
                if (userData) {
                    myCommentText.value = "";
                    let myUserData = JSON.parse(userData);
                    insertComment(myData, myUserData.profileFiletype);
                }
                else {
                    showErrorModal("Error: could not find user data");
                }
            })
            .catch((error) => showErrorModal("Error: could not find user data"))
        }
        else {
            showErrorModal("Error: Comment data is invalid");
        }
    })
    .catch((error) => showErrorModal("Error: Could not post comment"));

}

window.addEventListener("click", function(event) {

    if (event.target == modal) {
        myModal.classList.add("hidden");
    }

});


//Settings
let myFileDropper = document.querySelector("#fileDropper");
let myThumbnail = document.querySelector("#settingsThumbnail");
let nameTextBox = document.querySelector("#newUsername");
let settingsButton = document.querySelector("#updateSettings");
let fileToUpload;

myFileDropper.addEventListener("dragover", function(event) {
    
    event.stopPropagation();
    event.preventDefault();
    event.dataTransfer.dropEffect = 'copy'; 

});

myFileDropper.addEventListener("drop", function(event) {

    event.stopPropagation();
    event.preventDefault();

    let files = event.dataTransfer.files;

    for (file of files) {
        
        if (file.size > 500000) {
            showErrorModal("Error: Image too large, please use an image that is less than 500kb");
            return;
        }

        let reader = new FileReader();
        reader.onload = (function() {

            let imgResult = reader.result;
            let imgArray = imgResult.split(":");
            let imgMime = imgArray[1].split(";")[0];
            if (imgMime !== "image/png" && imgMime !== "image/jpeg") {

                return;
            }
            myThumbnail.setAttribute("src", reader.result);
            fileToUpload = imgArray[1].split(",")[1];
            
        });
        reader.readAsDataURL(file);
    }
    

});

settingsButton.addEventListener("click", function() {

    let formData = new URLSearchParams(); //URLSearchParams uses x-www-form-urlencoded which is necessary for this part of the API (FormData uses form-data which is incompatible with the backend code)
    formData.append("uid", FBID);
    formData.append("name", nameTextBox.value);
    formData.append("image", fileToUpload);

    fetch("endpoints/user.php", {
        method: "PUT",
        body: formData
    })
    .then((res) => res.text())
    .then((data) => {
        if (data) {
            let myData = JSON.parse(data);
            username = myData.name;
            loadLibrary(FBID);
        }
        else {
            showErrorModal("Error: Could not update settings, is your image of type png or jpg?");
        }
    })
    .catch((error) => showErrorModal("Failed to update settings"))

});


// Error Modal

let errorModal = document.querySelector("#errorModal");
let errorModalContent = document.querySelector("#errorModalContent");
let errorModalMessage = document.querySelector("#errorModalMessage");
let errorModalMessage2 = document.querySelector("#errorModalMessage2");

function showErrorModal(message) {
    errorModalMessage.textContent = message;
    errorModal.classList.remove("hidden");
}

window.addEventListener("click", function(event) {

    if (event.target == errorModal || event.target == errorModalContent || event.target == errorModalMessage || event.target == errorModalMessage2) {
        errorModal.classList.add("hidden");
    }

});


// Helper functions
function writeErrorMessage(message) {

    document.querySelector("#errorMessage").textContent = message;
    showPage("#error");

}

function clearChildElements(parentElement) {

    while (parentElement.firstChild) {
        parentElement.removeChild(parentElement.firstChild);
    }

}
