<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Style.css">
    <title>File Checking</title>
    <style>
        html {
            font-family: "Inter", sans-serif;
        }

        body {
            background-color: #CE99FF;
            margin: 0;
            padding: 0;
        }
        .center {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            width: 320px;
            background-color: #fff;
            margin: 20px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: box-shadow 0.3s ease;
        }

        form:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        }

        /* custom file input */
        .custom-file-upload {
            display: inline-block;
            width: calc(100% - 20px);
            padding: 12px;
            margin: 12px 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box;
            font-size: 1em;
            color: #777;
        }

        .custom-file-upload:hover {
            border-color: #4CA82C;
            outline: none;
            box-shadow: 0 0 5px rgba(76, 168, 44, 0.2);
        }

        input[type="file"] {
            display: none; /* Hide the default file input */
        }

        input[type="submit"] {
            width: 100%;
            background-color: #4CA82C;
            color: white;
            padding: 14px;
            margin: 12px 0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }

        input[type="submit"]:hover {
            background-color: #3c8833;
        }

        .formInput h1 {
            text-align: center;
            font-size: 1.5em;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="center">
        <!-- Form for checking local file upload -->
        <form action="" method="post" enctype="multipart/form-data" class="formInput" id="Local">
            <h1>Check Local File</h1>
            
            <label for="input_local" class="custom-file-upload">Choose a File</label>
            <input type="file" name="input_local" id="input_local" required>
            <input type="submit" name="submit_local" value="SUBMIT">
        </form>
    </div>

    <?php
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            if (isset($_POST['submit_local'])) {
                // Check if a file was uploaded without errors
                if (isset($_FILES['input_local']) && $_FILES['input_local']['error'] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['input_local']['name'];
                    $file_tmp = $_FILES['input_local']['tmp_name'];

                    // Define the upload directory (this should exist)
                    $upload_dir = 'uploads/';
                    $target_file = $upload_dir . basename($file_name);

                    // Move the uploaded file to the target directory
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        echo "<script>alert('File uploaded successfully: $file_name');</script>";
                    } else {
                        echo "<script>alert('Error uploading file.');</script>";
                    }
                } else {
                    echo "<script>alert('No file uploaded or error occurred.');</script>";
                }
            }
        }
    ?>
</body>
</html>