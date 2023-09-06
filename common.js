// getting date
function getDd() {
    return (new Date()).getDate();
}
function getMm() {
    return (new Date()).getMonth();
}
function getYy() {
    return (new Date()).getFullYear();
}
function getYymmdd(d) {
    date = [
    d.getFullYear(),
    ('0' + (d.getMonth() + 1)).slice(-2),
    ('0' + d.getDate()).slice(-2)
    ].join('');

    return date;
}

// AJAX call function
async function post(url, body, headers={}) {
    
    const options = {
        method:"POST",
        headers: {
            "Content-Type":"application/json",
            ...headers,
        },
        body: JSON.stringify(body)
    };
    const res = await fetch(url, options);
    const data = await res.json();
    if(res.ok) {
        return data;
    }
    else {
        throw Error(data);
    }
}

// when sending requestion, use this function to get a token
function getToken() {
    return document.getElementById('spanLoginToken').innerHTML;
}
