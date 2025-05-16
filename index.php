<?php
session_start();
define('UPLOAD_DIR', 'images/');
define('IMAGES_PER_PAGE', 6);

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    $file = $_FILES['image'];
    $error = null;
    
   
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed with error code: ' . $file['error'];
    } elseif (!in_array($file['type'], $allowedTypes)) {
        $error = 'Only JPG, JPEG, and PNG files are allowed';
    } elseif ($file['size'] > $maxFileSize) {
        $error = 'File size exceeds the maximum limit of 5MB';
    } else {
      
        $filename = uniqid() . '_' . basename($file['name']);
        $destination = UPLOAD_DIR . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['success'] = 'Image uploaded successfully';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $imageInfo = getimagesize($destination);
                $response = [
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'filename' => $filename,
                    'path' => $destination,
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
        } else {
            $error = 'Failed to save the uploaded image';
        }
    }
    
    if ($error) {
        $_SESSION['error'] = $error;
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            $response = [
                'success' => false,
                'message' => $error
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
$images = [];
if (file_exists(UPLOAD_DIR) && is_dir(UPLOAD_DIR)) {
    $files = scandir(UPLOAD_DIR);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png'])) {
            $images[] = $file;
        }
    }
    rsort($images);
}

$totalImages = count($images);
$totalPages = ceil($totalImages / IMAGES_PER_PAGE);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$startIndex = ($currentPage - 1) * IMAGES_PER_PAGE;
$currentImages = array_slice($images, $startIndex, IMAGES_PER_PAGE);

function isPortrait($imagePath) {
    $imageInfo = getimagesize($imagePath);
    return $imageInfo[1] > $imageInfo[0]; // height > width
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Album</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Photo Album</h1>
            
            <div class="book-cover">
                <h2>My Photo Collection</h2>
                <p>A beautiful way to showcase your memories</p>
            </div>
            
            <div class="upload-container">
                <form id="uploadForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" name="image" id="imageUpload" accept=".jpg, .jpeg, .png">
                        <label for="imageUpload">Choose an image</label>
                    </div>
                    <button type="submit" id="uploadButton">Upload</button>
                </form>
                <div id="uploadStatus"></div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
        </header>
        
        <main>
            <div class="album-container">
                <?php 
                for ($i = 0; $i < count($currentImages); $i += 6) {
                    // Get up to 6 images for one spread (3 left, 3 right)
                    $spreadImages = array_slice($currentImages, $i, 6);
                    if (count($spreadImages) > 0) {
                ?>
                <div class="book-spread">
                    <div class="album-column">
                        <?php 
                    
                        for ($j = 0; $j < min(3, count($spreadImages)); $j++) {
                            $image = $spreadImages[$j];
                            $imagePath = UPLOAD_DIR . $image;
                            $isPortraitClass = isPortrait($imagePath) ? 'portrait' : 'landscape';
                        ?>
                        <div class="album-item <?php echo $isPortraitClass; ?>">
                            <img src="<?php echo $imagePath; ?>" alt="Photo">
                        </div>
                        <?php } ?>
                    </div>
                    
                    <div class="album-column">
                        <?php 
                      
                        for ($j = 3; $j < min(6, count($spreadImages)); $j++) {
                            $image = $spreadImages[$j];
                            $imagePath = UPLOAD_DIR . $image;
                            $isPortraitClass = isPortrait($imagePath) ? 'portrait' : 'landscape';
                        ?>
                        <div class="album-item <?php echo $isPortraitClass; ?>">
                            <img src="<?php echo $imagePath; ?>" alt="Photo">
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php 
                    }
                }
                ?>
            </div>
            
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-button prev">«</a>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-button prev">‹</a>
                <?php else: ?>
                    <span class="pagination-button disabled">«</span>
                    <span class="pagination-button disabled">‹</span>
                <?php endif; ?>
                
                <span class="page-info"><?php echo $currentPage; ?></span>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-button next">›</a>
                    <a href="?page=<?php echo max(1, $totalPages); ?>" class="pagination-button next">»</a>
                <?php else: ?>
                    <span class="pagination-button disabled">›</span>
                    <span class="pagination-button disabled">»</span>
                <?php endif; ?>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Picera Pvt Ltd - Photo Album</p>
        </footer>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
