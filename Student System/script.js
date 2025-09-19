/* script.js: frontend logic using fetch + async/await */
const state = {
  programs: [],
  students: [],
  years: [],
  semesters: [],
  subjects: [],
  enrollments: []
};

document.addEventListener('DOMContentLoaded', init);

function $qs(sel){ return document.querySelector(sel); }
function $qa(sel){ return Array.from(document.querySelectorAll(sel)); }

function init(){
  // Tabs
  $qa('nav button').forEach(b => b.addEventListener('click', () => {
    $qa('nav button').forEach(x=>x.classList.remove('active'));
    b.classList.add('active');
    showTab(b.dataset.tab);
  }));

  // Buttons
  $qs('#add-student-btn').addEventListener('click', addStudentPrompt);
  $qs('#add-program-btn').addEventListener('click', addProgramPrompt);
  $qs('#add-year-btn').addEventListener('click', addYearPrompt);
  $qs('#add-semester-btn').addEventListener('click', addSemesterPrompt);
  $qs('#add-subject-btn').addEventListener('click', addSubjectPrompt);

  // Enroll form
  $qs('#enroll-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const student_id = +$qs('#enroll-student').value;
    const subject_id = +$qs('#enroll-subject').value;
    if(!student_id || !subject_id) return alert('Select student and subject.');
    const res = await apiPost('add.php', { entity: 'enrollments', payload: { student_id, subject_id }});
    if(res.success) { alert('Enrolled'); await loadAll(); }
    else alert(res.message || 'Failed to enroll');
  });

  // filter program
  $qs('#filter-program').addEventListener('change', renderStudentsTable);
  $qs('#filter-semester').addEventListener('change', renderSubjectsTable);

  loadAll();
}

function showTab(name){
  $qa('.tab').forEach(t => t.classList.remove('active'));
  $qs('#' + name).classList.add('active');
}

async function loadAll(){
  try {
    const [programs, students, years, semesters, subjects, enrollments] = await Promise.all([
      apiGet('view.php', { entity: 'programs' }),
      apiGet('view.php', { entity: 'students' }),
      apiGet('view.php', { entity: 'years' }),
      apiGet('view.php', { entity: 'semesters' }),
      apiGet('view.php', { entity: 'subjects' }),
      apiGet('view.php', { entity: 'enrollments' })
    ]);
    state.programs = programs.data || [];
    state.students = students.data || [];
    state.years = years.data || [];
    state.semesters = semesters.data || [];
    state.subjects = subjects.data || [];
    state.enrollments = enrollments.data || [];

    populateFilters();
    renderProgramsTable();
    renderStudentsTable();
    renderYearsTable();
    renderSubjectsTable();
    renderEnrollments();
    populateEnrollForm();
  } catch (err) {
    console.error(err); alert('Error loading data');
  }
}

/* API helper wrappers */
async function apiGet(url, params = {}){
  const qs = new URLSearchParams(params).toString();
  const res = await fetch(url + (qs ? '?' + qs : ''));
  return res.json();
}
async function apiPost(url, body){
  const res = await fetch(url, {
    method:'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(body)
  });
  return res.json();
}

/* Renderers */
function populateFilters(){
  const f = $qs('#filter-program');
  f.innerHTML = `<option value="">-- All Programs --</option>` + state.programs.map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
  const fs = $qs('#filter-semester');
  fs.innerHTML = `<option value="">-- All Semesters --</option>` + state.semesters.map(s=>{
    const y = state.years.find(yy=>yy.id===s.year_id);
    return `<option value="${s.id}">${s.name} (${y?y.name:'?'})</option>`;
  }).join('');
}

function renderStudentsTable(){
  const tbody = $qs('#students-table tbody');
  const filter = $qs('#filter-program').value;
  const rows = state.students.filter(s => !filter || s.program_id == filter);
  tbody.innerHTML = rows.map(s => {
    const prog = state.programs.find(p=>p.id===s.program_id);
    return `<tr>
      <td>${s.id}</td>
      <td>${escapeHtml(s.name)}</td>
      <td>${prog ? escapeHtml(prog.name) : '—'}</td>
      <td>${s.allowance}</td>
      <td>
        <button class="btn edit" data-id="${s.id}" onclick="editStudent(${s.id})">Edit</button>
        <button class="btn del" data-id="${s.id}" onclick="deleteEntity('students',${s.id})">Delete</button>
      </td>
    </tr>`;
  }).join('');
}

