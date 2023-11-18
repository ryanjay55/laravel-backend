<!DOCTYPE html>
<html>
<head>
    <title>User Details</title>
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
        <h1 class="red-cross-text">Registered Users</h1> 
        <div class="summary">
            <div class="total-users">Total Users: {{ $totalUsers }}</div>
            <div class="as-of-date">As of {{ $dateNow }}</div>
        </div>       
        <table>
            <thead>
                <tr>
                    <th>Donor Number</th>
                    <th>Name</th>
                    <th>Blood Type</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Last Date Donated</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userDetails as $user)
                    <tr>
                        <td>{{ $user->donor_no }}</td>
                        <td>{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</td>
                        <td>{{ $user->blood_type }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->mobile }}</td>
                        <td>{{ $user->latest_date_donated }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
