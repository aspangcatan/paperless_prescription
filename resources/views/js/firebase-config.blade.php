<script src="https://www.gstatic.com/firebasejs/9.16.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.16.0/firebase-database-compat.js"></script>
<script>
    let notification_counter = 0;
    let speaker = new SpeechSynthesisUtterance();
    speaker.text = "New Request!";

    toastr.options = {
        "positionClass": "toast-bottom-right",
    }

    const firebaseConfig = {
        apiKey: "AIzaSyC81XGWV6Ab6wraTdTkj7hx-9g1DSuFKSQ",
        authDomain: "centralized-socket.firebaseapp.com",
        databaseURL: "https://centralized-socket-default-rtdb.asia-southeast1.firebasedatabase.app",
        projectId: "centralized-socket",
        storageBucket: "centralized-socket.appspot.com",
        messagingSenderId: "247423105643",
        appId: "1:247423105643:web:037e3f9b3777e4513d8f2e",
        measurementId: "G-G8FDQMD142"
    };

    firebase.initializeApp(firebaseConfig);

    function insert(child, data, callback) {
        firebase.database().ref(child).push().set(data, function (err) {
            if (err) {
                callback("Something went wrong");
            } else {
                callback("Success");
            }
        });
    }

    function observe(child, callback) {
        firebase.database().ref(child)
            .on('child_added', (data) => {
                callback(data)
            });
    }

    function deleteChild(child) {
        firebase.database().ref(child).remove();
    }

    function formatFullAge(birthdate,currentdate){
        var today = currentdate;
        var birthdateMoment = moment(birthdate, "MM/DD/YYYY");

        var years = today.diff(birthdateMoment, 'years');
        birthdateMoment.add(years, 'years');

        var months = today.diff(birthdateMoment, 'months');
        birthdateMoment.add(months, 'months');

        var days = today.diff(birthdateMoment, 'days');

        var ageString = years + " years " + months + " months " + days + " days";

        return ageString;
    }
</script>
