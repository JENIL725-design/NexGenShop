document.addEventListener('DOMContentLoaded', function() {

    // Init Badge
    const badge = document.getElementById('cart-count');
    if (typeof CartStore !== 'undefined') {
        CartStore.init(badge ? badge.innerText : 0);
        CartStore.subscribe((state) => {
            if (badge) {
                badge.innerText = state.count;
                badge.style.transform = "scale(1.5)";
                setTimeout(() => badge.style.transform = "scale(1)", 200);
            }
        });
    }

    document.body.addEventListener("click", async function (e) {
        
        // --- ADD BUTTON ---
        const addBtn = e.target.closest(".add-btn");
        if (addBtn) {
            e.preventDefault();
            const res = await CartStore.updateCart("add", addBtn.dataset.id, addBtn.dataset.name, addBtn.dataset.price);
            if (res.success) Swal.fire({ icon: 'success', title: res.message, toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        }

        // --- REMOVE BUTTON ---
        const removeBtn = e.target.closest(".remove-btn");
        if (removeBtn) {
            e.preventDefault();
            handleRemove(removeBtn.dataset.id);
        }

        // --- QUANTITY (+ / -) ---
        const qtyBtn = e.target.closest(".qty-btn");
        if (qtyBtn) {
            e.preventDefault();
            const id = qtyBtn.dataset.id;
            const change = parseInt(qtyBtn.dataset.change);
            const qtyEl = document.getElementById(`qty-${id}`);
            const itemSubEl = document.getElementById(`subtotal-${id}`);
            
            if (qtyEl) {
                let currentQty = parseInt(qtyEl.innerText);
                let newQty = currentQty + change;

                // ✨ LOGIC FIX: If Qty goes to 0, ask to remove instead of blocking
                if (newQty < 1) {
                    handleRemove(id); // Call the remove function
                    return;
                }

                const res = await CartStore.updateQty(id, newQty);
                if (res.success) {
                    // Update Quantity Text
                    qtyEl.innerText = newQty;
                    // Update Item Subtotal
                    if (itemSubEl && res.new_item_subtotal) itemSubEl.innerText = res.new_item_subtotal;
                    // Update Grand Totals
                    if (res.new_total) {
                        document.getElementById('summary-total').innerText = res.new_total;
                        document.getElementById('summary-subtotal').innerText = res.new_total;
                    }
                }
            }
        }
    });

    // Helper Function to Remove Item (Used by both Trash Icon and Minus Button)
    function handleRemove(id) {
        const row = document.getElementById("row-" + id);
        
        Swal.fire({ 
            title: "Remove item?", 
            icon: "warning", 
            showCancelButton: true, 
            confirmButtonText: "Yes, remove it!",
            confirmButtonColor: "#dc3545"
        })
        .then(async (result) => {
            if (result.isConfirmed) {
                // Send 0 or 'remove' action to backend
                const res = await CartStore.updateCart("remove", id);
                if (res.success) {
                    if (row) {
                        row.style.opacity = "0";
                        setTimeout(() => row.remove(), 300);
                    }
                    // Update Totals
                    if (res.new_total) {
                        document.getElementById('summary-total').innerText = res.new_total;
                        document.getElementById('summary-subtotal').innerText = res.new_total;
                    }
                    // If cart is empty, reload
                    if (CartStore.state.count === 0) setTimeout(() => location.reload(), 300);
                }
            }
        });
    }
});