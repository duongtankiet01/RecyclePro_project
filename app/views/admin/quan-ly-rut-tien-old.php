<?php include __DIR__ . '/_layout.php'; ?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-money-check-alt me-2"></i>Quản lý rút tiền</h2>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6 class="card-title">Đang chờ xử lý</h6>
                    <h3><?php echo number_format($stats['totalPending'] ?? 0); ?></h3>
                    <small><?php echo number_format($stats['pendingAmount'] ?? 0); ?>đ</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6 class="card-title">Đã hoàn thành</h6>
                    <h3><?php echo number_format($stats['totalCompleted'] ?? 0); ?></h3>
                    <small><?php echo number_format($stats['completedAmount'] ?? 0); ?>đ</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h6 class="card-title">Đã từ chối</h6>
                    <h3><?php echo number_format($stats['totalRejected'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Withdrawals Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Danh sách yêu cầu rút tiền</h5>
            <button class="btn btn-sm btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-1"></i>Làm mới
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($withdrawals)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Chưa có yêu cầu rút tiền nào
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã YC</th>
                                <th>Hộ gia đình</th>
                                <th>SĐT</th>
                                <th>Điểm</th>
                                <th>Số tiền</th>
                                <th>Ngân hàng</th>
                                <th>Số TK</th>
                                <th>Chủ TK</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawals as $w): ?>
                                <tr id="row-<?php echo $w['request_id']; ?>">
                                    <td><strong><?php echo htmlspecialchars($w['request_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($w['household_name']); ?></td>
                                    <td><?php echo htmlspecialchars($w['phone']); ?></td>
                                    <td><?php echo number_format($w['points_deducted'], 0, ',', '.'); ?> điểm</td>
                                    <td class="fw-bold text-success"><?php echo number_format($w['amount_requested'], 0, ',', '.'); ?>đ</td>
                                    <td><?php echo htmlspecialchars($w['bank_name']); ?></td>
                                    <td><?php echo htmlspecialchars($w['bank_account_number']); ?></td>
                                    <td><?php echo htmlspecialchars($w['account_holder_name']); ?></td>
                                    <td>
                                        <?php if ($w['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Chờ xử lý</span>
                                        <?php elseif ($w['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Đã chuyển</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Từ chối</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($w['requested_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($w['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" onclick="confirmTransfer(<?php echo $w['request_id']; ?>, '<?php echo htmlspecialchars($w['request_code']); ?>')">
                                                <i class="fas fa-check me-1"></i>Xác nhận
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $w['request_id']; ?>, '<?php echo htmlspecialchars($w['request_code']); ?>')">
                                                <i class="fas fa-times me-1"></i>Từ chối
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function confirmTransfer(requestId, requestCode) {
    const note = prompt(`Xác nhận đã chuyển tiền cho yêu cầu ${requestCode}?\n\nGhi chú (tùy chọn):`, 'Đã chuyển tiền thành công');
    
    if (note === null) return; // User cancelled
    
    try {
        const response = await fetch('index.php?controller=api_withdrawal&action=confirm_transfer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                request_id: requestId,
                note: note
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ ' + result.message);
            location.reload();
        } else {
            alert('❌ ' + result.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Có lỗi xảy ra. Vui lòng thử lại.');
    }
}

async function rejectRequest(requestId, requestCode) {
    const reason = prompt(`Từ chối yêu cầu ${requestCode}?\n\nLý do từ chối:`, '');
    
    if (!reason || reason.trim() === '') {
        alert('Vui lòng nhập lý do từ chối');
        return;
    }
    
    if (!confirm(`Xác nhận từ chối yêu cầu ${requestCode}?\nĐiểm sẽ được hoàn lại cho hộ gia đình.`)) {
        return;
    }
    
    try {
        const response = await fetch('index.php?controller=api_withdrawal&action=reject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                request_id: requestId,
                reason: reason
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ ' + result.message);
            location.reload();
        } else {
            alert('❌ ' + result.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Có lỗi xảy ra. Vui lòng thử lại.');
    }
}
</script>
