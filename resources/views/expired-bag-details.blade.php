<!DOCTYPE html>
<html>
<head>
    <title>Expired Blood</title>
    <style>
        /* Apply styles to the container */
        .container {
            font-family: Arial, sans-serif;
            text-align: center;
            
        }

        /* Style the logo */
        .red-cross-logo {
            width: 100px; /* Adjust the width as needed */
        }

        /* Style the heading */
        .red-cross-text {
            color: #FF0000; /* Red color for the heading */
        }

        /* Style the summary section */
        .summary {
            margin: 20px 0;
        }

        /* Style the table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }


        /* Style table header */
        th {
            background-color: #FF0000; /* Red background for header cells */
            color: white; /* White text for header cells */
            padding: 10px;
        }

        /* Style table data rows */
        td {
            padding: 10px;
            border: 1px solid #ccc;
        }

        /* Style alternate rows with a background color */
        tr:nth-child(even) {
            background-color: #f2f2f2; /* Light gray background for even rows */
        }

        /* Style the "Total Donors" and "As of Date" text */
        .total-users, .as-of-date {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://redcrosslifelink.com/public/logo/Lifelink-logo.png" alt="LifeLink Logo" class="red-cross-logo">
        <h1 class="red-cross-text">Expired Blood</h1> 
        <div class="summary">
            <div class="total-users">Total Blood Bags: {{ $totalBloodBags }}</div>
            <div class="as-of-date">As of {{ $dateNow }}</div>
        </div>
        <div class="summary">

        </div>       
        <table>
            <thead>
                <tr>
                    <th>Donor Number</th>
                    <th>Serial Number</th>
                    {{-- <th>Donor Name</th> --}}
                    <th>Blood Type</th>
                    <th>Date Donated</th>
                    <th>Expiration Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inventory as $item)
                <tr>
                    <td>{{ $item->donor_no }}</td>
                    <td>{{ $item->serial_no }}</td>
                    {{-- <td>{{ $item->first_name }} {{ $item->last_name }}</td> --}}
                    <td>{{ $item->blood_type }}</td>
                    <td>{{ $item->date_donated }}</td>
                    <td>{{ $item->expiration_date }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
