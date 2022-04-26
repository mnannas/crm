@extends('layouts.app')

@section('title')
    Manual Invoice
@endsection


@section('content')

<!DOCTYPE html>
<html>
<!-- Jquery (For Date) --------------------------------------------------->
<script src="https://code.jquery.com/jquery-2.2.4.js" integrity="sha256-iT6Q9iMJYuQiMWNd9lDyBUStIq/8PuOW33aOqmvFpqI=" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/humanity/jquery-ui.css"> 

<div class="col-md-12 pt-3">
        
    <div class="card-header py-2" style="border: 1px solid rgb(233, 233, 233); border-radius: 5px;">
        <a href="/product"><i class="bi bi-arrow-left"></i></a> &nbsp; <a href="dashboard">Dashboard</a> / <a href="/product">Event</a> / <b>Create Invoice</b>
    </div>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Create Invoice</h1>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-block">
            <button type="button" class="close" data-dismiss="alert">×</button>	
            <strong>{{ $message }}</strong>
        </div>
    @endif

    <!-- Add product form --------------------------------------------------->
    <form class="row g-3 px-3" action="{{ url('manualdownload-invoice') }}/{{ $student->stud_id }}" method="POST" enctype="multipart/form-data"> 
    @csrf

        <div class="col-md-6">
            <label class="form-label">To</label>
            <input name="to" placeholder="John Doe" type="text" class="form-control" value="{{ ucwords(strtolower($student->first_name)) }} {{ ucwords(strtolower($student->last_name)) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Invoice No</label>
            <input name="invoiceno" placeholder="INV-01-001" type="text" class="form-control" value="{{ 'INV'.uniqid() }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="membership">Choose a Membership</label>
            <select class="form-select" value="{{ $membership_level->name }}" name="membership" id="membership">
                <option class="form-control" value="{{ $membership_level->name }}" disabled selected>{{ $membership_level->name }}</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Invoice Date</label>
            <input type="invoicedate" name="date1" id="date1" class="form-control" placeholder="01-01-2022" required/>
        </div>

        <div class="col-md-6">
            <label class="form-label">Due Date</label>
            <input type="duedate" name="date2" id="date2" class="form-control" placeholder="02-01-2022" required/>
        </div>
        <hr class="mb-2">

        <div class="col-md-12">
            <label for="feature">List of statement</label>
            <div id="inputFormRow">
                <div class="input-group mb-3">
                    <input type="text" name="no[]" placeholder="No" class="form-control" autocomplete="off" required >
                    <select class="form-select" name="pfeatures[]" id="pfeatures">
                        <option class="form-control" disabled selected>Choose One</option>
                    @foreach ($productfeatures as $pf => $pfvalue)
                        <option id="features_id" class="form-control" value="{{ $pfvalue->product_features_name }}">{{ $pfvalue->product_features_name }}</option>
                    @endforeach
                    </select>
                    <input type="number" name="quantity[]" value="" id="quantity" oninput="multiply()" placeholder="Quantity" class="form-control" autocomplete="off" required>
                    <input type="number" name="rate[]" value="" oninput="multiply()" id="rate" placeholder="Rate" class="form-control" autocomplete="off" required>
                    <input type="number" name="totalPrice[]" id="totalPrice" placeholder="Amount" class="form-control" readonly>
                    <div class="input-group-append">                
                        <button id="removeRow" type="button" class="btn btn-danger"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            <div id="newRow"></div>
            <button id="addRow" type='button' class='btn'><i class="bi bi-plus-lg pr-2"></i>Add Row</button>
        </div>
        <hr class="mb-2">

        <div class="col-md-4">
            <label class="form-label">Sub Total</label>
            <input name="subtotal" placeholder="RM 10000" type="text" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Taxable Amount</label>
            <input name="taxableamount" placeholder="RM 10000" type="text" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">SST (%)</label>
            <input name="sst" placeholder="Example 6% .." type="text" class="form-control" required>
        </div>

        <div class="col-12" style="margin-top: 30px;"> 
            <button type="submit" class="btn btn-primary float-right"><i class="bi bi-save pr-2"></i>Submit</button>
        </div>
    </form>
</div>

{{---------------------------------------------------------- Testing ------------------------------------------------------------}}
    
    <div class="justify-content-center">
    <table>
        <tr>
        <th>#</th>
        <th>Item Name</th>
        <th>Item Description</th>
        <th>Amount Per Hour</th>
        <th>Total Hours</th>
        <th>Line Total</th>
        </tr>
    
        <tbody id="addItems"></tbody>
    </table>
    </div>
    
    <p>
        <button type="button" class='btn' onclick="addItem();"><i class="bi bi-plus-lg pr-2"></i>
        Add Item
        </button>
    </p>
    
    <p>
        Amount Due: RM <span id="amountDue">0.00</span>
        <script type="text/javascript">

        </script>
    </p>

    <p>
        Taxable Amount <span id="taxableAmount">0.00</span>
        <script type="text/javascript">
        //add the amounts from all items.
        //if none added then have it set to zero.
        </script>
    </p>

    <p>
        Tax  <span id="tax"></span>%
        <script type="text/javascript">
        //add the amounts from all items.
        //if none added then have it set to zero.
        </script>
    </p>
    
    <p>
        Due Date:
        <script type="text/javascript">
        //start it 2 weeks from actual date
        </script>
    </p>
    
    <p>
        <input id="invoice_submit" type="submit" name="submit">
    </p>

