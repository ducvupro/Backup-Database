<?php

define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', ''); // Tên database cần backup
define('TABLE_BACKUP', 'ALL'); // ALL = tất cả các bảng trong database sẽ được backup

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME) or die("THÔNG TIN KẾT NỐI CƠ SỞ DỮ LIỆU SAI!");

$backup_file = 'sql/backup_' . date('Y-m-d-H-i-s') . '.sql';
$file_handle = fopen($backup_file, 'w');

if (TABLE_BACKUP == "ALL") {
    $tables = array();
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    foreach ($tables as $table) {
        backup_table($conn, $file_handle, $table);
    }
} else {
    backup_table($conn, $file_handle, TABLE_BACKUP);
}

fclose($file_handle);

echo "Tạo file dump thành công!";

function backup_table($conn, $file_handle, $table)
{
    $table = mysqli_real_escape_string($conn, $table);
    fwrite($file_handle, "DROP TABLE IF EXISTS `$table`;\n");

    $create_stmt = mysqli_prepare($conn, "SHOW CREATE TABLE `$table`");
    mysqli_stmt_execute($create_stmt);
    $create_row = mysqli_fetch_row(mysqli_stmt_get_result($create_stmt));
    $create_table = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $create_row[1]);
    fwrite($file_handle, $create_table . ";\n");
    mysqli_stmt_close($create_stmt);

    $result = mysqli_query($conn, "SELECT * FROM `$table`");
    $num_fields = mysqli_num_fields($result);

    $values = array();
    while ($row = mysqli_fetch_row($result)) {
        $fields = array();
        for ($i = 0; $i < $num_fields; $i++) {
            $fields[] = "'" . mysqli_real_escape_string($conn, $row[$i]) . "'";
        }
        $values[] = "(" . implode(',', $fields) . ")";
    }

    if (!empty($values)) {
        fwrite($file_handle, "INSERT INTO `$table` VALUES " . implode(',', $values) . ";\n");
    }
}
