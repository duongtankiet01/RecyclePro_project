/**
 * Reward Redemption - Đổi điểm lấy sản phẩm
 */

let householdProducts = [];
let currentPoints = 0;

function initRewardRedeem(products, points) {
    householdProducts = products || [];
    currentPoints = points || 0;
    console.log('Reward redeem initialized:', {
        productsCount: householdProducts.length,
        currentPoints: currentPoints,
        products: householdProducts
    });
}

async function exchangeProduct(productId) {
    console.log('Exchange product called with ID:', productId);
    
    if (!productId) {
        alert('ID sản phẩm không hợp lệ');
        return;
    }
    
    console.log('Searching in products:', householdProducts);
    const product = householdProducts.find((item) => parseInt(item.product_id) === parseInt(productId));
    
    console.log('Found product:', product);
    
    if (!product) {
        alert('Không tìm thấy sản phẩm. ID: ' + productId + '\nTổng số sản phẩm: ' + householdProducts.length);
        return;
    }

    // Kiểm tra điểm
    if (!product.can_redeem) {
        alert('Bạn không đủ điểm để đổi sản phẩm này.\n\nĐiểm hiện có: ' + currentPoints.toLocaleString() + ' điểm\nĐiểm cần thiết: ' + product.required_points.toLocaleString() + ' điểm');
        return;
    }

    // Confirm
    const confirmMsg = 'Xác nhận đổi điểm?\n\n' +
        'Sản phẩm: ' + product.name + '\n' +
        'Điểm cần: ' + product.points_label + '\n' +
        'Doanh nghiệp: ' + product.enterprise + '\n\n' +
        'Sau khi đổi, điểm còn lại: ' + (currentPoints - product.required_points).toLocaleString() + ' điểm';
    
    if (!confirm(confirmMsg)) {
        return;
    }

    try {
        const response = await fetch('index.php?controller=api_reward_product&action=redeem', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });

        const result = await response.json();
        console.log('API Response:', result);

        if (result.success) {
            alert('✅ ' + result.message + '\n\n' + (result.data?.message || 'Đơn đổi thưởng đã được tạo thành công!'));
            
            // Reload lại trang để cập nhật lịch sử đổi quà và hiển thị mã
            window.location.reload();
        } else {
            console.error('API Error:', result.message);
            alert('❌ Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Exchange error:', error);
        alert('❌ Lỗi kết nối server. Vui lòng thử lại!');
    }
}
