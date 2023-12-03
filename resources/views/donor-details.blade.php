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
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Style the donor list */
        .donor-list {
            list-style: none; /* Remove list bullets */
            padding: 0;
            text-align: left; /* Left-align list items */
            max-width: 800px; /* Limit the width of the list */
            margin: 0 auto; /* Center the list */
        }

        /* Style list items */
        .donor-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9; /* Light gray background */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Add a subtle shadow */
            border-radius: 5px; /* Rounded corners */
        }

        /* Style the "Total Donors" and "As of Date" text */
        .total-users, .as-of-date {
            font-weight: bold;
        }

        /* Display barangays in a horizontal row with four per row */
        .donors-per-barangay table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .donors-per-barangay th, .donors-per-barangay td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="{{ asset('logo/Lifelink-logo.png') }}" alt="LifeLink Logo" class="red-cross-logo">
        <h1 class="red-cross-text">Donor Details Summary</h1>
        <div class="as-of-date">As of {{ $dateNow }}</div>
        <div class="summary">
            <div class="total-users">Total Donors: {{ $totalDonors }}</div>
            <div class="total-users">Blood Type: {{ $bloodType }}</div>
            <div class="total-users">Donor Type: {{ $donorType }}</div>
            <div class="total-users">Male Donors: {{ $maleCount }}</div>
            <div class="total-users">Female Donors: {{ $femaleCount }}</div>
            <div class="donors-per-barangay">
                <table>
                    <thead>
                        <tr>
                            <th>Barangay</th>
                            <th>Donor Count</th>
                            <th>Male Count</th>
                            <th>Female Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($donorsPerBarangay as $barangay)
                        <tr>
                            <td>{{ $barangay->brgyDesc }}</td>
                            <td>{{ $barangay->donor_count }}</td>
                            <td>{{ $barangay->male_count }}</td>
                            <td>{{ $barangay->female_count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
            </div>
        </div>
        <ul class="donor-list">
            @foreach($donorDetails as $donor)
            <li class="donor-item">
                <strong>Donor Number:</strong> {{ $donor->donor_no }}<br>
                <strong>Name:</strong> {{ $donor->first_name }} {{ $donor->last_name }}<br>
                <strong>Sex:</strong> {{ $donor->sex }}<br>
                <strong>Blood Type:</strong> {{ $donor->blood_type }}<br>
                <strong>Birth Date:</strong> {{ $donor->dob }}<br>
                <strong>Occupation:</strong> {{ $donor->occupation }}<br>
                <strong>Email:</strong> {{ $donor->email }}<br>
                <strong>Mobile:</strong> {{ $donor->mobile }}<br>
                <strong>Address:</strong> {{ $donor->street }}, {{ $donor->brgyDesc }}, {{ $donor->citymunDesc }},
                {{ $donor->provDesc }}, {{ $donor->regDesc }}<br>
                <strong>Donate Quantity:</strong> {{ $donor->donate_qty }}<br>
                <strong>Badge:</strong> {{ $donor->badge }}<br>
            </li>
            @endforeach
        </ul>
    </div>
</body>

</html>
