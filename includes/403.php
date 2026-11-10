<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden – Chandila Web Development</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/favicon-32x32.png">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .error-card { background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 40px; text-align: center; max-width: 520px; box-shadow: 0 10px 30px rgba(15,23,42,0.06); }
    </style>
</head>
<body>
    <div class="error-card">
        <a href="<?php echo BASE_URL; ?>/index.php" class="d-inline-block mb-3">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Logo" style="max-height: 48px; object-fit: contain;">
        </a>
        <div class="mb-3">
            <i class="fa-solid fa-shield-halved fa-3x text-danger"></i>
        </div>
        <h3 class="text-dark fw-bold mb-2">Access Denied (403)</h3>
        <p class="text-muted mb-4 small">You do not have the required permissions to view this resource. Your role does not authorize access to this section.</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary btn-sm px-3"><i class="fa-solid fa-house me-1"></i> Public Home</a>
            <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary btn-sm px-3"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Switch Account</a>
        </div>
    </div>
</body>
</html>
