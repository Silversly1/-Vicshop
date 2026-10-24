// VICS Store - API Client (User + Admin)
const API_BASE = 'api';

class ApiClient {
    async request(endpoint, options = {}) {
        const url = `${API_BASE}/${endpoint}`;
        const config = {
            headers: { 'Content-Type': 'application/json' },
            ...options
        };
        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }
        const response = await fetch(url, config);
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.error || 'Request failed');
        }
        return data;
    }

    // Auth - user and admin
    async login(email, password, role = 'user') {
        return this.request('auth/login.php', { method: 'POST', body: { email, password, role } });
    }
    async register(username, email, password) {
        return this.request('auth/register.php', { method: 'POST', body: { username, email, password } });
    }
    async logout() {
        return this.request('auth/logout.php', { method: 'POST' });
    }
    async me() {
        return this.request('auth/me.php', { method: 'GET' });
    }

    // Products
    async getProducts(category = null) {
        const qs = category ? `?category=${encodeURIComponent(category)}` : '';
        return this.request(`products.php${qs}`, { method: 'GET' });
    }
    async getProduct(id) {
        return this.request(`product.php?id=${id}`, { method: 'GET' });
    }
    async createProduct(data) {
        return this.request('products.php', { method: 'POST', body: data });
    }
    async updateProduct(id, data) {
        return this.request(`product.php?id=${id}`, { method: 'PUT', body: data });
    }
    async deleteProduct(id) {
        return this.request(`product.php?id=${id}`, { method: 'DELETE' });
    }

    // Categories
    async getCategories() {
        return this.request('categories.php', { method: 'GET' });
    }

    // Cart
    async getCart() {
        return this.request('cart.php', { method: 'GET' });
    }
    async addToCart(productId, quantity = 1) {
        return this.request('cart.php', { method: 'POST', body: { product_id: productId, quantity } });
    }
    async removeFromCart(productId) {
        return this.request('cart.php', { method: 'DELETE', body: { product_id: productId } });
    }

    // Checkout & Payment
    async checkout(details) {
        return this.request('checkout.php', { method: 'POST', body: details });
    }
    async payment(orderId, cardData) {
        return this.request('payment.php', { method: 'POST', body: { ...cardData, order_id: orderId } });
    }

    // Admin only
    async getOrders() {
        return this.request('admin/orders.php', { method: 'GET' });
    }
    async updateOrderStatus(orderId, status) {
        return this.request('admin/orders.php', { method: 'PUT', body: { order_id: orderId, status } });
    }
    async getDashboard() {
        return this.request('admin/dashboard.php', { method: 'GET' });
    }
}

const api = new ApiClient();
