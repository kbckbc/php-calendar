////////////////////////////
// control calendar
////////////////////////////
function clickGoToday() {
    currMonth = new Month(getYy(), getMm());
    drawCalendar(currMonth);
    checkLogin();
}

function clickPrevMonth() {
    currMonth = currMonth.prevMonth();
    drawCalendar(currMonth);
    checkLogin();
}

function clickNextMonth() {
    currMonth = currMonth.nextMonth();
    drawCalendar(currMonth);
    checkLogin();
}

function clickGotoMonth(yy,mm) {
    modalMypage.style.display = "none";
    month = parseInt(mm) - 1;
    currMonth = new Month(yy,month);
    drawCalendar(currMonth);
    checkLogin();
}	

////////////////////////////
// control event
////////////////////////////
function clickSelectTime() {
    document.getElementById('inputEventTime').value = document.getElementById('selectEventTimeCombo').value;
}


function clickAddEditEvent() {
    let event_id = document.getElementById('inputEventId').value;
    let day = document.getElementById('inputEventDay').value;
    let title = document.getElementById('inputEventTitle').value;
    let content = document.getElementById('inputEventContent').value;
    let time = document.getElementById('inputEventTime').value;


    // console.log(`clickAddEditEvent:${event_id}, ${day}, ${title}, ${content}, ${time}`);
    postBody = {
        token:getToken(),
        event_id:event_id,
        day:day,
        title:title,
        content:content,
        time:time
    }		

    modalEvent.style.display = "none";

    post("write.php", postBody)
    .then(data => {
        if(data['write'] == 1) {
            drawEvents(currMonth, day);

            // redraw when the modal is open
            if( document.getElementById('divWholeEventModal').style.display == 'block' ) {
                clickOpenWholeEventModal(day);
            }
        }
        else {
            alert(data['msg']);
        }

    })
    .catch(error => console.log(error));		
}	

function clickEditEvent(day, username, title, content, time, event_id) {
    // console.log(`clickEditEvent,${event_id},${day},${title},${content},${time}`);
    clickOpenEventModal(day, username, title, content, time, event_id);
}

function deleteEvent(yymmdd, event_id) {
    if (confirm("Want to delete?") == true) {
        postBody = {
            token:getToken(),
            event_id:event_id
        }

        post("delete.php", postBody)
        .then(data => {
            if(data['delete'] == 1) {
                drawEvents(currMonth, yymmdd);
                if( modalWholeEvent.style.display == "block") {
                    clickOpenWholeEventModal(yymmdd);
                }
            }
            else {
                alert(data['msg']);
            }

        })
        .catch(error => console.log(error));		
    }		
}

////////////////////////////
// draw sharing
////////////////////////////
function clickAddSharing(taker_id) {
        postBody = {
            token:getToken(),
            taker_id:taker_id
        }

        post("s_add.php", postBody)
        .then(data => {
            document.getElementById("divSharingModal").style.display = "none";

            if(data['add'] == 1) {
                drawSharing();
            }
            else {
                alert(data['msg']);
            }

        })
        .catch(error => console.log(error));		
}	

function deleteSharing(sharing_id) {
    if (confirm("Want to delete?") == true) {
        postBody = {
            token:getToken(),
            sharing_id:sharing_id
        }

        post("s_delete.php", postBody)
        .then(data => {
            console.log(data);
            if(data['delete'] == 1) {
                drawSharing();
            }
            else {
                alert(data['msg']);
            }

        })
        .catch(error => console.log(error));		
    }		
}	

////////////////////////////
// draw calendar
////////////////////////////
function drawCalendar(currMonth) {
    let weeks = currMonth.getWeeks();
    let addHtml = "";

    divCalendar = document.getElementById("divCalendar");
    divCalendar.innerHTML = "";

    spanCurrMonth = document.getElementById("spanCurrMonth");
    spanCurrMonth.innerHTML = "<code>" + currMonth.year + "-" + (currMonth.month + 1) + "</code>";

    let today = getYymmdd(new Date());
    if( divCalendar != null) {
        addHtml += "<table class='pure-table pure-table-bordered center'>";
        addHtml += "<tr>";
        addHtml += "<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thr</th><th>Fri</th><th>Sat</th>";
        addHtml += "</tr>";
        for(let w of weeks) {

            addHtml += "<tr>";
            let days = w.getDates();
            for(let d of days) {
                let yymmdd = getYymmdd(d);
                addHtml += "<td>";
                addHtml += `<div class='daybox' onclick='clickOpenEventModal(${yymmdd})'>`; 
                // if a date is today, give it a class for background
                if( yymmdd == today) {
                    addHtml += `<div id='day${yymmdd}' class='today'>${d.getDate()}</div>`;
                }
                else {
                    addHtml += `<div id='day${yymmdd}'>${d.getDate()}</div>`;
                }

                addHtml += `<ul id='list${yymmdd}' class='eventlist'>`;

                addHtml += "</ul>";
                addHtml += "</div>"; // for daybox
                addHtml += "</td>";
            }
            addHtml += "</tr>";
        }

        addHtml += "</table>";
    }

    divCalendar.innerHTML += addHtml;
}	

