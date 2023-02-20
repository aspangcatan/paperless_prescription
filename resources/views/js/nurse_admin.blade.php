<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const nurses_modal = new bootstrap.Modal(document.getElementById('nurses_modal'));
    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    let page = 0;

    $(document).ready(function () {

        $("#btn_save").click(function () {
            let stations = "";
            for (let i = 0; i < STATIONS.length; i++) {
                if ($("input[name='station']:eq(" + i + ")").is(":checked")) {
                    stations += $("input[name='station']:eq(" + i + ")").next().text().trim() + ",";
                }
            }
            stations = stations.substring(0, stations.length - 1);

            updateNurseStation({
                id: $("#btn_save").val(),
                stations: stations
            });
        });

        populateStations();
        getNurses();
    });

    function updateNurseStation(params){
        nurses_modal.toggle();
        $("#spinner").removeClass("d-none");
        fetch('{{ route('update_nurse_station') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": $("meta[name='csrf-token']").attr('content')
            },
            body: JSON.stringify(params)
        })
            .then(res => res.json())
            .then(data => {
                $("#spinner").addClass("d-none");
                if (data.code == 500) {
                    $("#error_message").text(data.message);
                    error_modal.toggle();
                    return;
                }

                $("#success_message").text(data.message);
                success_modal.toggle();
                getNurses();
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function getNurses() {
        const filter_name = "";
        fetch("{{ url('user/get/nurses') }}?name=" + filter_name +
            "&page=" + page, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                console.log(data)
                $("#nurse_list").empty();
                if (data.results.length < 1) {
                    $("#nurse_list").append("<tr><td colspan='5'>No record found</td></tr>");
                    return;
                }

                data.results.forEach(it => {
                    const stations = (it.stations) ? it.stations : "";
                    const tr = `<tr id='user_id_` + it.id + `'>
                                <td>` + it.fname + `</td>
                                <td>` + it.mname + `</td>
                                <td>` + it.lname + `</td>
                                <td>` + it.suffix + `</td>
                                <td>` + stations + `</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewDetails(` + it.id + `)">
                                        <i class="bi bi-gear"></i>
                                    </button>
                                </td>
                            </tr>`;

                    $("#nurse_list").append(tr);
                });
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function viewDetails(id) {
        $("#btn_save").val(id);

        const fname = $("#user_id_" + id + " td:eq(0)").text().trim();
        const mname = $("#user_id_" + id + " td:eq(1)").text().trim();
        const lname = $("#user_id_" + id + " td:eq(2)").text().trim();
        const suffix = $("#user_id_" + id + " td:eq(3)").text().trim();
        const stations = $("#user_id_" + id + " td:eq(4)").text().trim();

        $("#fname").val(fname);
        $("#mname").val(mname);
        $("#lname").val(lname);
        $("#suffix").val(suffix);
        $("input[name='station']").prop("checked", false);

        const _stations = stations.split(",");
        for (let i = 0; i < _stations.length; i++) {
            const index = STATIONS.indexOf(_stations[i]);
            if (index < 0) continue;

            $("input[name='station']:eq(" + index + ")").prop("checked", true);
        }

        nurses_modal.toggle();
    }

    function populateStations() {
        STATIONS.forEach(it => {
            $("#stations").append(`
<div class="col-md-12">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="station">
      <label class="form-check-label" for="flexCheckChecked">
        ` + it + `
      </label>
    </div>
</div>`);

        });
    }
</script>
