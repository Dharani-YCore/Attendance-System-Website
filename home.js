// Configuration
const API_BASE_URL = 'http://localhost/Attendance-System-Website/api';

// DOM Elements
const logoutButton = document.getElementById('logoutButton');
const logoutModal = document.getElementById('logoutModal');
const cancelLogout = document.getElementById('cancelLogout');
const confirmLogout = document.getElementById('confirmLogout');
const userAvatar = document.getElementById('userAvatar');
const userName = document.getElementById('userName');
const userEmail = document.getElementById('userEmail');
const userId = document.getElementById('userId');
const usersTbody = document.getElementById('usersTbody');
const usersSearch = document.getElementById('usersSearch');
const prevPageBtn = document.getElementById('prevPage');
const nextPageBtn = document.getElementById('nextPage');
const pageInfo = document.getElementById('pageInfo');
const openAddUserBtn = document.getElementById('openAddUserBtn');
const addUserModal = document.getElementById('addUserModal');
const addUserForm = document.getElementById('addUserForm');
const cancelAddUser = document.getElementById('cancelAddUser');
const addName = document.getElementById('addName');
const addEmail = document.getElementById('addEmail');
const addPassword = document.getElementById('addPassword');

// Users state
let usersPage = 1;
let usersPerPage = 10;
let usersQuery = '';

// Check Authentication
function checkAuth() {
    const token = localStorage.getItem('token');
    const userStr = localStorage.getItem('user');
    
    if (!token || !userStr) {
        // Redirect to login if not authenticated
        window.location.href = 'index.html';
        return null;
    }
    
    try {
        return JSON.parse(userStr);
    } catch (error) {
        console.error('Error parsing user data:', error);
        window.location.href = 'index.html';
        return null;
    }
}

// Load User Data
function loadUserData() {
    const user = checkAuth();
    
    if (user) {
        // Set user avatar (first letter of name)
        const firstLetter = user.name ? user.name.charAt(0).toUpperCase() : 'U';
        userAvatar.textContent = firstLetter;
        
        // Set user name
        userName.textContent = user.name || 'User';
        
        // Set user email
        userEmail.textContent = user.email || '';
        
        // Set user ID
        userId.textContent = user.id || 'N/A';
    }
}

// Show Logout Modal
function showLogoutModal() {
    logoutModal.classList.remove('hidden');
}

// Hide Logout Modal
function hideLogoutModal() {
    logoutModal.classList.add('hidden');
}

// Handle Logout
function handleLogout() {
    // Clear localStorage
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    
    // Redirect to login page
    window.location.href = 'index.html';
}

// Event Listeners
logoutButton.addEventListener('click', showLogoutModal);
cancelLogout.addEventListener('click', hideLogoutModal);
confirmLogout.addEventListener('click', handleLogout);

// Close modal when clicking outside
logoutModal.addEventListener('click', (e) => {
    if (e.target === logoutModal) {
        hideLogoutModal();
    }
});

// Initialize on page load
window.addEventListener('DOMContentLoaded', () => {
    loadUserData();
    fetchAndRenderUsers();
});

// Fetch Users
async function fetchUsers(page = 1, perPage = 10, q = '') {
    const params = new URLSearchParams({ page: String(page), per_page: String(perPage) });
    if (q) params.set('q', q);
    const url = `${API_BASE_URL}/users.php?${params.toString()}`;
    const res = await fetch(url);
    if (!res.ok) {
        throw new Error(`Failed to fetch users (${res.status})`);
    }
    return res.json();
}

function renderUsers(items) {
    if (!Array.isArray(items) || items.length === 0) {
        usersTbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:16px;">No users found</td></tr>';
        return;
    }
    usersTbody.innerHTML = items.map(u => `
        <tr>
            <td>${u.id}</td>
            <td>${u.name ?? ''}</td>
            <td>${u.email ?? ''}</td>
            <td>${u.created_at ?? ''}</td>
        </tr>
    `).join('');
}

function updatePagination(pagination) {
    const { page, total_pages } = pagination;
    pageInfo.textContent = `Page ${page} of ${total_pages}`;
    prevPageBtn.disabled = page <= 1;
    nextPageBtn.disabled = page >= total_pages;
}

async function fetchAndRenderUsers() {
    try {
        usersTbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:16px;">Loading...</td></tr>';
        const data = await fetchUsers(usersPage, usersPerPage, usersQuery);
        if (!data.success) throw new Error(data.message || 'Error');
        renderUsers(data.data.items);
        updatePagination(data.data.pagination);
    } catch (e) {
        console.error(e);
        usersTbody.innerHTML = `<tr><td colspan="4" style="color:#c00; text-align:center; padding:16px;">${e.message}</td></tr>`;
    }
}

// Search with debounce
let searchTimer;
usersSearch && usersSearch.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        usersQuery = usersSearch.value.trim();
        usersPage = 1;
        fetchAndRenderUsers();
    }, 300);
});

// Pagination handlers
prevPageBtn && prevPageBtn.addEventListener('click', () => {
    if (usersPage > 1) {
        usersPage -= 1;
        fetchAndRenderUsers();
    }
});

nextPageBtn && nextPageBtn.addEventListener('click', () => {
    usersPage += 1;
    fetchAndRenderUsers();
});

// Modal helpers
function openAddUserModal() {
    if (addUserModal) addUserModal.classList.remove('hidden');
}
function closeAddUserModal() {
    if (addUserModal) addUserModal.classList.add('hidden');
    if (addUserForm) addUserForm.reset();
}

openAddUserBtn && openAddUserBtn.addEventListener('click', openAddUserModal);
cancelAddUser && cancelAddUser.addEventListener('click', closeAddUserModal);
addUserModal && addUserModal.addEventListener('click', (e) => {
    if (e.target === addUserModal) closeAddUserModal();
});

// Create user
addUserForm && addUserForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = addName.value.trim();
    const email = addEmail.value.trim();
    const password = addPassword.value;
    if (!name || !email || !password) {
        alert('Please fill all fields');
        return;
    }
    try {
        const res = await fetch(`${API_BASE_URL}/users.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, password })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to create user');
        closeAddUserModal();
        usersQuery = '';
        usersPage = 1;
        usersSearch && (usersSearch.value = '');
        fetchAndRenderUsers();
    } catch (err) {
        alert(err.message);
    }
});