function drawEvents(currMonth, yymmdd ='') {
    if( getToken() == '' || getToken() == 'undefined') {
        return;
    }

    // clear before drawing
    let firstDay = '';
    let lastDay = '';
    let postBody = {};

    // if date is not specified, cover a month
    if( yymmdd == '') {
        for(let w of currMonth.getWeeks()) {
            for(let d of w.getDates()) {
                if( firstDay == '') {
                    firstDay = getYymmdd(d);
                }
                lastDay = getYymmdd(d);
            }
        }
        postBody = {
            from:firstDay,
            to:lastDay			
        };
    }
    // if date is specified, cover a day
    else {
        postBody = {
            from:yymmdd,
            to:yymmdd
        };

        let ul = document.getElementById('list' + yymmdd);
        while(ul.firstChild) { 
            ul.removeChild(ul.firstChild);
        }			
    }

    post("read.php", postBody)
    .then(data => {
        // console.log(data);
        if(data['read'] == 1) {
            let cnt = 0;
            let prevYymmdd = '';
            // draw all events
            for(let event of data['events']) {
                if(prevYymmdd == '' || prevYymmdd != event['yymmdd']) {
                    prevYymmdd = event['yymmdd'];
                    cnt = 0;
                }

                // draw more events
                if( cnt == 2) {
                    cnt += 1;
                    listDom = document.getElementById('list' + event['yymmdd']);
                    listDom.innerHTML += `<li><mark><a href="#" onclick='javascript:clickOpenWholeEventModal(${yymmdd}); event.stopPropagation();'>more events</a></mark></li>`;
                    continue;
                }
                // draw events
                else if(cnt < 2) {
                    cnt += 1;
                    // console.log(event);
                    event_id = event['event_id'];
                    username = event['username'];
                    yymmdd = event['yymmdd'];
                    title = event['title'];
                    content = event['content'];
                    hhmi = event['hhmi'];

                    username_cut = username.substr(0, username.indexOf('('));
                    trashOrShared = `<a href='#' onclick='javascript:deleteEvent(${yymmdd},${event_id}); event.stopPropagation();'><i class="fa fa-trash-o"></i></a>`;
                    if( username != document.getElementById('spanLoginMessage').innerHTML ) {
                        trashOrShared = `<sub> by ${username_cut}</sub>`;
                    }
                    listDom = document.getElementById('list' + event['yymmdd']);
                    listDom.innerHTML += `<li id='event${event_id}'><mark><a href="#" onclick='javascript:clickEditEvent("${yymmdd}","${username}","${title}","${content}","${hhmi}",${event_id}); event.stopPropagation();'>${hhmi} ${title}</a>${trashOrShared}</mark></li>`;
                }
            }
        }
    })
    .catch(error => console.log(error));
}


function drawSharing() {
    let ul = document.getElementById('listSharing');
    while(ul.firstChild) { 
        ul.removeChild(ul.firstChild);
    }			

    if( getToken() == '' || getToken() == 'undefined') {
        return;
    }

    post("s_read_sharing.php", postBody)
    .then(data => {
        if(data['read'] == 1) {

            for(let item of data['sharing']) {
                listDom = document.getElementById('listSharing');
                listDom.innerHTML += `<li id='sharing${item['sharing_id']}'><small>${item['nickname']}</small><a href='#' onclick='javascript:deleteSharing(${item['sharing_id']}); event.stopPropagation();'><i class="fa fa-trash-o"></i></a></li>`;
            }
        }
    })
    .catch(error => console.log(error));
}

function drawAvailableUsers() {
    post("s_read_users.php", postBody)
    .then(data => {
        let list = document.getElementById('listAvailableUsers');
        while(list.firstChild) { 
            list.removeChild(list.firstChild);
        }		

        if(data['read'] == 1) {
            for(let item of data['available']) {
                list.innerHTML += `<li><a href='#' onclick='javascript:clickAddSharing(${item['user_id']})'>${item['nickname']}</a></li>`;
            }
        }
        else {
            list.innerHTML += `<li>There's no user available</li>`;
        }
    })
    .catch(error => console.log(error));
}	