function renderProgramsTable(){
  const tbody = $qs('#programs-table tbody');
  tbody.innerHTML = state.programs.map(p=>`<tr>
    <td>${p.id}</td>
    <td>${escapeHtml(p.name)}</td>
    <td>${escapeHtml(p.institute||'')}</td>
    <td>
      <button class="btn edit" onclick="editProgram(${p.id})">Edit</button>
      <button class="btn del" onclick="deleteEntity('programs',${p.id})">Delete</button>
    </td>
  </tr>`).join('');
}

function renderYearsTable(){
  const tbody = $qs('#years-table tbody');
  tbody.innerHTML = state.years.map(y=>{
    const sems = state.semesters.filter(s=>s.year_id===y.id).map(s=>`${s.id}:${s.name}`).join(', ');
    return `<tr>
      <td>${y.id}</td>
      <td>${escapeHtml(y.name)}</td>
      <td>${escapeHtml(sems)}</td>
      <td>
        <button class="btn edit" onclick="editYear(${y.id})">Edit</button>
        <button class="btn del" onclick="deleteEntity('years',${y.id})">Delete</button>
      </td>
    </tr>`;
  }).join('');
}

function renderSubjectsTable(){
  const tbody = $qs('#subjects-table tbody');
  const filter = $qs('#filter-semester').value;
  const rows = state.subjects.filter(s => !filter || s.semester_id == filter);
  tbody.innerHTML = rows.map(s=>{
    const sem = state.semesters.find(x=>x.id===s.semester_id);
    return `<tr>
      <td>${s.id}</td>
      <td>${escapeHtml(s.name)}</td>
      <td>${sem ? escapeHtml(sem.name) : '-'}</td>
      <td>
        <button class="btn edit" onclick="editSubject(${s.id})">Edit</button>
        <button class="btn del" onclick="deleteEntity('subjects',${s.id})">Delete</button>
      </td>
    </tr>`;
  }).join('');
}

function renderEnrollments(){
  const el = $qs('#enroll-list');
  if(!state.enrollments.length){ el.innerHTML='<div class="card">No enrollments yet.</div>'; return; }
  el.innerHTML = `<table style="width:100%"><thead><tr><th>ID</th><th>Student</th><th>Subject</th><th>Actions</th></tr></thead><tbody>` +
    state.enrollments.map(en=>{
      const st = state.students.find(s=>s.id===en.student_id);
      const sub = state.subjects.find(su=>su.id===en.subject_id);
      return `<tr>
        <td>${en.id}</td>
        <td>${st ? escapeHtml(st.name) : '—'}</td>
        <td>${sub ? escapeHtml(sub.name) : '—'}</td>
        <td>
          <button class="btn edit" onclick="changeEnrollment(${en.id})">Change</button>
          <button class="btn del" onclick="deleteEntity('enrollments',${en.id})">Remove</button>
        </td>
      </tr>`;
    }).join('') + `</tbody></table>`;
}

