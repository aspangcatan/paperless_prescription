<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/constant.js') }}?v={{ date('ymdhis') }}"></script>
<script src="{{ asset('js/prescription-ui.js') }}?v={{ date('ymdhis') }}"></script>
<script src="{{ asset('js/websocket.js') }}?v={{ date('ymdhis') }}"></script>
<script>
    let patient_id = "";
    let hospital_id = "";
    let page = 0;
    const disposition_modal = new bootstrap.Modal(document.getElementById('disposition_modal'));
    $(document).ready(function () {
        $("#btn_next").click(function () {
            page++;
            getPatients();
            $("#btn_prev").removeClass("d-none");
        });

        $("#btn_prev").click(function () {
            if (page == 0) return;
            if (page == 1) {
                $("#btn_prev").addClass("d-none");
            }
            page--;
            getPatients();
        });

        $("#btn_search_prescription").click(function () {
            page = 0;
            getPatients();
        });

        $("#btn_submit").click(function () {
            const disposition_status = $("#disposition_status").val();
            const disposition_date = $("#disposition_date").val();

            fetch("{{ route("set_patient_disposition") }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    id: patient_id,
                    hospital_id: hospital_id,
                    disposition_status: disposition_status,
                    disposition_date: disposition_date
                })
            })
                .then(res => res.json())
                .then(data => {
                    console.log(data);
                    getPatients();
                }).catch(err => {
                console.log(err);
            });
        });

        getPatients();
    });

    function setDisposition(id) {
        patient_id = id;
        hospital_id = $("#patient_" + id + " td:eq(0)").text().trim();
        disposition_modal.toggle();
    }

    function getPatients() {
        const firstname = $("#filter_firstname").val().trim();
        const lastname = $("#filter_lastname").val().trim();
        $("#patient_list").empty();
        $("#patient_list").append("<tr><td colspan='6'>Loading data, please wait ...</td></tr>");
        fetch("{{ url('/patient/get/list') }}?firstname=" + firstname +
            "&lastname=" + lastname +
            "&page=" + page, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#pagination_container").addClass("d-none")
                $("#patient_list").empty();
                $("#btn_next").addClass("d-none");
                if (data.results.length < 1) {
                    $("#patient_list").append("<tr><td colspan='6'>No record found</td></tr>");
                    return;
                }

                $("#pagination_container").removeClass("d-none")

                let max_visible = data.results.length;
                if (max_visible == 11) {
                    max_visible = 10;
                    $("#btn_next").removeClass("d-none");
                }

                $("#page_items_count").text(((page * 10) + 1) + " - " + ((page * 10) + max_visible));

                for (let i = 0; i < max_visible; i++) {
                    const it = data.results[i];
                    const fullName = `${it.lname}, ${it.fname} ${it.mname ? it.mname + ' ' : ''}${it.suffix ? it.suffix : ''}`;
                    let birthdate = moment(it.birthdate).format("MM/DD/YYYY");
                    const disposition_status = (it.disposition_status) ? it.disposition_status : "";
                    if (birthdate == "Invalid date")
                        birthdate = it.birthdate;

                    const tr = ` <tr id="patient_` + it.id + `">
                                <td>` + escapeCell(it.hospital_id) + `</td>
                                <td>` + escapeCell(fullName) + `</td>
                                <td>` + birthdate + `</td>
                                <td>` + escapeCell(it.sex) + `</td>
                                <td>` + escapeCell(disposition_status) + `</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="setDisposition(` + it.id + `)">
                                        <i class="bi bi-prescription"></i>
                                    </button>
                                </td>
                            </tr>`;

                    $("#patient_list").append(tr);
                }
            }).catch(err => {
            $("#patient_list").empty();
            $("#patient_list").append("<tr><td colspan='6'>Something went wrong, please refresh browser</td></tr>");
            console.log(err);
        });
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