////////////////////////////
// about login and signup
////////////////////////////

// call login.php to check username is login or not
// if logged in, logout and welcome text
// if not, login and signup button
function checkLogin(username="", password="") {
    postBody = {};

    if(username != "") {
        postBody = {
            loginUsername:username,
            loginPassword:password
        }
    }

    post("login.php", postBody)
    .then(data => {
        modalLogin.style.display = "none";
        // when login success
        document.getElementById('spanLoginToken').innerHTML = data['token'];
        document.getElementById('spanLoginMessage').innerHTML = data['msg'];
        if(data['logged'] == 1) {
            document.getElementById('btnLoginModal').style.display = "none";
            document.getElementById('btnSignupModal').style.display = "none";
            document.getElementById('spanAddSharing').style.display = "inline";
            document.getElementById('btnMypage').style.display = "inline";
            document.getElementById('btnLogout').style.display = "inline";
        }
        else {
            document.getElementById('btnLoginModal').style.display = "inline";
            document.getElementById('btnSignupModal').style.display = "inline";
            document.getElementById('spanAddSharing').style.display = "none";
            document.getElementById('btnMypage').style.display = "none";
            document.getElementById('btnLogout').style.display = "none";
        }
        drawEvents(currMonth);
        drawSharing();
    })
    .catch(error => console.log(error));
}

function clickLogin() {
    username = document.getElementById('loginUsername').value,
    password = document.getElementById('loginPassword').value

    checkLogin(username, password);
}	

function clickSignup() {
    let username = document.getElementById('signupUsername').value;
    let password = document.getElementById('signupPassword').value;
    let nickname = document.getElementById('signupNickname').value;
    post("signup.php", {
        signupUsername:username,
        signupPassword:password,
        signupNickname:nickname
    })
    .then(data => {
        modalSignup.style.display = "none";
        if(data['signup'] == 1) {
            checkLogin(username, password);
        }
        else {
            alert(data['msg']);
        }
    })
    .catch(error => console.log(error));
}

function clickLogout() {
    // alert('test');
    post("logout.php", {
    })
    .then(data => {
        if(data['logout'] == 1){
            initPage();
        }
        else {
            alert(data['msg']);
        }
    })		
    .catch(error => console.log(error));
}

////////////////////////////
// init page
////////////////////////////
// initPage: init function and automatically called when a page loaded
// currMonth: holding a current month
let currMonth;
function initPage() {
    clickGoToday();
}


////////////////////////////
// about modal functions
////////////////////////////
// Get the modal
let modalLogin = document.getElementById("divLoginMoal");
let modalSignup = document.getElementById("divSignupMoal");
let modalEvent = document.getElementById("divEventMoal");
let modalWholeEvent = document.getElementById("divWholeEventModal");
let modalSharing = document.getElementById("divSharingModal");
let modalMypage = document.getElementById("divMypageModal");


function clickOpenSharingModal() {
    drawAvailableUsers();		

    modalSharing.style.display = "block";		
}

function clickOpenMypageModal() {
    // drawMypageEventLists();	
    
    let ul = document.getElementById('listMypageEventLists');
    while(ul.firstChild) { 
        ul.removeChild(ul.firstChild);
    }	

    postBody = {
            from:'10000101',
            to:'30000101'
    };

    post("read.php", postBody)
    .then(data => {
        if(data['read'] == 1) {
            // draw all events
            for(let event of data['events']) {
                // console.log(event);
                event_id = event['event_id'];
                yymmdd = event['yymmdd'];
                yy = event['yy'];
                mm = event['mm'];
                username = event['username'];
                title = event['title'];
                content = event['content'];
                hhmi = event['hhmi'];

                username_cut = username.substr(0, username.indexOf('('));
                trashOrShared = '';
                if( username != document.getElementById('spanLoginMessage').innerHTML ) {
                    trashOrShared = `<sub> by ${username_cut}</sub>`;
                }

                listWholeEventDom = document.getElementById('listMypageEventLists');
                listWholeEventDom.innerHTML += `<li><a href='javascript:clickGotoMonth(${yy},${mm})'>${yymmdd}</a>, ${hhmi}, <strong>${title}</strong> ${trashOrShared}</li>`;
            }
        }
        else {
            listWholeEventDom = document.getElementById('listMypageEventLists');
            listWholeEventDom.innerHTML += `<li>There's no schedule!</li>`;
        }
    })
    .catch(error => console.log(error));		

    modalMypage.style.display = "block";		
}

