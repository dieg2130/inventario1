<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "simple_stock";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Procesar el formulario de subida de PDF
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    $nombre = $_POST['nombre'];
    $dependencia = $_POST['dependencia'];
    $cedula = $_POST['cedula'];

    // Validar que el archivo sea PDF
    if ($_FILES['pdf']['type'] == 'application/pdf') {
        $pdf = file_get_contents($_FILES['pdf']['tmp_name']);

        // Insertar el PDF y otros datos en la base de datos
        $stmt = $conn->prepare("INSERT INTO pdf_files (pdf, nombre, dependencia, cedula) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("bsss", $pdf, $nombre, $dependencia, $cedula);
        $stmt->send_long_data(0, $pdf); // Enviar datos largos para el archivo PDF
        $stmt->execute();
        $stmt->close();

        // Redireccionar para evitar duplicación al recargar
        header("Location: subir.php");
        exit;
    } else {
        echo "Por favor, sube un archivo PDF válido.";
    }
}

// Eliminar un archivo PDF de la base de datos
if (isset($_GET['delete_pdf_id'])) {
    $id = $_GET['delete_pdf_id'];
    
    // Preparar la eliminación del registro
    $stmt = $conn->prepare("DELETE FROM pdf_files WHERE id_pdf = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Redireccionar para evitar duplicación al recargar
    header("Location: subir.php");
    exit;
}

// Mostrar PDF en el navegador
if (isset($_GET['ver_pdf_id'])) {
    $id = $_GET['ver_pdf_id'];

    // Obtener el archivo PDF de la base de datos
    $stmt = $conn->prepare("SELECT pdf FROM pdf_files WHERE id_pdf = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($pdf);
    $stmt->fetch();
    $stmt->close();

    if ($pdf) {
        header("Content-type: application/pdf");
        echo $pdf;
        exit;
    } else {
        echo "No se encontró el archivo PDF.";
    }
}

// Procesar búsqueda
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
}

// Obtener registros de pdf_files para mostrarlos en una tabla
$sql = "SELECT id_pdf, nombre, dependencia, cedula FROM pdf_files";
if (!empty($search_query)) {
    $sql .= " WHERE nombre LIKE ? OR dependencia LIKE ? OR cedula LIKE ?";
}
$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilo.css">
    <title>Subir y Mostrar PDF</title>
</head>
<body>

    <h2>Subir un archivo PDF</h2>
    
    
    <form action="subir.php" method="post" enctype="multipart/form-data">
    <button onclick="window.location.href='stock.php';">Volver al Inventario</button>
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" required><br><br>

        <label for="dependencia">Dependencia:</label>
        <input type="text" name="dependencia" required><br><br>

        <label for="cedula">Cédula:</label>
        <input type="text" name="cedula" required><br><br>

        <label for="pdf">Archivo PDF:</label>
        <input type="file" name="pdf" accept="application/pdf" required><br><br>

        <input type="submit" name="upload" value="Subir PDF">
    </form>

    <h2>Buscar Archivos PDF</h2>
    <form action="subir.php" method="post">
        <input type="text" name="search_query" placeholder="Buscar por nombre, dependencia o cédula" value="<?php echo htmlspecialchars($search_query); ?>">
        <input type="submit" name="search" value="Buscar">
    </form>

    <h2>Archivos PDF Subidos</h2>
    <table border="1">
        <tr>
            <th>Nombre</th>
            <th>Dependencia</th>
            <th>Cédula</th>
            <th>Ver PDF</th>
            <th>Eliminar PDF</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dependencia']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cedula']) . "</td>";
                echo "<td><a href='subir.php?ver_pdf_id=" . $row['id_pdf'] . "' target='_blank'>Ver PDF</a></td>";
                echo "<td><a href='subir.php?delete_pdf_id=" . $row['id_pdf'] . "' onclick=\"return confirm('¿Estás seguro de que deseas eliminar este PDF?');\">Eliminar</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No hay datos disponibles</td></tr>";
        }
        ?>
    </table>

    <div style="text-align: center; margin-top: 20px;">
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
