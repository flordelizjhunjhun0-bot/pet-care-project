
<?php

require_once '../db/db.php';

$user_id = require_user($conn);
$medical_id = valid_id($_GET['id'] ?? 0);

$username = $_SESSION['username'] ?? 'Pet Owner';
$error = '';

if (!$medical_id) {
    header('Location: my_pet.php');
    exit;
}

/* GET MEDICAL RECORD AND VERIFY OWNERSHIP */
$stmt = $conn->prepare(
    "SELECT medical_history.*, pets.id AS pet_id, pets.pet_name
     FROM medical_history
     INNER JOIN pets ON medical_history.pet_id = pets.id
     WHERE medical_history.id = ?
     AND pets.user_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $medical_id, $user_id);
$stmt->execute();

$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    http_response_code(404);
    exit('Medical record not found or access denied.');
}

$pet_id = (int)$record['pet_id'];

$date = $record['date'];
$diagnosis = $record['diagnosis'];
$treatment = $record['treatment'];
$notes = $record['notes'];

/* UPDATE MEDICAL RECORD */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $date = trim($_POST['date'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    /* VALIDATE DATE */
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date;

    if (!$validDate || $date > date('Y-m-d')) {

        $error = 'Please enter a valid medical record date.';

    } elseif ($diagnosis === '') {

        $error = 'Please enter the diagnosis.';

    } elseif (mb_strlen($diagnosis) > 255) {

        $error = 'Diagnosis must not exceed 255 characters.';

    } elseif ($treatment === '') {

        $error = 'Please enter the treatment.';

    } elseif (mb_strlen($treatment) > 255) {

        $error = 'Treatment must not exceed 255 characters.';

    } elseif (mb_strlen($notes) > 2000) {

        $error = 'Notes must not exceed 2000 characters.';

    } else {

        /* UPDATE USING PREPARED STATEMENT */
        $stmt = $conn->prepare(
            "UPDATE medical_history
             SET date = ?,
                 diagnosis = ?,
                 treatment = ?,
                 notes = ?
             WHERE id = ?
             AND pet_id IN (
                 SELECT id
                 FROM pets
                 WHERE user_id = ?
             )"
        );

        if (!$stmt) {

            $error = 'Unable to prepare update. Please try again.';

        } else {

            $stmt->bind_param(
                "ssssii",
                $date,
                $diagnosis,
                $treatment,
                $notes,
                $medical_id,
                $user_id
            );

            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: view.php?pet_id=" . $pet_id
                );

                exit;

            } else {

                $error = 'Unable to update medical record. Please try again.';
                $stmt->close();

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Medical History | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">

    <style>

        .medical-form-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 15px;
            padding: 28px;
            max-width: 750px;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
        }

        .medical-form-description {
            color: #6b7785;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .medical-form-group {
            margin-bottom: 20px;
        }

        .medical-form-group label {
            display: block;
            color: #354454;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .medical-form-group input,
        .medical-form-group textarea {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid #dce3e8;
            border-radius: 8px;
            background: #ffffff;
            color: #263445;
            font-size: 14px;
            font-family: Arial, Helvetica, sans-serif;
            box-sizing: border-box;
            outline: none;
        }

        .medical-form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .medical-form-group input:focus,
        .medical-form-group textarea:focus {
            border-color: #285d6b;
            box-shadow: 0 0 0 3px rgba(40,93,107,.10);
        }

        .medical-form-error {
            padding: 13px 15px;
            background: #fdeaea;
            color: #b83232;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .medical-form-help {
            color: #7b8792;
            font-size: 12px;
            margin-top: 7px;
        }

        .medical-form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .medical-submit-button,
        .medical-cancel-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 45px;
            padding: 11px 19px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .medical-submit-button {
            background: #285d6b;
            color: white;
        }

        .medical-submit-button:hover {
            background: #173b4d;
        }

        .medical-cancel-button {
            background: #e9eef2;
            color: #203747;
        }

        .medical-cancel-button:hover {
            background: #dce5eb;
        }

        @media(max-width: 768px) {

            .medical-form-card {
                padding: 20px;
            }

            .medical-form-actions > * {
                width: 100%;
            }

        }

    </style>

</head>

<body class="professional-dashboard">

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="sidebar-brand">

        <div class="brand-icon">🐾</div>

        <div>
            <h1>Pet-Care</h1>
            <span>Veterinary Portal</span>
        </div>

    </div>

    <p class="sidebar-label">MAIN MENU</p>

    <nav class="sidebar-nav">

        <a href="user_dashboard.php">
            <span class="nav-icon">▦</span>
            Dashboard
        </a>

        <a href="add_pet.php">
            <span class="nav-icon">＋</span>
            Add Pet
        </a>

        <a href="my_pet.php" class="active">
            <span class="nav-icon">🐾</span>
            My Pets
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="sidebar-user">

            <div class="user-avatar">👤</div>

            <div>
                <strong><?= h($username) ?></strong>
                <span>Pet Owner</span>
            </div>

        </div>

        <a href="../Account/logout.php" class="logout-link">
            <span class="nav-icon">↪</span>
            Logout
        </a>

    </div>

</aside>

<!-- MAIN CONTENT -->
<main class="main professional-main">

    <header class="dashboard-topbar">

        <div>
            <span class="topbar-label">HEALTH RECORDS</span>
            <h2>Edit Medical History</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= date('F d, Y') ?></strong>
        </div>

    </header>

    <section class="dashboard-section">

        <div class="section-heading">

            <div>
                <span class="section-eyebrow">MEDICAL RECORD MANAGEMENT</span>
                <h3>Update Medical Information</h3>
            </div>

        </div>

        <div class="medical-form-card">

            <p class="medical-form-description">
                Update the medical history of
                <strong><?= h($record['pet_name']) ?></strong>.
                Make sure all information is accurate.
            </p>

            <?php if ($error !== ''): ?>

                <div class="medical-form-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(csrf_token()) ?>"
                >

                <div class="medical-form-group">

                    <label for="date">Date</label>

                    <input
                        type="date"
                        id="date"
                        name="date"
                        max="<?= date('Y-m-d') ?>"
                        value="<?= h($date) ?>"
                        required
                    >

                </div>

                <div class="medical-form-group">

                    <label for="diagnosis">Diagnosis</label>

                    <input
                        type="text"
                        id="diagnosis"
                        name="diagnosis"
                        maxlength="255"
                        value="<?= h($diagnosis) ?>"
                        placeholder="Enter diagnosis"
                        required
                    >

                </div>

                <div class="medical-form-group">

                    <label for="treatment">Treatment</label>

                    <input
                        type="text"
                        id="treatment"
                        name="treatment"
                        maxlength="255"
                        value="<?= h($treatment) ?>"
                        placeholder="Enter treatment"
                        required
                    >

                </div>

                <div class="medical-form-group">

                    <label for="notes">Additional Notes</label>

                    <textarea
                        id="notes"
                        name="notes"
                        maxlength="2000"
                        placeholder="Enter additional notes..."
                    ><?= h($notes) ?></textarea>

                    <p class="medical-form-help">
                        Optional: Add other important information
                        about the pet's medical condition.
                    </p>

                </div>

                <div class="medical-form-actions">

                    <button
                        type="submit"
                        name="save"
                        class="medical-submit-button"
                    >
                        Save Changes
                    </button>

                    <a
                        href="view.php?pet_id=<?= (int)$pet_id ?>"
                        class="medical-cancel-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </section>

</main>

</body>
</html>
