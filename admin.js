(function(){
  const $ = (sel) => document.querySelector(sel);

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
      const res = await fetch('api/stats.php');
      if (res.ok) {
        const data = await res.json();
        if (data && data.success && data.data && typeof data.data.total_users === 'number'){
          $('#totalUsers').textContent = String(data.data.total_users);
          return;
        }
      }
      // Fallback to users endpoint pagination total
      const res2 = await fetch('api/users.php?page=1&per_page=1');
      const data2 = await res2.json();
      if(data2 && data2.success && data2.data && data2.data.pagination){
        $('#totalUsers').textContent = data2.data.pagination.total ?? '--';
      } else {
        $('#totalUsers').textContent = '--';
      }
    }catch(e){
      console.error('Failed to load total users', e);
      $('#totalUsers').textContent = '--';
    }
  }

  // Optionally: populate attendance table later when API is available.
  async function fetchAttendance(q = ''){
    const params = new URLSearchParams({ page: '1', per_page: '50' });
    if (q) params.set('q', q);
    const res = await fetch(`api/attendance.php?${params.toString()}`);
    if (!res.ok) throw new Error(`Failed to load attendance (${res.status})`);
    return res.json();
  }

  function renderAttendance(items){
    const tbody = document.querySelector('#attendanceTable tbody');
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0){
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:12px;">No records</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(r => {
      const wh = r.working_hours ?? '';
      return `
        <tr>
          <td>${r.id}</td>
          <td>${r.user_name ?? ''}</td>
          <td>${r.check_in ?? ''}</td>
          <td>${r.check_out ?? ''}</td>
          <td>${wh}</td>
        </tr>
      `;
    }).join('');
  }

  async function loadAttendance(){
    try{
      const q = $('#searchInput') ? $('#searchInput').value.trim() : '';
      const data = await fetchAttendance(q);
      if (data && data.success && data.data && Array.isArray(data.data.items)){
        renderAttendance(data.data.items);
      }
    }catch(err){
      console.error(err);
    }
  }

  // Init
  updateDateTime();
  setInterval(updateDateTime, 30_000);
  fetchTotalUsers();
  loadAttendance();

  let t;
  const si = $('#searchInput');
  if (si){
    si.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => loadAttendance(), 300);
    });
  }
})();
