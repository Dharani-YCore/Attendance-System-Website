(function(){
  const $ = (sel) => document.querySelector(sel);

  // Sidebar avatar behavior
  const nameEl = document.querySelector('.admin-name');
  const avatar = document.getElementById('sidebarAvatar');
  const btn = document.getElementById('avatarAddBtn');
  const file = document.getElementById('avatarFile');
  
  if (nameEl && avatar && !avatar.style.backgroundImage){
    const letter = (nameEl.textContent || 'A').trim().charAt(0).toUpperCase();
    avatar.textContent = letter || 'A';
  }
  
  function applyPreview(fileObj){
    const url = URL.createObjectURL(fileObj);
    avatar.style.backgroundImage = `url('${url}')`;
    avatar.style.backgroundSize = 'cover';
    avatar.style.backgroundPosition = 'center';
    avatar.textContent = '';
  }
  
  btn && btn.addEventListener('click', () => file && file.click());
  file && file.addEventListener('change', (e) => {
    const f = e.target.files && e.target.files[0];
    if (f) applyPreview(f);
  });

  const API = {
    base: 'http://localhost/Attendance-System-Website/api',
    get holidays(){ return `${this.base}/holidays.php`; }
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

  const holidaysTableBody = $('#holidaysTableBody');
  const addHolidayBtn = $('#addHolidayBtn');
  const addHolidayModal = $('#addHolidayModal');
  const addHolidayForm = $('#addHolidayForm');
  const holidayDate = $('#holidayDate');
  const holidayName = $('#holidayName');
  const holidayType = $('#holidayType');
  const cancelAddHoliday = $('#cancelAddHoliday');

  async function fetchHolidays(){
    const res = await fetch(API.holidays);
    if (!res.ok) throw new Error('Failed to load holidays');
    return res.json();
  }

  function renderHolidays(items){
    if (!holidaysTableBody) return;
    if (!Array.isArray(items) || items.length === 0){
      holidaysTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:12px;">No upcoming holidays</td></tr>';
      return;
    }
    holidaysTableBody.innerHTML = items.map(h => `
      <tr>
        <td>${h.holiday_date}</td>
        <td>${h.holiday_name}</td>
        <td>${h.holiday_type}</td>
      </tr>
    `).join('');
  }

  async function loadHolidays(){
    try{
      if (holidaysTableBody) holidaysTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:12px;">Loading...</td></tr>';
      const data = await fetchHolidays();
      if (data && data.success){
        renderHolidays(data.data?.items || []);
      }
    }catch(err){
      if (holidaysTableBody) holidaysTableBody.innerHTML = `<tr><td colspan="3" style="color:#c00; text-align:center; padding:12px;">${err.message}</td></tr>`;
    }
  }

  function openAddHoliday(){ addHolidayModal && addHolidayModal.classList.remove('hidden'); }
  function closeAddHoliday(){ if(addHolidayModal){ addHolidayModal.classList.add('hidden'); addHolidayForm && addHolidayForm.reset(); } }

  addHolidayBtn && addHolidayBtn.addEventListener('click', openAddHoliday);
  cancelAddHoliday && cancelAddHoliday.addEventListener('click', closeAddHoliday);
  addHolidayModal && addHolidayModal.addEventListener('click', (e)=>{ if(e.target === addHolidayModal) closeAddHoliday(); });

  addHolidayForm && addHolidayForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      holiday_date: holidayDate?.value || '',
      holiday_name: (holidayName?.value || '').trim(),
      holiday_type: holidayType?.value || 'National'
    };
    if (!payload.holiday_date || !payload.holiday_name){
      alert('Please provide date and name');
      return;
    }
    try{
      const res = await fetch(API.holidays, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to add holiday');
      closeAddHoliday();
      await loadHolidays();
    }catch(err){ alert(err.message); }
  });

  updateDateTime();
  setInterval(updateDateTime, 30_000);
  loadHolidays();
})();


