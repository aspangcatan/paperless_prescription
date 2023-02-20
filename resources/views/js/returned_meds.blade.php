<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    $(document).ready(function () {

        $("#btn_search_medicine").click(function () {
            getReturnedMeds();
        });

        $("#search_medicine").keypress(function (e) {
            if (e.which == 13) {
                getReturnedMeds();
            }
        });

        getReturnedMeds();
    });

    function getReturnedMeds() {
        const filtered_description = $("#filter_description").val().trim();
        const filtered_date = $("#filter_date").val();

        $("#medicine_list").empty();
        fetch("{{ url('returned_meds/get') }}?filter_description=" + filtered_description +
            "&filter_date=" + filtered_date, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.results.length < 1) {
                    $("#medicine_list").append("<tr><td class='text-center fs-5' colspan='10'>No record found</td></tr>");
                    return;
                }

                for (let i = 0; i < data.results.length; i++) {
                    const it = data.results[i];
                    const received_by = {
                        name: "",
                        date: ""
                    }

                    let button = `<button class="btn btn-sm btn-success" onclick="receiveMedicine(` + it.id + `)">ACCEPT</button>`;

                    if (it.received_by) {
                        received_by.name = it.received_by;
                        received_by.date = it.date_received;
                        button = "";
                    }

                    const tr = `
                        <tr>
                           <td>` + it.hospital_id + `</td>
                           <td>` + it.name + `</td>
                           <td>` + it.station + `</td>
                           <td>` + it.description + `</td>
                           <td>` + it.returned_amount + `</td>
                           <td>` + it.returned_by + `</td>
                           <td>` + it.date_returned + `</td>
                           <td>` + received_by.name + `</td>
                           <td>` + received_by.date + `</td>
                           <td>` + button + `</td>
                        </tr>`;
                    $("#medicine_list").append(tr);
                }
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function receiveMedicine(id) {
        if (confirm("Are you sure you want to receive this?")) {
            $("#spinner").removeClass("d-none");
            fetch("{{ route('accept_meds')}}", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({id: id})
            })
                .then(res => res.json())
                .then(data => {
                    $("#spinner").addClass("d-none");
                    if (data.code == 500) {
                        toastr.error(data.message, "Ooops");
                        return;
                    }

                    toastr.success(data.message, "Information");
                    getReturnedMeds();
                })
        }
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
