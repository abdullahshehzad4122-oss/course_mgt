<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header("HTTP/1.0 403 Forbidden");
    exit("Access denied.");
}
checkAccess();

$user_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$assignment_id) {
    header('Location: my_assignments.php');
    exit;
}

// Get Assignment Details
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, c.course_code 
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE a.assignment_id = ? AND e.student_id = ? AND e.status = 'active'
");
$stmt->execute([$assignment_id, $user_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die("Assignment not found or access denied.");
}

// Check existing submission
$stmt = $pdo->prepare("SELECT s.*, g.marks_obtained, g.feedback FROM submissions s LEFT JOIN grades g ON s.submission_id = g.submission_id WHERE s.assignment_id = ? AND s.student_id = ?");
$stmt->execute([$assignment_id, $user_id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

$is_past_due = strtotime($assignment['due_date']) < time();
$error = '';
$success = '';

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['submission_file'])) {
    if ($submission && $submission['status'] == 'graded') {
        $error = "This assignment is already graded.";
    } elseif ($is_past_due && !$submission) {
        $error = "The deadline has passed.";
    } else {
        $file = $_FILES['submission_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = session_id() . '_' . time() . '.' . $ext;
            $destination = __DIR__ . '/../uploads/submissions/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $file_path = 'uploads/submissions/' . $filename;
                
                if ($submission) {
                    $stmt = $pdo->prepare("UPDATE submissions SET file_path = ?, status = 'submitted', submission_date = NOW() WHERE submission_id = ?");
                    $stmt->execute([$file_path, $submission['submission_id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, status) VALUES (?, ?, ?, 'submitted')");
                    $stmt->execute([$assignment_id, $user_id, $file_path]);
                }
                
                header("Location: my_assignments.php?success=" . urlencode("Assignment submitted successfully."));
                exit;
            } else {
                $error = "Failed to save uploaded file.";
            }
        } else {
            $error = "File upload error. Error code: " . $file['error'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.student-glass {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
}
body { background-color: #f4f6f9 !important; }
.content-wrapper { background-color: transparent !important; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold">Assignment Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/my_assignments.php">Assignments</a></li>
                        <li class="breadcrumb-item active">Submit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger student-glass"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-7">
                    <div class="card student-glass border-0 mb-4">
                        <div class="card-body">
                            <h4 class="font-weight-bold text-primary"><?= htmlspecialchars($assignment['title']) ?></h4>
                            <p class="text-muted border-bottom pb-2 mb-3">
                                <i class="fas fa-book mr-1"></i> <?= htmlspecialchars($assignment['course_code']) ?> - <?= htmlspecialchars($assignment['course_name']) ?>
                            </p>
                            
                            <h6 class="font-weight-bold">Description:</h6>
                            <div class="p-3 bg-light rounded mb-4" style="min-height: 100px;">
                                <?= nl2br(htmlspecialchars($assignment['description'])) ?>
                            </div>
                            
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                    <span class="font-weight-bold">Due Date:</span>
                                    <span class="<?= $is_past_due ? 'text-danger font-weight-bold' : '' ?>">
                                        <?= date('l, F j, Y \a\t g:i A', strtotime($assignment['due_date'])) ?>
                                    </span>
                                </li>
                                <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                                    <span class="font-weight-bold">Status:</span>
                                    <?php if ($submission && $submission['status'] == 'graded'): ?>
                                        <span class="badge badge-success px-3 py-1">Graded</span>
                                    <?php elseif ($submission): ?>
                                        <span class="badge badge-primary px-3 py-1">Submitted</span>
                                    <?php elseif ($is_past_due): ?>
                                        <span class="badge badge-danger px-3 py-1">Missing</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary px-3 py-1">Pending</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <?php if ($submission && $submission['status'] == 'graded'): ?>
                    <div class="card student-glass border-0 bg-success text-white">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-award fa-4x mb-3 opacity-75"></i>
                            <h3 class="font-weight-bold">Grade: <?= htmlspecialchars($submission['marks_obtained']) ?></h3>
                            <?php if ($submission['feedback']): ?>
                                <hr class="border-white opacity-25">
                                <h6>Instructor Feedback:</h6>
                                <p class="font-italic">"<?= nl2br(htmlspecialchars($submission['feedback'])) ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card student-glass border-0">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <h4 class="card-title font-weight-bold"><i class="fas fa-cloud-upload-alt text-primary mr-2"></i> Submit Work</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($is_past_due && !$submission): ?>
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> The deadline for this assignment has passed. Submissions are locked.
                                </div>
                            <?php else: ?>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Select File</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="submission_file" name="submission_file" required>
                                            <label class="custom-file-label" for="submission_file">Choose file...</label>
                                        </div>
                                        <small class="form-text text-muted mt-2">Accepted formats: PDF, DOCX, ZIP, JPG, PNG. Max size: 10MB.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block rounded-pill shadow-sm py-2">
                                        <?= $submission ? 'Update Submission' : 'Submit Assignment' ?>
                                    </button>
                                </form>
                                <?php if ($submission): ?>
                                    <div class="mt-4 text-center">
                                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($submission['file_path']) ?>" target="_blank" class="text-info">
                                            <i class="fas fa-external-link-alt mr-1"></i> View current submission
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- bs-custom-file-input for updating file label -->
<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  bsCustomFileInput.init();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
