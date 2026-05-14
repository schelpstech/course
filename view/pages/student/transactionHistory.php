<?php
$payments = $model->getRows('payments', [
  'select' => 'payments.*, semesters.name as semester_name, academic_sessions.name as session_name',
  'join' => [
    'semesters' => ' on payments.semester_id = semesters.id',
    'academic_sessions' => ' on academic_sessions.id = semesters.session_id',
  ],
  'where' => ['student_id' => $_SESSION['user_id']],
  'order_by' => 'payments.created_at DESC'
]);
?>

<div class="row">
  <div class="col-sm-12">
    <div class="card">
      <div class="card-header table-card-header">
        <h5>Transaction History</h5>
        <small>View all your payment records and their approval status</small>
      </div>

      <div class="card-body">
        <div class="dt-responsive table-responsive">
          <table id="basic-btn" class="table table-striped table-bordered nowrap">

            <thead>
              <tr>
                <th>#</th>
                <th>Reference</th>
                <th>Semester</th>
                <th>Amount</th>
                <th>Mode</th>
                <th>Date</th>
                <th>Remark</th>
                <th>Status</th>
                <th>Evidence</th>
              </tr>
            </thead>

            <tbody>
              <?php if (!empty($payments)): ?>
                <?php $i = 1;
                foreach ($payments as $row): ?>
                  <tr>
                    <td><?= $i++; ?></td>

                    <td><?= htmlspecialchars($row['paymentReference']); ?></td>

                    <td><?= htmlspecialchars($row['semester_name'] . ' Semester ' . $row['session_name']); ?></td>

                    <td>₦<?= number_format($row['amount_paid'], 2); ?></td>

                    <td><?= ucfirst($row['payment_mode']); ?></td>

                    <td><?= date('d M Y', strtotime($row['payment_date'])); ?></td>

                    <td><?= ucfirst($row['payment_mode']); ?></td>
                    <td><?= htmlspecialchars($row['admin_note'] ?? 'No remarks Yet'); ?></td>
                    <td>
                      <?php
                      $status = $row['status'] ?? 'pending';

                      if ($status == 'successful') {
                        echo '<button class="btn btn-sm btn-success">Approved</button>';
                      } elseif ($status == 'failed') {
                        echo '<button class="btn btn-sm btn-danger">Rejected</button>';
                      } else {
                        echo '<button class="btn btn-sm btn-warning text-dark">Pending</button>';
                      }
                      ?>
                    </td>

                    <td>
                      <a href="../<?= $row['payment_proof']; ?>" target="_blank" class="btn btn-sm btn-primary">
                        View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center">No transactions found</td>
                </tr>
              <?php endif; ?>
            </tbody>

            <tfoot>
              <tr>
                <th>#</th>
                <th>Reference</th>
                <th>Semester</th>
                <th>Amount</th>
                <th>Mode</th>
                <th>Date</th>
                <th>Status</th>
                <th>Evidence</th>
              </tr>
            </tfoot>

          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // [ HTML5 Export Buttons ]
  $('#basic-btn').DataTable({
    dom: 'Bfrtip',
    buttons: ['copy', 'csv', 'excel', 'print']
  });

  // [ Column Selectors ]
  $('#cbtn-selectors').DataTable({
    dom: 'Bfrtip',
    buttons: [{
        extend: 'copyHtml5',
        exportOptions: {
          columns: [0, ':visible']
        }
      },
      {
        extend: 'excelHtml5',
        exportOptions: {
          columns: ':visible'
        }
      },
      {
        extend: 'pdfHtml5',
        exportOptions: {
          columns: [0, 1, 2, 5]
        }
      },
      'colvis'
    ]
  });

  // [ Excel - Cell Background ]
  $('#excel-bg').DataTable({
    dom: 'Bfrtip',
    buttons: [{
      extend: 'excelHtml5',
      customize: function(xlsx) {
        var sheet = xlsx.xl.worksheets['sheet1.xml'];
        $('row c[r^="F"]', sheet).each(function() {
          if ($('is t', this).text().replace(/[^\d]/g, '') * 1 >= 500000) {
            $(this).attr('s', '20');
          }
        });
      }
    }]
  });

  // [ Custom File (JSON) ]
  $('#pdf-json').DataTable({
    dom: 'Bfrtip',
    buttons: [{
      text: 'JSON',
      action: function(e, dt, button, config) {
        var data = dt.buttons.exportData();
        $.fn.dataTable.fileSave(new Blob([JSON.stringify(data)]), 'Export.json');
      }
    }]
  });
</script>