function clickOpenLoginModal() {
    document.getElementById('loginUsername').value = '';
    document.getElementById('loginPassword').value = '';

    modalLogin.style.display = "block";
}
function clickOpenSignupModal() {
    document.getElementById('signupUsername').value = '';
    document.getElementById('signupPassword').value = '';
    document.getElementById('signupNickname').value = '';

    modalSignup.style.display = "block";
}
function clickOpenWholeEventModal(yymmdd) {
    document.getElementById('inputWholeEventDay').value = yymmdd;

    let ul = document.getElementById('listWholeEvent');
    while(ul.firstChild) { 
        ul.removeChild(ul.firstChild);
    }	

    postBody = {
            from:yymmdd,
            to:yymmdd
    };

    post("read.php", postBody)
    .then(data => {
        if(data['read'] == 1) {
            // draw all events
            for(let event of data['events']) {
                // console.log(event);
                event_id = event['event_id'];
                yymmdd = event['yymmdd'];
                username = event['username'];
                title = event['title'];
                content = event['content'];
                hhmi = event['hhmi'];

                username_cut = username.substr(0, username.indexOf('('));
                trashOrShared = `<a href='#' onclick='javascript:deleteEvent(${yymmdd},${event_id}); event.stopPropagation();'><i class="fa fa-trash-o"></i></a>`;
                if( username != document.getElementById('spanLoginMessage').innerHTML ) {
                    trashOrShared = `<sub> by ${username_cut}</sub>`;
                }

                listWholeEventDom = document.getElementById('listWholeEvent');
                listWholeEventDom.innerHTML += `<li id='event${event_id}'><mark><a href="#" onclick='javascript:clickEditEvent("${yymmdd}","${username}","${title}","${content}","${hhmi}",${event_id}); event.stopPropagation();'>${hhmi} ${title}</a>${trashOrShared}</mark></li>`;
            }
        }
    })
    .catch(error => console.log(error));		

    modalWholeEvent.style.display = "block";
}	
function clickOpenEventModal(day, username='', title='', content='', time='', event_id='') {
    
    if( document.getElementById('spanLoginMessage').innerHTML == 'Login please') {
        alert('Login please');
    }
    else {
        // if there is no input value, it will act as add function
        // if there are values including event_id, it will edit the event
        document.getElementById('inputEventId').value = event_id;
        document.getElementById('inputEventDay').value = day;
        document.getElementById('inputEventUsername').value = (username == '' ? document.getElementById('spanLoginMessage').innerHTML : username);
        document.getElementById('inputEventTitle').value = title;
        document.getElementById('inputEventContent').value = content;
        document.getElementById('inputEventTime').value = time;
        if( time == '') {
            document.getElementById('selectEventTimeCombo').value = '';
        }
        else {
            document.getElementById('selectEventTimeCombo').value = time;
        }

        if( event_id != '') {
            document.getElementById('btnAddEditEvent').value = 'Edit event!'
        }
        else {
            document.getElementById('btnAddEditEvent').value = 'Add event!'
        }

        // turn on edit button if the event belongs to logged user.
        if( username != '' && username != document.getElementById('spanLoginMessage').innerHTML) {
            document.getElementById('btnAddEditEvent').style.display = "none";
        }
        else {
            document.getElementById('btnAddEditEvent').style.display = "block";
        }

        modalEvent.style.display = "block";		
    }
}	


// when click x button on modal, close the modal
document.getElementsByClassName("close")[0].onclick = function() {
    modalLogin.style.display = "none";
}
document.getElementsByClassName("close")[1].onclick = function() {
    modalSignup.style.display = "none";
}
document.getElementsByClassName("close")[2].onclick = function() {
    modalWholeEvent.style.display = "none";
}	
document.getElementsByClassName("close")[3].onclick = function() {
    modalEvent.style.display = "none";
}
document.getElementsByClassName("close")[4].onclick = function() {
    modalSharing.style.display = "none";
}
document.getElementsByClassName("close")[5].onclick = function() {
    modalMypage.style.display = "none";
}

// when click outside of the modal, close the modal
window.onclick = function(event) {
    if (event.target == modalLogin) {
        modalLogin.style.display = "none";
    }
    else if (event.target == modalSignup) {
        modalSignup.style.display = "none";
    }
    else if (event.target == modalWholeEvent) {
        modalWholeEvent.style.display = "none";
    }			
    else if (event.target == modalEvent) {
        modalEvent.style.display = "none";
    }		
    else if (event.target == modalSharing) {
        modalSharing.style.display = "none";
    }		
    else if (event.target == modalMypage) {
        modalMypage.style.display = "none";
    }		

}


////////////////////////////
// binding events
////////////////////////////
// Bind the function to a page load
document.addEventListener("DOMContentLoaded", initPage, false); 
