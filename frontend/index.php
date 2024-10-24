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
            width: 300px;
            background-color: #fff;
            margin: 60px;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input[type="text"], input[type="file"] {
            width: calc(100% - 40px);
            padding: 10px;
            margin: 10px 20px;
            border: none;
            border-bottom: 3px solid #777;
            border-radius: 0;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
        }

        input[type="submit"] {
            width: 100%;
            background-color: #4CA82C;
            color: white;
            padding: 15px 0;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            box-sizing: border-box;
        }

        img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .formInput h1 {
            text-align: center;
        }

        .formInput div {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="center">
        <!-- Form for checking from a link -->
        <form action="" method="post" class="formInput" id="Link">
            <h1>Check from Link</h1>
            <input type="text" name="input_link" id="input_link" placeholder="Put link to file here" required>
            <input type="submit" name="submit_link" value="SUBMIT">
        </form>

        <!-- Form for checking local file upload -->
        <form action="" method="post" enctype="multipart/form-data" class="formInput" id="Local">
            <h1>Check Local File</h1>
            <input type="file" name="input_local" id="input_local" required>
            <input type="submit" name="submit_local" value="SUBMIT">
        </form>
    </div>

    <?php
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Check if 'Check from Link' form was submitted
            if (isset($_POST['submit_link'])) {
                $link_file = htmlspecialchars($_POST['input_link']);
                echo "<script>alert('Checking from link for file: $link_file');</script>";
            }

            // Check if 'Check Local File' form was submitted
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