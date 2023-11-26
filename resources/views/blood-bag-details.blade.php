<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <style>
        /* Apply styles to the container */
        .container {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f5f5f5;
            padding: 20px;
        }

        /* Style the logo */
        .red-cross-logo {
            width: 100px; /* Adjust the width as needed */
        }

        /* Style the heading */
        .red-cross-text {
            color: #FF0000; /* Red color for the heading */
            font-size: 24px; /* Increase font size for emphasis */
            margin-bottom: 10px; /* Add spacing below the heading */
        }

        /* Style the summary section */
        .summary {
            margin-bottom: 20px; /* Add spacing below the summary */
        }

        /* Style the donor list table */
        .donor-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        /* Style table headers */
        .donor-table th {
            background-color: #f2f2f2; /* Light gray background for headers */
            padding: 10px;
            text-align: left;
        }

        /* Style table rows */
        .donor-table td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        /* Style the "Total Donors" and "As of Date" text */
        .total-users, .as-of-date {
            font-weight: bold;
        }

        /* Add hover effect to table rows */
        .donor-table tr:hover {
            background-color: #f9f9f9; /* Light gray background on hover */
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="{{ asset('logo/Lifelink-logo.png') }}" alt="LifeLink Logo">
        <h1 class="red-cross-text">Collected Blood Bags</h1>
        <div class="summary">
            <div class="total-users">Total Blood Bags: {{ $totalBloodBags }}</div>
            <div class="as-of-date">As of {{ $dateNow }}</div>
        </div>
        <table class="donor-table">
            <thead>
                <tr>
                    <th>Donor Number</th>
                    <th>Serial Number</th>
                    <th>Name</th>
                    <th>Blood Type</th>
                    <th>Date Donated</th>
                    <th>Expiration Date</th>
                    <th>Bled By</th>
                    <th>Venue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bloodBags as $bags)
                <tr>
                    <td>{{ $bags->donor_no }}</td>
                    <td>{{ $bags->serial_no }}</td>
                    <td>{{ $bags->first_name }} {{ $bags->last_name }}</td>
                    <td>{{ $bags->blood_type }}</td>
                    <td>{{ $bags->date_donated }}</td>
                    <td>{{ $bags->expiration_date }}</td>
                    <td>{{ $bags->bled_by_first_name }} {{ $bags->bled_by_middle_name }} {{ $bags->bled_by_last_name }}</td>
                    <td>{{ $bags->venues_desc }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
