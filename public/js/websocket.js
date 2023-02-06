const ws = new WebSocket("ws://localhost:2020");
const station = $("#user_station").val().trim();
const key = $("#user_key").val().trim();

ws.onopen = function () {
    console.log("Websocket client connected...");
    const data = {
        action: "LOGIN",
        body: {
            station: station,
            key: key
        }
    }

    ws.send(JSON.stringify(data));
}

ws.onmessage = function (message) {
    const action = JSON.parse(message.data).action;
    const body = JSON.parse(message.data).body;

    switch (action) {
        case "LOGIN":
            console.log(body.message);
            break;
        case "INSERT PRESCRIPTION":
            //IF NURSE
            console.log(body.message);
            getPrescriptions();
            break;
        case "LOGOUT":

            break;
    }
}

ws.onclose = function () {

}

function sendWebsocketServer(data) {
    ws.send(JSON.stringify(data));
}
