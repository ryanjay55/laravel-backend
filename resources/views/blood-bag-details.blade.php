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

        /* Style the donor list */
        .donor-list {
            list-style: none; /* Remove list bullets */
            padding: 0;
            text-align: left; /* Left-align list items */
            margin: 0 auto; /* Center the list */
            max-width: 800px; /* Limit the width of the list */
        }

        /* Style list items */
        .donor-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff; /* White background for items */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Add a subtle shadow */
            border-radius: 5px; /* Rounded corners */
        }

        /* Style the "Total Donors" and "As of Date" text */
        .total-users, .as-of-date {
            font-weight: bold;
        }

        /* Add hover effect to list items */
        .donor-item:hover {
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
        <ul class="donor-list">
            @foreach($bloodBags as $bags)
            <li class="donor-item">
                <strong>Donor Number:</strong> {{ $bags->donor_no }}<br>
                <strong>Serial Number:</strong> {{ $bags->serial_no }}<br>
                <strong>Name:</strong> {{ $bags->first_name }} {{ $bags->last_name }}<br>
                <strong>Blood Type:</strong> {{ $bags->blood_type }}<br>
                <strong>Date Donated:</strong> {{ $bags->date_donated }}<br>
                <strong>Expiration Date:</strong> {{ $bags->expiration_date }}<br>
                <strong>Expiration Date:</strong> {{ $bags->bled_by }}<br>
                <strong>Expiration Date:</strong> {{ $bags->venue }}<br>
            </li>
            @endforeach
        </ul>
    </div>
</body>

</html>
