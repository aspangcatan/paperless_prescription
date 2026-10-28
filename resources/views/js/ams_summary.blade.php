<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/xlsx.bundle.js') }}"></script>
<script src="{{ asset('js/constant.js') }}?v={{ date('ymdhis') }}"></script>
<script src="{{ asset('js/prescription-ui.js') }}?v={{ date('ymdhis') }}"></script>
<script src="{{ asset('js/websocket.js') }}?v={{ date('ymdhis') }}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
    let filtered_date = "";
    $(document).ready(function () {

        $('input[name="datefilter"]').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        });

        $('input[name="datefilter"]').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        });

        $('input[name="datefilter"]').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
        });

        $("#btn_search").click(function () {
            getAmsSummary();
        });

        $("#btn_download").click(function () {
            generateExcel();
        });

        $("#search_medicine").keypress(function (e) {
            if (e.which == 13) {
                getAmsSummary();
            }
        });

    });

    function getAmsSummary() {
        const filtered_patient = $("#filter_patient").val().trim();
        const filtered_description = $("#filter_description").val().trim();
        const filter_type = $("#filter_type").val();
        filtered_date = $("#filter_date").val();
        let from_date = "";
        let to_date = "";

        if (filtered_date == "") {
            alert("Please specify date range");
            $("#ams_summary").empty();
            $("#ams_summary").append("<tr><td class='text-center fs-5' colspan='13'>No record found</td></tr>");
            return;
        }

        if (filtered_date) {
            from_date = moment(filtered_date.split("-")[0].trim(), "MM/DD/YYYY").format("YYYY-MM-DD");
            to_date = moment(filtered_date.split("-")[1].trim(), "MM/DD/YYYY").format("YYYY-MM-DD");
        }

        $("#ams_summary").empty();
        $("#ams_summary").append("<tr><td colspan='11' ding data, please wait ...</td></tr>");
        fetch("{{ url('ams/summary') }}?filter_description=" + filtered_description +
            "&filter_patient=" + filtered_patient +
            "&filter_type=" + filter_type +
            "&from_date=" + from_date +
            "&to_date=" + to_date, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#ams_summary").empty();
                if (data.results.length < 1) {
                    $("#ams_summary").append("<tr><td class='text-center fs-5' colspan='13'>No record found</td></tr>");
                    return;
                }

                for (let i = 0; i < data.results.length; i++) {
                    const it = data.results[i];
                    const approved_by = (it.approved_by) ? it.approved_by : "";
                    const date_approved = (it.date_approved) ? moment(it.date_approved).format("MM/DD/YYYY hh:mm:ss A") : "";
                    const tr = `
                        <tr>
                           <td>` + escapeCell(it.name) + `</td>
                           <td>` + escapeCell(it.age) + `</td>
                           <td>` + escapeCell(it.sex) + `</td>
                           <td>` + escapeCell(it.station) + `</td>
                           <td>` + escapeCell(it.physician) + `</td>
                           <td>` + moment(it.created_at).format("MM/DD/YYYY hh:mm:ss A") + `</td>
                           <td>` + escapeCell(it.attending_physician) + `</td>
                           <td class="fw-bold">` + escapeCell(it.antimicrobial) + `</td>
                           <td>` + escapeCell(approved_by) + `</td>
                           <td>` + escapeCell(it.days_approved) + `</td>
                           <td>` + date_approved + `</td>
                           <td>` + moment(it.start_date).format("MM/DD/YYYY") + `</td>
                           <td>` + moment(it.stop_date).format("MM/DD/YYYY") + `</td>
                        </tr>`;
                    $("#ams_summary").append(tr);
                }
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function generateExcel() {
        if (!filtered_date) {
            alert("Please fill in date range");
            return;
        }

        if ($("#ams_summary tr").length < 1) {
            alert("Nothing to generate");
            return;
        }

        let from_date = moment(filtered_date.split("-")[0].trim(), "MM/DD/YYYY");
        let to_date = moment(filtered_date.split("-")[1].trim(), "MM/DD/YYYY");

        let to_month_name = "";
        if (from_date.format("M") != to_date.format("M")) {
            to_month_name = to_date.format("MMMM");
        }

        const datas = [];
        let title = "For the Month of " + from_date.format("MMMM") + " " + from_date.format("D");
        if (to_month_name == "") {
            if (from_date.format("D") == to_date.format("D"))
                title += to_date.format(", YYYY");
            else {
                title += to_date.format("-D, YYYY");
            }
        } else
            title += " - " + to_month_name + " " + to_date.format("D, YYYY");
        const wb = XLSX.utils.book_new();
        datas.push([]);
        datas.push([{
            v: "CEBU SOUTH MEDICAL CENTER",
            t: "s",
            s: {
                alignment: {
                    vertical: "center",
                    horizontal: "center"
                },
                font: {
                    bold: true,
                    sz: "18"
                }
            }
        }]);
        datas.push([{
            v: "Antimicrobial Stewardship Program (AMS) Summary Report",
            t: "s",
            s: {
                alignment: {
                    vertical: "center",
                    horizontal: "center"
                },
                font: {
                    bold: true,
                    sz: "18"
                }
            }
        }]);
        //ADD SPACE
        datas.push([{
            v: title,
            t: "s",
            s: {
                alignment: {
                    vertical: "center",
                    horizontal: "center"
                },
                font: {
                    bold: true,
                    sz: "18"
                }
            }
        }]);
        datas.push([]);
        datas.push([]);
        datas.push([{
            v: "PATIENT",
            t: "s",
            s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}
        },
            {v: "AGE", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}},
            {v: "SEX", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}},
            {v: "WARD", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}},
            {
                v: "PRESCRIBED BY",
                t: "s",
                s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}
            },
            {
                v: "DATE PRESCRIBED",
                t: "s",
                s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}
            },
            {v: "PHYSICIAN", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}},
            {v: "ANTIBIOTIC", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}},
            {v: "APPROVED BY", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}},
            {
                v: "DAYS APPROVED",
                t: "s",
                s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}
            },
            {
                v: "DATE APPROVED",
                t: "s",
                s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}
            },
            {v: "START DATE", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}},
            {v: "STOP DATE", t: "s", s: {font: {bold: true}, alignment: {vertical: "center", horizontal: "center"}}}]);

        for (let i = 0; i < $("#ams_summary tr").length; i++) {
            const tr = $("#ams_summary tr:eq(" + i + ")");
            const rows = [];
            const patient = tr.find("td:eq(0)").text();
            const age = tr.find("td:eq(1)").text();
            const gender = tr.find("td:eq(2)").text();
            const ward = tr.find("td:eq(3)").text();
            const prescribed_by = tr.find("td:eq(4)").text();
            const date_prescribed = tr.find("td:eq(5)").text();
            const physician = tr.find("td:eq(6)").text();
            const antibiotic = tr.find("td:eq(7)").text();
            const approved_by = tr.find("td:eq(8)").text();
            const days_approved = tr.find("td:eq(9)").text();
            const date_approved = tr.find("td:eq(10)").text();
            const start_date = tr.find("td:eq(11)").text();
            const stop_date = tr.find("td:eq(12)").text();

            rows.push({v: patient, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: age, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: gender, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: ward, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: prescribed_by, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: date_prescribed, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: physician, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: antibiotic, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: approved_by, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: days_approved, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: date_approved, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: start_date, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});
            rows.push({v: stop_date, t: "s", s: {alignment: {vertical: "center", horizontal: "center"}}});

            datas.push(rows);
        }

        const ws = XLSX.utils.aoa_to_sheet(datas);
        const merges =
            [
                {s: {r: 1, c: 0}, e: {r: 1, c: 13}},
                {s: {r: 2, c: 0}, e: {r: 2, c: 13}},
                {s: {r: 3, c: 0}, e: {r: 4, c: 13}},
            ];

        ws["!merges"] = merges;

        var wscols = [
            {wch: 50},
            {wch: 7},
            {wch: 7},
            {wch: 30},
            {wch: 20},
            {wch: 30},
            {wch: 40},
            {wch: 70},
            {wch: 30},
            {wch: 15},
            {wch: 20},
            {wch: 20},
            {wch: 20}
        ];

        ws['!cols'] = wscols;

        setTimeout(function () {
            XLSX.utils.book_append_sheet(wb, ws, "Antibiotic Summary Report");
            XLSX.writeFile(wb, moment().format("MMDDYYYYhhmmss") + ".xlsx");
            $("#loader").modal("hide");
            alert("Downloaded successfully");
        }, 800);
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
