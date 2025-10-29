(function(){
  const $ = (sel) => document.querySelector(sel);

  const API = {
    base: 'http://localhost/Attendance-System-Website/api',
    get users(){ return `${this.base}/users.php`; },
    get stats(){ return `${this.base}/stats.php`; }
  };

  function updateDateTime(){
    const now = new Date();
    const dd = String(now.getDate()).padStart(2,'0');
    const mm = String(now.getMonth()+1).padStart(2,'0');
    const yyyy = now.getFullYear();
    const HH = String(now.getHours()).padStart(2,'0');
    const MM = String(now.getMinutes()).padStart(2,'0');
    $('#dateText').textContent = `Date: ${dd}-${mm}-${yyyy}`;
    $('#timeText').textContent = `Time: ${HH}:${MM}`;
  }

  async function fetchTotalUsers(){
    try{
      const res = await fetch(API.stats);
      if (res.ok) {
        const data = await res.json();
        if (data && data.success && data.data && typeof data.data.total_users === 'number'){
          $('#totalUsers').textContent = String(data.data.total_users);
          return;
        }
      }
      const res2 = await fetch(`${API.users}?page=1&per_page=1`);
      const data2 = await res2.json();
      if(data2 && data2.success && data2.data && data2.data.pagination){
        $('#totalUsers').textContent = data2.data.pagination.total ?? '--';
      } else {
        $('#totalUsers').textContent = '--';
      }
    }catch(e){
      $('#totalUsers').textContent = '--';
    }
  }

  async function fetchUsers(){
    const res = await fetch(`${API.users}?page=1&per_page=100`);
    if (!res.ok) throw new Error('Failed to load users');
    return res.json();
  }

  function renderUsers(items){
    const tbody = $('#usersTableBody');
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0){
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:12px;">No users</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(u => `
      <tr>
        <td>${u.id}</td>
        <td>${u.name ?? ''}</td>
        <td>${u.email ?? ''}</td>
        <td>
          <button class="icon-btn view-btn" title="View" data-id="${u.id}">👁️</button>
          <button class="icon-btn edit-btn" title="Edit" data-id="${u.id}">✏️</button>
          <button class="icon-btn delete-btn" title="Delete" data-id="${u.id}">🗑️</button>
        </td>
      </tr>
    `).join('');
  }

  async function loadUsers(){
    try{
      const data = await fetchUsers();
      if (data && data.success && data.data && Array.isArray(data.data.items)){
        renderUsers(data.data.items);
      }
    }catch(err){
      const tbody = $('#usersTableBody');
      if (tbody) tbody.innerHTML = `<tr><td colspan="4" style="color:#c00; text-align:center; padding:12px;">${err.message}</td></tr>`;
    }
  }

  // Add user modal
  const addUserBtn = $('#addUserBtn');
  const addUserModal = $('#addUserModal');
  const addUserForm = $('#addUserForm');
  const addName = $('#addName');
  const addEmail = $('#addEmail');
  const addPassword = $('#addPassword');
  const cancelAddUser = $('#cancelAddUser');

  function openAddModal(){ addUserModal && addUserModal.classList.remove('hidden'); }
  function closeAddModal(){ if(addUserModal){ addUserModal.classList.add('hidden'); addUserForm && addUserForm.reset(); } }
  addUserBtn && addUserBtn.addEventListener('click', openAddModal);
  cancelAddUser && cancelAddUser.addEventListener('click', closeAddModal);
  addUserModal && addUserModal.addEventListener('click', (e)=>{ if(e.target === addUserModal) closeAddModal(); });

  addUserForm && addUserForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = (addName?.value || '').trim();
    const email = (addEmail?.value || '').trim();
    const password = addPassword?.value || '';
    if (!name || !email || !password){
      alert('Please fill all fields');
      return;
    }
    try{
      const res = await fetch(API.users, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to create user');
      closeAddModal();
      await loadUsers();
    }catch(err){
      alert(err.message);
    }
  });

  // View/Edit/Delete
  const editUserModal = $('#editUserModal');
  const editUserForm = $('#editUserForm');
  const editUserId = $('#editUserId');
  const editName = $('#editName');
  const editEmail = $('#editEmail');
  const editPassword = $('#editPassword');
  const cancelEditUser = $('#cancelEditUser');
  const viewUserModal = $('#viewUserModal');
  const closeViewUser = $('#closeViewUser');
  const viewId = $('#viewId');
  const viewName = $('#viewName');
  const viewEmail = $('#viewEmail');

  function openEditModal(){ editUserModal && editUserModal.classList.remove('hidden'); }
  function closeEditModal(){ if(editUserModal){ editUserModal.classList.add('hidden'); editUserForm && editUserForm.reset(); } }
  function openViewModal(){ viewUserModal && viewUserModal.classList.remove('hidden'); }
  function closeViewModal(){ viewUserModal && viewUserModal.classList.add('hidden'); }

  cancelEditUser && cancelEditUser.addEventListener('click', closeEditModal);
  editUserModal && editUserModal.addEventListener('click', (e)=>{ if(e.target === editUserModal) closeEditModal(); });
  closeViewUser && closeViewUser.addEventListener('click', closeViewModal);
  viewUserModal && viewUserModal.addEventListener('click', (e)=>{ if(e.target === viewUserModal) closeViewModal(); });

  async function getUserById(id){
    const res = await fetch(`${API.users}?id=${encodeURIComponent(id)}`);
    if (!res.ok) throw new Error('Failed to fetch user');
    return res.json();
  }

  async function handleTableClick(e){
    const btn = e.target.closest('button');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    if (!id) return;
    if (btn.classList.contains('view-btn')){
      try{
        const data = await getUserById(id);
        if (!data.success) throw new Error(data.message || 'Error');
        const u = data.data.user;
        if (viewId) viewId.textContent = String(u.id);
        if (viewName) viewName.textContent = u.name || '';
        if (viewEmail) viewEmail.textContent = u.email || '';
        openViewModal();
      }catch(err){ alert(err.message); }
    } else if (btn.classList.contains('edit-btn')){
      try{
        const data = await getUserById(id);
        if (!data.success) throw new Error(data.message || 'Error');
        const u = data.data.user;
        if (editUserId) editUserId.value = String(u.id);
        if (editName) editName.value = u.name || '';
        if (editEmail) editEmail.value = u.email || '';
        if (editPassword) editPassword.value = '';
        openEditModal();
      }catch(err){ alert(err.message); }
    } else if (btn.classList.contains('delete-btn')){
      if (!confirm('Are you sure you want to delete this user?')) return;
      try{
        const res = await fetch(`${API.users}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to delete');
        await loadUsers();
      }catch(err){ alert(err.message); }
    }
  }

  document.addEventListener('click', (e) => {
    if (e.target.closest('#usersTableBody .icon-btn')) handleTableClick(e);
  });

  // Init
  updateDateTime();
  setInterval(updateDateTime, 30_000);
  fetchTotalUsers();
  loadUsers();
})();


