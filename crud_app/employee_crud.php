<?php
require_once '../config.php';
requireAdmin();
$conn = getDBConnection();
$result = $conn->query("SELECT * FROM employees ORDER BY user_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Employee Management</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
	color: #566787;
	background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
	font-family: 'Varela Round', sans-serif;
	font-size: 13px;
	min-height: 100vh;
}
.back-nav {
	padding: 15px 20px;
	background: white;
	margin-bottom: 20px;
	box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.back-nav a {
	color: #667eea;
	text-decoration: none;
	font-weight: 600;
	font-size: 16px;
	transition: all 0.3s;
}
.back-nav a:hover {
	color: #764ba2;
}
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
}
.table-wrapper {
	background: #fff;
	padding: 20px 25px;
	border-radius: 3px;
	min-width: 800px;
	box-shadow: 0 1px 1px rgba(0,0,0,.05);
}
.table-title {        
	padding-bottom: 15px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #fff;
	padding: 16px 20px;
	min-width: 100%;
	margin: -20px -25px 10px;
	border-radius: 3px 3px 0 0;
}
.table-title h2 {
	margin: 5px 0 0;
	font-size: 22px;
}
.table-title .btn-group {
	float: right;
}
.table-title .btn {
	color: #fff;
	float: right;
	font-size: 13px;
	border: none;
	min-width: 50px;
	border-radius: 2px;
	border: none;
	outline: none !important;
	margin-left: 10px;
}
.table-title .btn i {
	float: left;
	font-size: 21px;
	margin-right: 5px;
}
.table-title .btn span {
	float: left;
	margin-top: 2px;
}
table.table tr th, table.table tr td {
	border-color: #e9e9e9;
	padding: 12px 15px;
	vertical-align: middle;
}
table.table tr th:first-child {
	width: 60px;
}
table.table tr th:last-child {
	width: 100px;
}
table.table-striped tbody tr:nth-of-type(odd) {
	background-color: #fcfcfc;
}
table.table-striped.table-hover tbody tr:hover {
	background: #f5f5f5;
}
table.table th i {
	font-size: 13px;
	margin: 0 5px;
	cursor: pointer;
}	
table.table td:last-child i {
	opacity: 0.9;
	font-size: 22px;
	margin: 0 5px;
}
table.table td a {
	font-weight: bold;
	color: #566787;
	display: inline-block;
	text-decoration: none;
	outline: none !important;
}
table.table td a:hover {
	color: #2196F3;
}
table.table td a.edit {
	color: #FFC107;
}
table.table td a.delete {
	color: #F44336;
}
table.table td i {
	font-size: 19px;
}
.modal .modal-dialog {
	max-width: 400px;
}
.modal .modal-header, .modal .modal-body, .modal .modal-footer {
	padding: 20px 30px;
}
.modal .modal-content {
	border-radius: 3px;
	font-size: 14px;
}
.modal .modal-footer {
	background: #ecf0f1;
	border-radius: 0 0 3px 3px;
}
.modal .modal-title {
	display: inline-block;
}
.modal .form-control {
	border-radius: 2px;
	box-shadow: none;
	border-color: #dddddd;
}
.modal textarea.form-control {
	resize: vertical;
}
.modal .btn {
	border-radius: 2px;
	min-width: 100px;
}	
.modal form label {
	font-weight: normal;
}	
.modal .form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
}
.swal2-popup { border-radius: 20px !important; }
.swal2-confirm { border-radius: 10px !important; padding: 12px 30px !important; }
.swal2-cancel { border-radius: 10px !important; padding: 12px 30px !important; }
.hint-text {
    font-size: 13px;
    color: #999;
    margin-top: 15px;
}

/* Responsive Styles */
@media (max-width: 1024px) {
    .container-xl {
        max-width: 100%;
        padding: 0 15px;
    }
    .table-wrapper {
        min-width: 700px;
    }
    .table-title h2 {
        font-size: 20px;
    }
}

