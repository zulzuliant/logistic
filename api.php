<?php
header('Content-Type: application/json');

// Database connection parameters
$host = 'localhost';
$db   = 'inventaris_kantua'; // Ganti dengan nama database Anda yang sebenarnya
$user = 'root'; // Ganti dengan user database Anda yang sebenarnya
$pass = ''; // Ganti dengan password database Anda yang sebenarnya
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Menampilkan pesan kesalahan koneksi database
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit();
}

$action = $_GET['action'] ?? '';
$table = $_GET['table'] ?? '';

switch ($action) {
    case 'get_data':
        handleGetData($pdo, $table);
        break;
    case 'add_data':
        handleAddData($pdo, $table);
        break;
    case 'update_data':
        handleUpdateData($pdo, $table);
        break;
    case 'delete_data':
        handleDeleteData($pdo, $table);
        break;
    case 'upload_csv':
        handleUploadCsv($pdo, $table);
        break;
    case 'download_csv':
        handleDownloadCsv($pdo, $table);
        break;
    case 'get_dashboard_summary':
        handleGetDashboardSummary($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

function handleGetData($pdo, $table) {
    $search = $_GET['search'] ?? '';
    $sql = '';
    $params = [];

    if ($table === 'team_tools') {
        // Query SELECT for team_tools, retrieving the year from purchase_date and manufacturing_date
        $sql = "SELECT id, team_id, user, part_name, serial_number, brand, standard, size, type, YEAR(purchase_date) AS purchase_year, YEAR(manufacturing_date) AS manufacturing_year, kondisi, updated_at AS `Update`, remark FROM team_tools";
        if ($search) {
            // WHERE clause for searching, covering all relevant columns including year fields
            $sql .= " WHERE team_id LIKE ? OR user LIKE ? OR part_name LIKE ? OR serial_number LIKE ? OR brand LIKE ? OR standard LIKE ? OR size LIKE ? OR type LIKE ? OR kondisi LIKE ? OR remark LIKE ? OR YEAR(purchase_date) LIKE ? OR YEAR(manufacturing_date) LIKE ?";
            $searchTerm = '%' . $search . '%';
            // 12 parameters: 10 original columns + 2 year columns
            $params = array_fill(0, 12, $searchTerm); 
        }
    } elseif ($table === 'gadget_tools') {
        // SELECT query for gadget_tools, including updated_at as 'Update'
        $sql = "SELECT id, pic, job_role, area, device_type, device_brand_type, ram_rom, sn_imei, kondisi, updated_at AS `Update`, remark FROM gadget_tools";
        if ($search) {
            $sql .= " WHERE pic LIKE ? OR job_role LIKE ? OR area LIKE ? OR device_type LIKE ? OR device_brand_type LIKE ? OR ram_rom LIKE ? OR sn_imei LIKE ? OR kondisi LIKE ? OR remark LIKE ?";
            $searchTerm = '%' . $search . '%';
            $params = array_fill(0, 9, $searchTerm); // 9 parameters for 9 searchable columns
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid table.']);
        return;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error retrieving data: ' . $e->getMessage()]);
    }
}

function handleAddData($pdo, $table) {
    $input = json_decode(file_get_contents('php://input'), true);
    $sql = '';
    $params = [];

    if ($table === 'team_tools') {
        // INSERT query for team_tools, including purchase_date and manufacturing_date
        $sql = "INSERT INTO team_tools (team_id, user, part_name, serial_number, brand, standard, size, type, purchase_date, manufacturing_date, kondisi, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $input['team_id'] ?? null,
            $input['user'] ?? null,
            $input['part_name'] ?? null,
            $input['serial_number'] ?? null,
            $input['brand'] ?? null,
            $input['standard'] ?? null,
            $input['size'] ?? null,
            $input['type'] ?? null,
            $input['purchase_date'] ?? null, // Will be 'YYYY-01-01' from frontend
            $input['manufacturing_date'] ?? null, // Will be 'YYYY-01-01' from frontend
            $input['kondisi'] ?? null,
            $input['remark'] ?? null
        ];
    } elseif ($table === 'gadget_tools') {
        // INSERT query for gadget_tools
        $sql = "INSERT INTO gadget_tools (pic, job_role, area, device_type, device_brand_type, ram_rom, sn_imei, kondisi, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $input['pic'] ?? null,
            $input['job_role'] ?? null,
            $input['area'] ?? null,
            $input['device_type'] ?? null,
            $input['device_brand_type'] ?? null,
            $input['ram_rom'] ?? null,
            $input['sn_imei'] ?? null,
            $input['kondisi'] ?? null,
            $input['remark'] ?? null
        ];
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid table.']);
        return;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Data added successfully.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding data: ' . $e->getMessage()]);
    }
}

function handleUpdateData($pdo, $table) {
    $input = json_decode(file_get_contents('php://input'), true);
    $sql = '';
    $params = [];

    if ($table === 'team_tools') {
        // UPDATE query for team_tools, including purchase_date and manufacturing_date
        $sql = "UPDATE team_tools SET team_id = ?, user = ?, part_name = ?, serial_number = ?, brand = ?, standard = ?, size = ?, type = ?, purchase_date = ?, manufacturing_date = ?, kondisi = ?, remark = ? WHERE id = ?";
        $params = [
            $input['team_id'] ?? null,
            $input['user'] ?? null,
            $input['part_name'] ?? null,
            $input['serial_number'] ?? null,
            $input['brand'] ?? null,
            $input['standard'] ?? null,
            $input['size'] ?? null,
            $input['type'] ?? null,
            $input['purchase_date'] ?? null, // Will be 'YYYY-01-01' from frontend
            $input['manufacturing_date'] ?? null, // Will be 'YYYY-01-01' from frontend
            $input['kondisi'] ?? null,
            $input['remark'] ?? null,
            $input['id']
        ];
    } elseif ($table === 'gadget_tools') {
        // UPDATE query for gadget_tools
        $sql = "UPDATE gadget_tools SET pic = ?, job_role = ?, area = ?, device_type = ?, device_brand_type = ?, ram_rom = ?, sn_imei = ?, kondisi = ?, remark = ? WHERE id = ?";
        $params = [
            $input['pic'] ?? null,
            $input['job_role'] ?? null,
            $input['area'] ?? null,
            $input['device_type'] ?? null,
            $input['device_brand_type'] ?? null,
            $input['ram_rom'] ?? null,
            $input['sn_imei'] ?? null,
            $input['kondisi'] ?? null,
            $input['remark'] ?? null,
            $input['id']
        ];
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid table.']);
        return;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Data updated successfully.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating data: ' . $e->getMessage()]);
    }
}

function handleDeleteData($pdo, $table) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID is required.']);
        return;
    }

    $sql = '';
    if ($table === 'team_tools' || $table === 'gadget_tools') {
        $sql = "DELETE FROM $table WHERE id = ?";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid table.']);
        return;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Data deleted successfully.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting data: ' . $e->getMessage()]);
    }
}

