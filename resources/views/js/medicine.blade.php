<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const medicine_modal = new bootstrap.Modal(document.getElementById('medicine_modal'));

    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    $(document).ready(function () {

        $("#btn_search_medicine").click(function () {
            getMedicine();
        });

        $("#btn_add_medicine").click(function () {
            clearForms();
            medicine_modal.toggle();
            $("#btn_save_medicine").val(0);
        });

        $("#btn_save_medicine").click(function () {
            const description = $("#medicine_description").val().trim();
            const unit = $("#medicine_unit").val().trim();
            const type = $("#medicine_type").val();
            const status = ($("input[name='medicine_status']").is(":checked")) ? 1 : 0;

            let is_valid = true;
            $("#medicine_modal .is-invalid").removeClass("is-invalid");
            $("#medicine_modal .error_container").addClass("d-none");
            $("#medicine_modal .error_list").empty();

            if (description == "") {
                is_valid = false;
                $("#medicine_modal .error_list").append("<li>Description is required</li>");
            }

            if (unit == "") {
                is_valid = false;
                $("#medicine_modal .error_list").append("<li>Unit is required</li>");
            }

            if (type == "") {
                is_valid = false;
                $("#medicine_modal .error_list").append("<li>Type is required</li>");
            }

            if (!is_valid) {
                $("#medicine_modal .error_container").removeClass("d-none");
                return;
            }

            const params = {
                id: $(this).val(),
                description: description,
                unit: unit,
                type: type,
                status: status
            }

            console.log(params);
            insertMedicine(params);
        });

        $("#search_medicine").keypress(function (e) {
            if (e.which == 13) {
                getMedicine();
            }
        });

        getMedicine();
    });

    function viewMedicine(id) {
        clearForms();

        const description = $("#medicine_id_" + id + " td:eq(0)").text();
        const unit = $("#medicine_id_" + id + " td:eq(1)").text();
        const type = $("#medicine_id_" + id + " td:last-child").text();
        const status = $("#medicine_id_" + id + " td:eq(3)").text();

        $("#btn_save_medicine").val(id);
        $("#medicine_description").val(description);
        $("#medicine_unit").val(unit);
        $("#medicine_type").val(type);

        if (status == "Active") {
            $("input[name='medicine_status']").prop("checked", true);
        } else {
            $("input[name='medicine_status']").prop("checked", false);
        }
        medicine_modal.toggle();
    }

    function getMedicine() {
        const key = $("#search_medicine").val().trim();
        $("#medicine_list").empty();
        fetch("{{ url('medicine/get/master') }}?medicine=" + key, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.results.length < 1) {
                    $("#medicine_list").append("<tr><td class='text-center fs-5' colspan='4'>No record found</td></tr>");
                    return;
                }

                for (let i = 0; i < data.results.length; i++) {
                    const it = data.results[i];
                    let status = "Inactive"
                    let bg = "badge bg-danger text-white fw-bold";

                    if (it.status == 1) {
                        status = "Active";
                        bg = "badge bg-success text-white fw-bold"
                    }

                    const tr = `
                        <tr id="medicine_id_` + it.id + `">
                           <td>` + it.description + `</td>
                           <td>` + it.unit + `</td>
                           <td>` + PRESCRIPTION_TYPES[it.type] + `</td>
                           <td><span class="` + bg + `">` + status + `</span></td>
                           <td>
                                <button class="btn btn-sm btn-success" onclick="viewMedicine('` + it.id + `')">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                           </td>
                           <td class="d-none">` + it.type + `</td>
                        </tr>`;
                    $("#medicine_list").append(tr);
                }
                data.results.forEach(it => {

                });
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function insertMedicine(params) {
        $("#spinner").removeClass("d-none");
        medicine_modal.toggle();
        fetch('{{ route('insert_medicine') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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

                getMedicine();
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function editMedicine(params) {
        prescription_modal.toggle();
        $("#spinner").removeClass("d-none");
        fetch('{{ route('insert_prescription') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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
                getPrescriptions();

                //SEND NOTIFICATION TO NURSE
                if (params.patient.type == "2") {
                    sendWebsocketServer({
                        action: "NOTIFY ROLE",
                        body: {role: "ICC"}
                    })
                } else {
                    sendWebsocketServer({
                        action: "NOTIFY STATION",
                        body: {station: params.patient.station}
                    })
                }
            }).catch(err => {
            $("#spinner").addClass("d-none");
            $("#error_message").text(err);
            error_modal.toggle();
        });
    }

    function clearForms() {
        $("#medicine_description").val("");
        $("#medicine_unit").val("");
        $("#medicine_type").val("");
        $("input[name='medicine_status']").prop("checked", true);
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
