<!DOCTYPE html>
<html>
<head>
    <title>User Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: #d52b1e;
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #d52b1e;
            color: #fff;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .red-cross-logo {
            display: block;
            width: 100px;
            margin: 0 auto;
            padding-bottom: 10px;
        }

        .red-cross-text {
            font-weight: bold;
            color: #d52b1e;
        }
    </style>
</head>
<body>
    <img src="{{ asset('logo/LifeLink-logo.png') }}" alt="LifeLink Logo" class="red-cross-logo">
    <h1 class="red-cross-text">Registerd Users</h1>
    <table>
        <thead>
            <tr>
                <th>Donor Number</th>
                <th>Name</th>
                <th>Blood Type</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Birth Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($userDetails as $user)
                <tr>
                    <td>{{ $user->donor_no }}</td>
                    <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                    <td>{{ $user->blood_type }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->mobile }}</td>
                    <td>{{ $user->dob }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>