function handleUploadCsv($pdo, $table) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Error uploading file.']);
        return;
    }

    $filePath = $_FILES['csv_file']['tmp_name'];
    $file = fopen($filePath, 'r');

    if ($file === FALSE) {
        echo json_encode(['success' => false, 'message' => 'Failed to open uploaded file.']);
        return;
    }

    $headers = fgetcsv($file); // Get headers from the first row
    $data = [];
    while (($row = fgetcsv($file)) !== FALSE) {
        // Create an associative array, mapping headers to row values
        if (count($headers) === count($row)) {
            $data[] = array_combine($headers, $row);
        } else {
            // Handle rows with mismatched column counts
            error_log("Skipping row due to column mismatch: " . implode(',', $row));
        }
    }
    fclose($file);

    $errors = [];
    $successCount = 0;

    $pdo->beginTransaction();
    try {
        if ($table === 'team_tools') {
            // Updated INSERT statement for team_tools, including purchase_date and manufacturing_date
            $stmt = $pdo->prepare("INSERT INTO team_tools (team_id, user, part_name, serial_number, brand, standard, size, type, purchase_date, manufacturing_date, kondisi, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data as $row) {
                // Ensure all expected keys exist, provide null default if not
                $team_id = $row['team_id'] ?? null;
                $user = $row['user'] ?? null;
                $part_name = $row['part_name'] ?? null;
                $serial_number = $row['serial_number'] ?? null;
                $brand = $row['brand'] ?? null;
                $standard = $row['standard'] ?? null;
                $size = $row['size'] ?? null;
                $type = $row['type'] ?? null;
                // Convert year to 'YYYY-01-01' date format for the database
                $purchase_date = isset($row['purchase_date']) && is_numeric($row['purchase_date']) ? $row['purchase_date'] . '-01-01' : null;
                $manufacturing_date = isset($row['manufacturing_date']) && is_numeric($row['manufacturing_date']) ? $row['manufacturing_date'] . '-01-01' : null;
                $kondisi = $row['kondisi'] ?? null;
                $remark = $row['remark'] ?? null;

                try {
                    $stmt->execute([$team_id, $user, $part_name, $serial_number, $brand, $standard, $size, $type, $purchase_date, $manufacturing_date, $kondisi, $remark]);
                    $successCount++;
                } catch (\PDOException $e) {
                    $errors[] = "Row with serial number '{$serial_number}' failed: " . $e->getMessage();
                }
            }
        } elseif ($table === 'gadget_tools') {
            $stmt = $pdo->prepare("INSERT INTO gadget_tools (pic, job_role, area, device_type, device_brand_type, ram_rom, sn_imei, kondisi, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data as $row) {
                // Ensure all expected keys exist, provide null default if not
                $pic = $row['pic'] ?? null;
                $job_role = $row['job_role'] ?? null;
                $area = $row['area'] ?? null;
                $device_type = $row['device_type'] ?? null;
                $device_brand_type = $row['device_brand_type'] ?? null;
                $ram_rom = $row['ram_rom'] ?? null;
                $sn_imei = $row['sn_imei'] ?? null;
                $kondisi = $row['kondisi'] ?? null;
                $remark = $row['remark'] ?? null;

                try {
                    $stmt->execute([$pic, $job_role, $area, $device_type, $device_brand_type, $ram_rom, $sn_imei, $kondisi, $remark]);
                    $successCount++;
                } catch (\PDOException $e) {
                    $errors[] = "Row with SN/IMEI '{$sn_imei}' failed: " . $e->getMessage();
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid table for CSV upload.']);
            $pdo->rollBack();
            return;
        }

        $pdo->commit();
        if (empty($errors)) {
            echo json_encode(['success' => true, 'message' => "Successfully uploaded {$successCount} records."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Uploaded {$successCount} records with errors. See console for details.", 'errors' => $errors]);
        }

    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error during CSV upload: ' . $e->getMessage()]);
    }
}


function handleDownloadCsv($pdo, $table) {
    $filename = $table . '_summary.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if ($table === 'team_tools') {
        // SELECT query and headers for team_tools CSV download, retrieving year from date columns
        $stmt = $pdo->query("SELECT team_id, user, part_name, serial_number, brand, standard, size, type, YEAR(purchase_date) AS purchase_year, YEAR(manufacturing_date) AS manufacturing_year, kondisi, updated_at AS `Update`, remark FROM team_tools");
        $headers = ['Team ID', 'User', 'Part Name', 'Serial Number', 'Brand', 'Standard', 'Size', 'Type', 'Purchase Year', 'Manufacture Year', 'Kondisi', 'Update', 'Remark'];
        fputcsv($output, $headers);
    } elseif ($table === 'gadget_tools') {
        // SELECT query and headers for gadget_tools CSV download
        $stmt = $pdo->query("SELECT pic, job_role, area, device_type, device_brand_type, ram_rom, sn_imei, kondisi, updated_at AS `Update`, remark FROM gadget_tools");
        $headers = ['PIC', 'Job Role', 'Area', 'Device Type', 'Device Brand | Type', 'RAM / ROM', 'SN / IMEI', 'Kondisi', 'Update', 'Remark'];
        fputcsv($output, $headers);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid table for CSV download.']);
        fclose($output);
        return;
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

function handleGetDashboardSummary($pdo) {
    try {
        // Total Team Tools
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM team_tools");
        $totalTeamTools = $stmt->fetchColumn();

        // Total Gadget Tools
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM gadget_tools");
        $totalGadgetTools = $stmt->fetchColumn();

        // Team Tools Condition Summary
        $stmt = $pdo->query("SELECT kondisi, COUNT(*) AS count FROM team_tools GROUP BY kondisi");
        $teamKondisiSummary = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $teamKondisiSummary[$row['kondisi']] = (int)$row['count'];
        }

        // Gadget Tools Condition Summary
        $stmt = $pdo->query("SELECT kondisi, COUNT(*) AS count FROM gadget_tools GROUP BY kondisi");
        $gadgetKondisiSummary = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $gadgetKondisiSummary[$row['kondisi']] = (int)$row['count'];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'total_team_tools' => $totalTeamTools,
                'total_gadget_tools' => $totalGadgetTools,
                'team_kondisi_summary' => $teamKondisiSummary,
                'gadget_kondisi_summary' => $gadgetKondisiSummary
            ]
        ]);

    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error retrieving dashboard summary: ' . $e->getMessage()]);
    }
}
?>