function populateEnrollForm(){
  const sSel = $qs('#enroll-student');
  const subSel = $qs('#enroll-subject');
  sSel.innerHTML = `<option value="">-- select student --</option>` + state.students.map(s=>`<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
  subSel.innerHTML = `<option value="">-- select subject --</option>` + state.subjects.map(s=>`<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
}

/* CRUD prompts & actions (simple prompt-based modals) */
function addStudentPrompt(){
  if(!state.programs.length){ return alert('Add a program first.'); }
  const name = prompt('Student name:');
  if(!name) return;
  const program_id = +prompt('Program ID (see Programs tab):\n' + state.programs.map(p=>`${p.id}: ${p.name}`).join('\n'));
  if(!program_id) return alert('Invalid program id');
  const allowance = +prompt('Allowance (number):', '0');
  apiPost('add.php', { entity:'students', payload:{ name, program_id, allowance } }).then(async res=>{
    if(res.success){ alert('Student added'); await loadAll(); }
    else alert(res.message || 'Failed to add');
  });
}

function editStudent(id){
  fetch(`student.php?id=${id}`).then(r=>r.json()).then(async res=>{
    if(!res.success) return alert(res.message||'Not found');
    const s = res.data;
    const name = prompt('Name:', s.name);
    if(!name) return;
    const program_id = +prompt('Program ID:', s.program_id || '');
    const allowance = +prompt('Allowance:', s.allowance || 0);
    const up = await apiPost('update.php', { entity:'students', id, payload:{ name, program_id, allowance }});
    if(up.success){ alert('Updated'); loadAll(); } else alert(up.message || 'Failed to update');
  });
}

function addProgramPrompt(){
  const name = prompt('Program name:');
  if(!name) return;
  const institute = prompt('Institute / Department:','');
  apiPost('add.php',{ entity:'programs', payload:{ name, institute }}).then(async res=>{
    if(res.success){ alert('Program added'); await loadAll(); }
    else alert(res.message||'Failed');
  });
}

function editProgram(id){
  const program = state.programs.find(p=>p.id===id);
  if(!program) return alert('Program not found');
  const name = prompt('Name:', program.name);
  if(!name) return;
  const institute = prompt('Institute:', program.institute || '');
  apiPost('update.php',{ entity:'programs', id, payload:{ name, institute }}).then(async r=>{
    if(r.success){ alert('Updated'); await loadAll(); } else alert(r.message||'Failed');
  });
}

function addYearPrompt(){
  const name = prompt('School year (ex: 2024-2025):');
  if(!name) return;
  apiPost('add.php',{ entity:'years', payload:{ name }}).then(async r=>{
    if(r.success){ alert('Year added'); await loadAll(); } else alert(r.message||'Failed');
  });
}

function editYear(id){
  const y = state.years.find(x=>x.id===id);
  if(!y) return alert('Not found');
  const name = prompt('Year name:', y.name);
  if(!name) return;
  apiPost('update.php',{ entity:'years', id, payload:{ name }}).then(async r=>{
    if(r.success){ alert('Updated'); await loadAll(); } else alert(r.message||'Fail');
  });
}

function addSemesterPrompt(){
  if(!state.years.length) return alert('Add a year first.');
  const name = prompt('Semester name (e.g., First Semester):');
  if(!name) return;
  const year_id = +prompt('Year ID:\n' + state.years.map(y=>`${y.id}: ${y.name}`).join('\n'));
  if(!year_id) return alert('Invalid year id');
  apiPost('add.php',{ entity:'semesters', payload:{ name, year_id }}).then(async r=>{
    if(r.success){ alert('Semester added'); await loadAll(); } else alert(r.message||'Fail');
  });
}

function addSubjectPrompt(){
  if(!state.semesters.length) return alert('Add semester first.');
  const name = prompt('Subject name:');
  if(!name) return;
  const semester_id = +prompt('Semester ID:\n' + state.semesters.map(s=>`${s.id}: ${s.name}`).join('\n'));
  apiPost('add.php',{ entity:'subjects', payload:{ name, semester_id }}).then(async r=>{
    if(r.success){ alert('Added subject'); await loadAll(); } else alert(r.message||r.error||'Fail');
  });
}

function editSubject(id){
  const s = state.subjects.find(x=>x.id===id);
  if(!s) return alert('Not found');
  const name = prompt('Subject name:', s.name);
  const semester_id = +prompt('Semester ID:', s.semester_id || '');
  apiPost('update.php',{ entity:'subjects', id, payload:{ name, semester_id }}).then(async r=>{
    if(r.success){ alert('Updated'); await loadAll(); } else alert(r.message||'Fail');
  });
}

async function changeEnrollment(id){
  const en = state.enrollments.find(e=>e.id===id);
  if(!en) return alert('Not found');
  const newSubject = +prompt('New subject ID:\n' + state.subjects.map(s=>`${s.id}: ${s.name}`).join('\n'), en.subject_id);
  if(!newSubject) return;
  const res = await apiPost('update.php', { entity:'enrollments', id, payload:{ subject_id: newSubject }});
  if(res.success){ alert('Enrollment updated'); await loadAll(); } else alert(res.message||'Failed');
}

/* Delete generic */
async function deleteEntity(entity, id){
  if(!confirm(`Delete ${entity} id ${id}? This may fail if constraints exist.`)) return;
  const res = await apiPost('delete.php', { entity, id });
  if(res.success){ alert('Deleted'); await loadAll(); } else alert(res.message||'Failed');
}

/* helpers */
function escapeHtml(s){ if(s===null||s===undefined)return''; return s.toString().replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]); }
window.editStudent = editStudent;
window.editProgram = editProgram;
window.editYear = editYear;
window.editSubject = editSubject;
window.deleteEntity = deleteEntity;
window.changeEnrollment = changeEnrollment;
