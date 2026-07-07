<?php
date_default_timezone_set('Africa/Nairobi');

echo "<h2>Timezone Test</h2>";

echo "<h3>PHP Timezone:</h3>";
echo "Default timezone: " . date_default_timezone_get() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br><br>";

include 'config.php';
$result = $conn->query("SELECT NOW() as mysql_time, @@session.time_zone as timezone");
$row = $result->fetch_assoc();

echo "<h3>MySQL Timezone:</h3>";
echo "MySQL current time: " . $row['mysql_time'] . "<br>";
echo "MySQL timezone: " . $row['timezone'] . "<br><br>";

$session_query = "SELECT session_id, start_time, end_time, 
                  NOW() as current_time,
                  CASE 
                      WHEN NOW() BETWEEN start_time AND end_time THEN 'ACTIVE'
                      WHEN NOW() < start_time THEN 'NOT STARTED'
                      WHEN NOW() > end_time THEN 'EXPIRED'
                  END as status
                  FROM attendance_sessions";
$result = $conn->query($session_query);

echo "<h3>Sessions:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Start Time</th><th>End Time</th><th>Current Time</th><th>Status</th></tr>";
    while ($session = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $session['session_id'] . "</td>";
        echo "<td>" . $session['start_time'] . "</td>";
        echo "<td>" . $session['end_time'] . "</td>";
        echo "<td>" . $session['current_time'] . "</td>";
        echo "<td style='font-weight:bold; color:" . ($session['status'] == 'ACTIVE' ? 'green' : 'red') . "'>" . $session['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No sessions found.";
}

$conn->close();
?>