@media (max-width: 768px) {
    .back-nav {
        padding: 12px 15px;
    }
    .back-nav a {
        font-size: 14px;
    }
    
    .container-xl {
        padding: 0 10px;
    }
    
    .table-responsive {
        margin: 15px 0;
    }
    
    .table-wrapper {
        padding: 15px;
        min-width: 650px;
    }
    
    .table-title {
        padding: 12px 15px;
        margin: -15px -15px 10px;
        flex-wrap: wrap;
    }
    
    .table-title h2 {
        font-size: 18px;
        margin-bottom: 10px;
        width: 100%;
    }
    
    .table-title .row {
        width: 100%;
    }
    
    .table-title .col-sm-6 {
        width: 100%;
        text-align: center;
        margin: 5px 0;
    }
    
    .table-title .btn {
        float: none;
        margin: 5px auto;
        display: inline-block;
    }
    
    table.table {
        font-size: 12px;
    }
    
    table.table tr th, 
    table.table tr td {
        padding: 10px 8px;
    }
    
    table.table td i {
        font-size: 18px;
    }
    
    table.table td:last-child i {
        font-size: 20px;
        margin: 0 3px;
    }
    
    .hint-text {
        font-size: 12px;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .back-nav {
        padding: 10px;
    }
    
    .back-nav a {
        font-size: 13px;
    }
    
    .table-wrapper {
        padding: 10px;
        min-width: 600px;
        border-radius: 0;
    }
    
    .table-title {
        padding: 10px;
        margin: -10px -10px 10px;
    }
    
    .table-title h2 {
        font-size: 16px;
    }
    
    .table-title .btn {
        font-size: 12px;
        padding: 8px 12px;
    }
    
    .table-title .btn i {
        font-size: 18px;
        margin-right: 3px;
    }
    
    .table-title .btn span {
        font-size: 11px;
    }
    
    table.table {
        font-size: 11px;
    }
    
    table.table tr th, 
    table.table tr td {
        padding: 8px 6px;
    }
    
    table.table td i {
        font-size: 16px;
    }
    
    table.table td:last-child i {
        font-size: 18px;
    }
    
    .modal-dialog {
        margin: 10px;
    }
    
    .modal .modal-header, 
    .modal .modal-body, 
    .modal .modal-footer {
        padding: 15px 20px;
    }
    
    .modal .modal-title {
        font-size: 18px;
    }
    
    .modal .form-group {
        margin-bottom: 15px;
    }
    
    .modal .btn {
        min-width: 80px;
        padding: 8px 15px;
        font-size: 13px;
    }
}

@media (max-width: 400px) {
    .table-wrapper {
        min-width: 550px;
    }
    
    .table-title h2 {
        font-size: 14px;
    }
    
    .table-title .btn span {
        display: none;
    }
    
    .table-title .btn i {
        margin-right: 0;
    }
    
    table.table {
        font-size: 10px;
    }
    
    table.table tr th, 
    table.table tr td {
        padding: 6px 4px;
    }
}
</style>
</head>
<body>
<div class="back-nav">
    <a href="../admin_dashboard.php">← Back to Admin Dashboard</a>
</div>
<div class="container-xl">
	<div class="table-responsive">
		<div class="table-wrapper">
			<div class="table-title">
				<div class="row">
					<div class="col-sm-6">
						<h2>👥 Manage <b>Employees</b></h2>
					</div>
					<div class="col-sm-6">
						<a href="#addEmployeeModal" class="btn btn-success" data-toggle="modal"><i class="material-icons">&#xE147;</i> <span>Add New Employee</span></a>
					</div>
				</div>
			</div>
			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th>
							<span class="custom-checkbox">
								<input type="checkbox" id="selectAll">
								<label for="selectAll"></label>
							</span>
						</th>
						<th>Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>Address</th>
						<th>Role</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td>
        <span class="custom-checkbox">
            <input type="checkbox" class="row-checkbox" value="<?= $row['user_id'] ?>">
            <label></label>
        </span>
    </td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($row['address']) ?></td>
    <td><?= ucfirst($row['role']) ?></td>
    <td>
        <a href="#editEmployeeModal<?= $row['user_id'] ?>" class="edit" data-toggle="modal">
            <i class="material-icons" title="Edit">&#xE254;</i>
        </a>
        <a href="#" class="delete" onclick="deleteEmployee(<?= $row['user_id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>'); return false;">
            <i class="material-icons" title="Delete">&#xE872;</i>
        </a>
    </td>
</tr>

<!-- EDIT MODAL -->
<div id="editEmployeeModal<?= $row['user_id'] ?>" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="employee_update.php">
        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
        <div class="modal-header">
          <h4>Edit Employee</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Name</label>
            <input name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input name="email" value="<?= htmlspecialchars($row['email']) ?>" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input name="phone" value="<?= htmlspecialchars($row['phone'] ?? '') ?>" class="form-control">
          </div>
          <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" required><?= htmlspecialchars($row['address']) ?></textarea>
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control">
              <option value="admin" <?= $row['role']=='admin'?'selected':'' ?>>Admin</option>
              <option value="user" <?= $row['role']=='user'?'selected':'' ?>>User</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button class="btn btn-info" type="submit">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endwhile; ?>
</tbody>
			</table>
			<div class="clearfix">
				<div class="hint-text">Showing <b><?= $result->num_rows ?></b> entries</div>
			</div>
		</div>
	</div>        
</div>

<!-- Add Modal HTML -->
<div id="addEmployeeModal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="POST" action="employee_insert.php">
                <div class="modal-header">
                    <h4 class="modal-title">Add Employee</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Employee</button>
                </div>
            </form>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
	$('[data-toggle="tooltip"]').tooltip();
	
	const rowCheckboxes = $('.row-checkbox');
    const selectAll = $('#selectAll');

    selectAll.on('change', function () {
        rowCheckboxes.prop('checked', this.checked);
    });

    rowCheckboxes.on('change', function () {
        if (rowCheckboxes.length === rowCheckboxes.filter(':checked').length) {
            selectAll.prop('checked', true);
        } else {
            selectAll.prop('checked', false);
        }
    });
    
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('success') === 'added') {
        Swal.fire({
            icon: 'success',
            title: 'Employee Added!',
            text: 'New employee has been added successfully',
            timer: 2000,
            showConfirmButton: false
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (urlParams.get('success') === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: 'Employee details have been updated',
            timer: 2000,
            showConfirmButton: false
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (urlParams.get('success') === 'deleted') {
        Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: 'Employee has been deleted successfully',
            timer: 2000,
            showConfirmButton: false
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (urlParams.get('error') {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Operation failed. Please try again.',
            confirmButtonColor: '#eb3349'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function deleteEmployee(id, name) {
    Swal.fire({
        title: 'Delete Employee?',
        html: `Are you sure you want to delete <strong>${name}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#eb3349',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `employee_delete.php?id=${id}`;
        }
    });
}
</script>
</body>
</html>