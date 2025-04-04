
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>


<?php
$cn = pg_connect("host=localhost port=5432 dbname=artists user=postgres password=Superfubar1993!");

$result = pg_query($cn,"SELECT * FROM users");

if (!$result) {
    echo "error";
    exit;
}


?>

<table>
 

<?php
while($row = pg_fetch_assoc($result)) {
    echo "
    <tr>
    <td>$row[firstname]</td>
    <td>$row[middlename]</td>
    </tr>
    ";
}
?>
</table>

    
</body>
</html>
