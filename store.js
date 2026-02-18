const CartStore = {
    state: { count: 0 },
    listeners: [],
    init(initialCount) { this.state.count = parseInt(initialCount) || 0; },
    subscribe(callback) { this.listeners.push(callback); },
    notify() { this.listeners.forEach(callback => callback(this.state)); },
    setCount(newCount) { this.state.count = newCount; this.notify(); },

    async updateCart(action, id, name = null, price = null) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('id', id);
        if(name) formData.append('name', name);
        if(price) formData.append('price', price);

        const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.status === 'success') {
            this.setCount(data.cart_count);
            // Return new totals
            return { 
                success: true, 
                message: data.message, 
                new_total: data.new_total 
            };
        }
        return { success: false };
    },

    async updateQty(id, newQty) {
        const formData = new FormData();
        formData.append('action', 'update_qty');
        formData.append('id', id);
        formData.append('qty', newQty);

        const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.status === 'success') {
            this.setCount(data.cart_count);
            // Return new totals
            return { 
                success: true, 
                new_total: data.new_total, 
                new_item_subtotal: data.new_item_subtotal 
            };
        }
        return { success: false };
    }
};