{{---------------------------------------------------------- End Testing ------------------------------------------------------------}}


<!-- Function for datepicker --------------------------------------------------->
<script type="text/javascript">
    $(document).ready(function () {

        $("#date1").datepicker({
            showAnim: 'drop',
            numberOfMonth: 1,
            dateFormat: 'yy-mm-dd',
            onClose: function (selectedDate) {
                $("#date2").datepicker("option", "minDate", selectedDate);
            }
        });

        $("#date2").datepicker({
            showAnim: 'drop',
            numberOfMonth: 1,
            dateFormat: 'yy-mm-dd',
            onClose: function (selectedDate) {
                $("#date1").datepicker("option", "maxDate", selectedDate);
            }
        });
    });

     // add row
    $("#addRow").click(function () {
        var html = '';
        // console.log($productfeatures);
        html += '<div id="inputFormRow">';
        html += '<div class="input-group mb-3">';
        html += '<input type="text" name="no[]" placeholder="No" class="form-control" autocomplete="off" required>';
        html += '<select class="form-select" name="pfeatures[]" id="pfeatures">';
        html += '<option class="form-control" disabled selected>Choose One</option>';
        html += '@foreach ($productfeatures as $pf => $pfvalue)';
        html += '<option id="features_id" class="form-control" value="{{ $pfvalue->product_features_name }}">{{ $pfvalue->product_features_name }}</option>';
        html += '@endforeach';
        html += '</select>';
        html += '<input type="number" name="quantity[]" value="" id="quantitys" oninput="multiply2()" placeholder="Quantity" class="form-control" autocomplete="off" required>';
        html += '<input type="number" name="rate[]" value="" oninput="multiply2()" id="rates" placeholder="Rate" class="form-control" autocomplete="off" required>';
        html += '<input type="number" name="totalPrice[]" id="totalPrices" placeholder="Amount" class="form-control" readonly>';
        html += '<div class="input-group-append">';                
        html += '<button id="removeRow" type="button" class="btn btn-danger"><i class="bi bi-x-lg"></i></button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        $('#newRow').append(html);
    });

    // remove row
    $(document).on('click', '#removeRow', function () {
        $(this).closest('#inputFormRow').remove();
    });

    //////////////////////////////////////////////////////////////////////////////////////////////////

    var renumber = 0;

    // trigger everytime there is change in the table row to calculate the new due amount.
    function calculateDueAmount() {
    var tblRows = document.getElementById("addItems").getElementsByTagName('tr');
    let total = 0;
    for (var i = 0; i < tblRows.length; i++) {
        let lineTotal = tblRows[i].getElementsByTagName('td')[5].getElementsByTagName('input')[0].value;
        total += Number(lineTotal)
    }
    document.getElementById("amountDue").innerText = total.toFixed(2);
    }
    // no changes required in this
    function addItem() {
    renumber++;
    var html = "<tr>";
    html += "<div class='justify-content-center'>";
    html += "<td class='form-control' id='itemNum'>" + renumber + "</td>";
    html += "<td><input class='form-select' id='pfeatures[]' name='itemName[]'></td>";
    html += "<td><input class='form-control' name='itemDescription[]'></td>";
    html += "<td><span class='currency'></span><input class='form-control' id='perHour' onblur='lineTotal(this);' value='0' name='amountPerHour[]'></td>";
    html += "<td><input id='lineHours' class='form-control' onblur='lineTotal(this);' value='0' name='hours[]'></td>";
    html += "<td><span class='currency'></span><input id='lineTotal' class='form-control' onblur='lineTotal(this);' value='0' name='lineTotal[]'></td>";
    html += "<td><button type='button' class='btn btn-danger' id='remove_button' onclick='removeItem(this);'> X </button></td>";
    html += "</tr>";
    html += "</div>";
    document.getElementById("addItems").insertRow().innerHTML = html;
    }
    // updated and sanitized the logic to delete the row
    function removeItem(elem) {

    let rowToDelete = elem.parentElement.parentElement;
    let rowNumber = rowToDelete.getElementsByTagName('td')[0].innerText;
    document.getElementById("addItems").deleteRow(rowNumber - 1);
    let tblRows = document.getElementById('addItems').getElementsByTagName('tr');
    let j = 0;
    for (; j < tblRows.length; j++) {
        tblRows[j].getElementsByTagName('td')[0].innerText = j + 1
    }
    calculateDueAmount(); // calculate due amount since row got deleted.
    renumber = j;
    }

    function lineTotal(elem) {
    var mainRow = elem.parentElement.parentElement;
    var AmtPerHour = mainRow.getElementsByTagName('td')[3].getElementsByTagName('input')[0].value;
    var lnHrs = mainRow.getElementsByTagName('td')[4].getElementsByTagName('input')[0].value;
    var total = mainRow.getElementsByTagName('td')[5].getElementsByTagName('input')[0];
    var myResult = Number(AmtPerHour) * Number(lnHrs);
    total.value = myResult;
    calculateDueAmount(); // calculate due amount since row got added
}

    //////////////////////////////////////////////////////////////////////////////////////////////////
</script>

@endsection

</html>