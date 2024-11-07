<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkAdmin();

// Complex join query to get complete student history
$query = "
    SELECT 
        s.student_id,
        s.first_name,
        s.last_name,
        r.room_no,
        r.room_type,
        a.allotment_date,
        a.end_date,
        f.amount as fee_amount,
        f.status as fee_status,
        f.paid_on,
        COUNT(DISTINCT c.complaint_id) as total_complaints,
        SUM(CASE WHEN c.status = 'Pending' THEN 1 ELSE 0 END) as pending_complaints
    FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id
    LEFT JOIN room r ON a.room_no = r.room_no
    LEFT JOIN fee f ON s.student_id = f.student_id
    LEFT JOIN complaint c ON s.student_id = c.student_id
    GROUP BY s.student_id, s.first_name, s.last_name, r.room_no, r.room_type, 
             a.allotment_date, a.end_date, f.amount, f.status, f.paid_on
    ORDER BY s.student_id DESC";

$students = $pdo->query($query)->fetchAll();

include '../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Student History Report</h2>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="studentHistoryTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Room Details</th>
                            <th>Allotment Period</th>
                            <th>Fee Details</th>
                            <th>Complaints</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $student): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                <br>
                                <small class="text-muted">ID: <?= $student['student_id'] ?></small>
                            </td>
                            <td>
                                <?php if ($student['room_no']): ?>
                                    Room <?= htmlspecialchars($student['room_no']) ?>
                                    <br>
                                    <span class="badge bg-info"><?= $student['room_type'] ?></span>
                                <?php else: ?>
                                    No room assigned
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['allotment_date']): ?>
                                    <?= date('d-m-Y', strtotime($student['allotment_date'])) ?>
                                    to
                                    <?= date('d-m-Y', strtotime($student['end_date'])) ?>
                                <?php else: ?>
                                    No allotment
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['fee_amount']): ?>
                                    ₹<?= number_format($student['fee_amount'], 2) ?>
                                    <br>
                                    <span class="badge bg-<?= $student['fee_status'] == 'Paid' ? 'success' : 'warning' ?>">
                                        <?= $student['fee_status'] ?>
                                    </span>
                                    <?php if ($student['paid_on']): ?>
                                        <br>
                                        <small>Paid on: <?= date('d-m-Y', strtotime($student['paid_on'])) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    No fee records
                                <?php endif; ?>
                            </td>
                            <td>
                                Total: <?= $student['total_complaints'] ?>
                                <br>
                                Pending: <?= $student['pending_complaints'] ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#studentHistoryTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25
    });
});
</script>

<?php include '../../includes/admin_footer.php'; ?>
