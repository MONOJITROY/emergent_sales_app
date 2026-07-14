// StockFlow client app
const SF = (function(){
  const $ = jQuery;
  const baseUrl = $('meta[name="base-url"]').attr('content') || '';
  const csrf    = () => $('meta[name="csrf-token"]').attr('content') || '';
  const money   = (v) => Number(v||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  const ucwords = (s) => String(s).replace(/\b\w/g, c => c.toUpperCase());

  // global ajax setup
  $.ajaxSetup({
    headers: { 'X-CSRF-Token': csrf() },
    contentType: 'application/json',
    dataType: 'json',
    error: (xhr) => {
      const msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.detail)) || xhr.statusText || 'Request failed';
      iziToast.error({title:'Error', message: String(msg), position:'bottomRight'});
      if (xhr.status === 401) setTimeout(()=>{ location.href = baseUrl + '/login'; }, 600);
    }
  });

  const api = {
    get:  (u)        => $.ajax({url: baseUrl + u, method:'GET'}),
    post: (u, body)  => $.ajax({url: baseUrl + u, method:'POST', data: JSON.stringify(body||{})}),
    put:  (u, body)  => $.ajax({url: baseUrl + u, method:'POST', headers:{'X-CSRF-Token':csrf(),'X-HTTP-Method-Override':'PUT'}, data: JSON.stringify(body||{})}),
    del:  (u)        => $.ajax({url: baseUrl + u, method:'POST', headers:{'X-CSRF-Token':csrf(),'X-HTTP-Method-Override':'DELETE'}, data: '{}'}),
  };

  const confirmAction = (message, onYes) => {
    iziToast.question({
      timeout:false, close:false, overlay:true, position:'center',
      title:'Confirm', message:String(message),
      buttons:[
        ['<button><b>YES</b></button>', (inst,toast)=>{ inst.hide({},toast,'button'); onYes(); }, true],
        ['<button>NO</button>', (inst,toast)=> inst.hide({},toast,'button')]
      ]
    });
  };

  // sidebar toggle
  $(function(){
    $('#sbToggle').on('click', ()=> $('#sidebar').toggleClass('show'));
    $('#logoutBtn').on('click', function(){
      confirmAction('Sign out?', ()=> api.post('/api/auth/logout',{}).then(()=> location.href = baseUrl + '/login'));
    });
    // login form
    $('#loginForm').on('submit', function(e){
      e.preventDefault();
      const data = { email: this.email.value.trim(), password: this.password.value };
      api.post('/api/auth/login', data).then((r)=>{
        iziToast.success({title:'Signed in', position:'bottomRight'});
        location.href = baseUrl + '/';
      });
    });
  });

  // ---- Dashboard ----
  function dashboard(){
    api.get('/api/dashboard/stats').then((s)=>{
      const kpi = (label,value,hint) => `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">${label}</div><div class="value">${value}</div>${hint?`<div class="small text-muted mt-1">${hint}</div>`:''}</div></div>`;
      $('#kpis').html(
        kpi("Today's sales", money(s.today_sales), 'INR') +
        kpi('Outstanding', money(s.outstanding), 'Across all unpaid') +
        kpi('Products', s.products_count, s.low_stock_count + ' low') +
        kpi('Pending Recon', money(s.pending_reconciliation||0), 'Awaiting settlement')
      );
      // chart
      new Chart(document.getElementById('salesChart'), {
        type:'bar',
        data:{ labels: s.chart.map(c=>c.date.slice(5)), datasets:[{ data: s.chart.map(c=>c.total), backgroundColor:'#ea580c', borderRadius:3 }]},
        options:{ plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,grid:{color:'#e2e8f0'}}, x:{grid:{display:false}} } }
      });
      $('#lowStock').html(
        (s.low_stock_items||[]).length === 0
          ? '<li class="text-muted text-center py-3"><span class="healthystock">✔</span>All stock levels healthy.</li>'
          : s.low_stock_items.map(p =>
              `<li class="d-flex justify-content-between py-2 border-bottom"><div><div class="fw-semibold">${p.name}</div><div class="text-muted text-num small">${p.sku}</div></div><span class="badge sf-badge sf-status-partial">${p.stock} ${p.unit}</span></li>`
            ).join('')
      );
      $('#recentSales').html(
        (s.recent_sales||[]).length === 0
          ? '<tr><td colspan="6" class="text-center text-muted py-3">No sales yet.</td></tr>'
          : s.recent_sales.map(r => `<tr>
              <td><a class="text-decoration-none text-orange text-num small" href="${baseUrl}/sales/${r.id}">${r.invoice_no}</a></td>
              <td>${esc(r.customer_name)}</td><td class="small text-muted">${r.sale_date}</td>
              <td class="text-end text-num">${money(r.total)}</td>
              <td class="text-end text-num">${money(r.balance)}</td>
              <td><span class="badge sf-badge sf-status-${r.status}">${r.status.toUpperCase()}</span></td>
            </tr>`).join('')
      );
    });
  }

  const esc = (s) => String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  // ---- Products ----
  let taxtypeOptions = [];
  function products(){
    const modalEl = document.getElementById('formModal');
    const modal = new bootstrap.Modal(modalEl);
    const load = () => {
      const q = $('#searchInput').val()||'';
      api.get('/api/products' + (q?`?q=${encodeURIComponent(q)}`:'')).then(rows=>{
        if (!rows.length) return $('#rows').html('<tr><td colspan="8" class="text-center text-muted py-3">No products yet.</td></tr>');
        $('#rows').html(rows.map(r => {
          const low = Number(r.reorder_level)>0 && Number(r.stock)<=Number(r.reorder_level);
          return `<tr>
            <td class="text-num small">${esc(r.hsn)}</td>
            <td class="text-num small">${esc(r.sku)}</td>
            <td class="fw-semibold">${esc(r.name)}</td>
            <td class="text-muted">${esc(r.category||'—')}</td>
            <td class="text-end text-num">${money(r.cost_price)}</td>
            <td class="text-end text-num">${money(r.sale_price)}</td>
            <td class="text-end text-num ${low?'text-orange fw-semibold':''}">${r.stock} ${esc(r.unit)}</td>
            <td class="text-end text-num text-muted">${r.reorder_level}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-link p-1 text-secondary edit" data-id="${r.id}"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-link p-1 text-danger del" data-id="${r.id}" data-name="${esc(r.name)}"><i class="bi bi-trash"></i></button>
            </td></tr>`;
        }).join(''));
        $('#rows .edit').on('click', function(){
          const r = rows.find(x=>String(x.id)===$(this).data('id').toString()); fillForm(r); modal.show();
        });
        $('#rows .del').on('click', function(){
          const id=$(this).data('id'), name=$(this).data('name');
          confirmAction(`Delete "${name}"?`, ()=> api.del('/api/products/'+id).then(()=>{ iziToast.success({title:'Deleted',position:'bottomRight'}); load(); }));
        });
      });
    };
    const fillForm = (r) => {
      const f = document.getElementById('form');
      const fields = ['id','hsn','sku','name','category','unit','cost_price','sale_price','stock','reorder_level'];
      fields.forEach(k => { if (f[k]) f[k].value = r ? (r[k]??'') : (k==='unit'?'pcs':(['cost_price','sale_price','stock','reorder_level'].includes(k)?0:'')); });
      if (f.taxtype_id) f.taxtype_id.value = r ? (r.taxtype_id||'') : '';
    };
    $('#newBtn').on('click', ()=>{ fillForm(null); modal.show(); });
    $('#form').on('submit', function(e){
      e.preventDefault();
      const f = this, data = Object.fromEntries(new FormData(f));
      ['cost_price','sale_price','stock','reorder_level'].forEach(k=> data[k] = Number(data[k]||0));
      data.taxtype_id = Number(data.taxtype_id||0) || null;
      const id = data.id; delete data.id;
      const p = id ? api.put('/api/products/'+id, data) : api.post('/api/products', data);
      p.then(()=>{ iziToast.success({title: id?'Updated':'Created', position:'bottomRight'}); modal.hide(); load(); });
    });
    $('#searchInput').on('input', debounce(load, 250));
    // load taxtypes for dropdown
    api.get('/api/taxtypes').then(rows => {
      taxtypeOptions = rows;
      const sel = document.querySelector('[name="taxtype_id"]');
      if (sel) {
        rows.forEach(t => { sel.innerHTML += `<option value="${t.id}">${esc(t.taxname)} (${t.percentage}%)</option>`; });
      }
    });
    load();
  }

  function party(endpoint, showBalance){
    const modalEl = document.getElementById('formModal');
    const modal = new bootstrap.Modal(modalEl);
    const load = () => {
      const q = $('#searchInput').val()||'';
      api.get('/api/'+endpoint + (q?`?q=${encodeURIComponent(q)}`:'')).then(rows=>{
        const cols = showBalance ? 6 : 5;
        if (!rows.length) return $('#rows').html(`<tr><td colspan="${cols}" class="text-center text-muted py-3">No records yet.</td></tr>`);
        $('#rows').html(rows.map(r => `<tr>
          <td class="fw-semibold">${esc(r.name)}</td>
          <td class="text-muted">${esc(r.email||'—')}</td>
          <td class="text-muted text-num small">${esc(r.phone||'—')}</td>
          <td class="text-muted small text-truncate" style="max-width:280px">${esc(r.address||'—')}</td>
          ${showBalance?`<td class="text-end text-num ${Number(r.balance)>0?'text-danger':''}">${money(r.balance||0)}</td>`:''}
          <td class="text-end">
            <button class="btn btn-sm btn-link p-1 text-secondary edit" data-id="${r.id}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-link p-1 text-danger del" data-id="${r.id}" data-name="${esc(r.name)}"><i class="bi bi-trash"></i></button>
          </td></tr>`).join(''));
        $('#rows .edit').on('click', function(){
          const r = rows.find(x=>String(x.id)===$(this).data('id').toString());
          const f = document.getElementById('form');
          ['id','name','email','phone','address'].forEach(k => { if(f[k]) f[k].value = r[k] ?? ''; });
          modal.show();
        });
        $('#rows .del').on('click', function(){
          const id=$(this).data('id'), name=$(this).data('name');
          confirmAction(`Delete "${name}"?`, ()=> api.del('/api/'+endpoint+'/'+id).then(()=>{ iziToast.success({title:'Deleted',position:'bottomRight'}); load(); }));
        });
      });
    };
    $('#newBtn').on('click', ()=>{ const f=document.getElementById('form'); ['id','name','email','phone','address'].forEach(k=>{ if(f[k]) f[k].value=''; }); modal.show(); });
    $('#form').on('submit', function(e){
      e.preventDefault();
      const data = Object.fromEntries(new FormData(this));
      const id = data.id; delete data.id;
      const p = id ? api.put('/api/'+endpoint+'/'+id, data) : api.post('/api/'+endpoint, data);
      p.then(()=>{ iziToast.success({title:id?'Updated':'Created', position:'bottomRight'}); modal.hide(); load(); });
    });
    $('#searchInput').on('input', debounce(load, 250));
    load();
  }

  function sales(){
    const load = () => {
      const q = $('#searchInput').val()||'';
      api.get('/api/sales' + (q?`?q=${encodeURIComponent(q)}`:'')).then(rows=>{
        if (!rows.length) return $('#rows').html('<tr><td colspan="8" class="text-center text-muted py-3">No invoices yet.</td></tr>');
        $('#rows').html(rows.map(r => `<tr>
          <td><a class="text-decoration-none text-orange text-num small" href="${baseUrl}/sales/${r.id}">${r.invoice_no}</a></td>
          <td>${esc(r.customer_name)}</td><td class="small text-muted">${r.sale_date}</td>
          <td class="text-muted small">${r.item_count||0}</td>
          <td class="text-end text-num">${money(r.total)}</td>
          <td class="text-end text-num">${money(r.paid)}</td>
          <td class="text-end text-num">${money(r.balance)}</td>
          <td><span class="badge sf-badge sf-status-${r.status}">${r.status.toUpperCase()}</span></td>
        </tr>`).join(''));
      });
    };
    $('#searchInput').on('input', debounce(load, 250));
    load();
  }

  function saleNew(){
    let products = [], customers = [], taxtypes = [], lines = [];
    Promise.all([api.get('/api/products'), api.get('/api/customers'), api.get('/api/taxtypes')]).then(([p,c,t])=>{
      products = p; customers = c; taxtypes = t;
      $('#customerSelect').append(customers.map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''));
    });
    $('#customerSelect').on('change', function(){
      const c = customers.find(x=>String(x.id)===this.value);
      $('#customerName').val(c?c.name:'Walk-in customer');
    });
    $('#addLine').on('click', ()=>{ lines.push({product_id:'',sku:'',name:'',qty:1,price:0,total:0,tax_pct:0}); render(); });

    function render(){
      $('#lines').html(lines.length===0
        ? '<tr><td colspan="6" class="text-center text-muted py-3 small">No items yet.</td></tr>'
        : lines.map((it,i)=>`<tr>
            <td><select class="form-select form-select-sm ln-prod" data-i="${i}"><option value="">— select product —</option>${products.map(p=>`<option value="${p.id}" ${String(p.id)===String(it.product_id)?'selected':''}>${esc(p.sku)} — ${esc(p.name)} (stock ${p.stock})</option>`).join('')}</select></td>
            <td class="text-end"><input class="form-control form-control-sm text-end numberinput ln-qty" data-i="${i}" type="text" step="any" style="width:80px" value="${it.qty}"></td>
            <td class="text-end"><input class="form-control form-control-sm text-end numberinput ln-price" data-i="${i}" type="text" step="any" style="width:100px" value="${it.price}"></td>
            <td class="text-end text-num small text-muted">${it.tax_pct ? it.tax_pct + '%' : '—'}</td>
            <td class="text-end text-num">${money(it.total)}</td>
            <td class="text-end"><button class="btn btn-sm btn-link text-danger p-0 ln-del" data-i="${i}"><i class="bi bi-trash"></i></button></td>
          </tr>`).join(''));
      $('.ln-prod').on('change', function(){
        const i=+$(this).data('i'),pid=this.value;
        const p=products.find(x=>String(x.id)===pid);
        if(p){
          lines[i].product_id=p.id; lines[i].sku=p.sku; lines[i].name=p.name; lines[i].price=Number(p.sale_price);
          const tt=taxtypes.find(x=>String(x.id)===String(p.taxtype_id));
          lines[i].tax_pct=tt?Number(tt.percentage):0;
        } else {
          lines[i].tax_pct=0;
        }
        lines[i].total=Number(lines[i].qty)*Number(lines[i].price);
        render();
      });
      $('.ln-qty').on('input', function(){ const i=+$(this).data('i'); lines[i].qty=Number(this.value||0); lines[i].total=lines[i].qty*Number(lines[i].price||0); render(); });
      $('.ln-price').on('input', function(){ const i=+$(this).data('i'); lines[i].price=Number(this.value||0); lines[i].total=lines[i].qty*lines[i].price; render(); });
      $('.ln-del').on('click', function(){ lines.splice(+$(this).data('i'),1); render(); });
      totals();
    }
    function totals(){
      const sub = lines.reduce((s,it)=>s+Number(it.total||0),0);
      const taxAmt = lines.reduce((s,it)=>s+(Number(it.total||0)*Number(it.tax_pct||0)/100),0);
      const disc = Number($('#discount').val()||0);
      const beforeRound = sub - disc + taxAmt;
      const rounded = Math.round(beforeRound);
      const roff = +(rounded - beforeRound).toFixed(2);
      $('#subtotal').text(money(sub));
      $('#tax').val(taxAmt.toFixed(2));
      $('#roundoff').text(roff.toFixed(2));
      $('#total').text(money(rounded));
      $('#balance').text(money(Math.max(0, rounded - Number($('#paid').val()||0))));
    }
    $('#discount,#paid').on('input', totals);

    $('#saveSale').on('click', ()=>{
      if(lines.length===0) return iziToast.error({title:'Error',message:'Add at least one line item',position:'bottomRight'});
      if(lines.some(it=>!it.product_id||Number(it.qty)<=0)) return iziToast.error({title:'Error',message:'Each line needs a product and qty>0',position:'bottomRight'});
      const taxAmt = lines.reduce((s,it)=>s+(Number(it.total||0)*Number(it.tax_pct||0)/100),0);
      api.post('/api/sales', {
        customer_id: $('#customerSelect').val() || null,
        customer_name: $('#customerName').val(),
        items: lines, discount: Number($('#discount').val()||0), tax: taxAmt, roundoff: Number($('#roundoff').text()||0),
        paid: Number($('#paid').val()||0), notes: $('#notes').val(),
      }).then((s)=>{
        iziToast.success({title:`Invoice ${s.invoice_no} created`,position:'bottomRight'});
        setTimeout(()=> location.href = baseUrl + '/sales/' + s.id, 400);
      });
    });
  }

  function saleView(id){
    $('#deleteSale').on('click', function(){
      confirmAction('Delete this invoice?', ()=> api.del('/api/sales/'+id).then(()=>{ iziToast.success({title:'Deleted',position:'bottomRight'}); setTimeout(()=>location.href=baseUrl+'/sales',300); }));
    });
    $('#emailInvoice').on('click', function(){
      iziToast.question({
        timeout:false, close:false, overlay:true, position:'center',
        title:'Email invoice', message:'Recipient email:', inputs:[['<input type="email" placeholder="customer@example.com">','keyup',function(){}, true]],
        buttons:[
          ['<button><b>SEND</b></button>', (inst,toast,btn,e,inputs)=>{
            const email = inputs[0].value.trim();
            inst.hide({},toast,'button');
            api.post('/api/sales/'+id+'/email', {email: email}).then((r)=> iziToast.success({title:'Sent to '+r.sent_to,position:'bottomRight'}));
          }, true],
          ['<button>CANCEL</button>', (inst,toast)=> inst.hide({},toast,'button')]
        ]
      });
    });
  }

  function saleEdit(sale){
    let products=[], customers=[], taxtypes=[], lines = (sale.items||[]).map(it => ({product_id:+it.product_id, sku:it.sku, name:it.name, qty:Number(it.qty), price:Number(it.price), total:Number(it.total), tax_pct:0}));
    Promise.all([api.get('/api/products'), api.get('/api/customers'), api.get('/api/taxtypes')]).then(([p,c,t])=>{
      products = p; customers = c; taxtypes = t;
      // look up tax_pct for existing lines
      lines.forEach(it => {
        const prod = products.find(x=>String(x.id)===String(it.product_id));
        if (prod) {
          const tt = taxtypes.find(x=>String(x.id)===String(prod.taxtype_id));
          it.tax_pct = tt ? Number(tt.percentage) : 0;
        }
      });
      $('#customerSelect').append(customers.map(x=>`<option value="${x.id}" ${String(x.id)===String(sale.customer_id||'')?'selected':''}>${esc(x.name)}</option>`).join(''));
      render();
    });
    $('#customerSelect').on('change', function(){ const c=customers.find(x=>String(x.id)===this.value); $('#customerName').val(c?c.name:'Walk-in customer'); });
    $('#addLine').on('click', ()=>{ lines.push({product_id:'',sku:'',name:'',qty:1,price:0,total:0,tax_pct:0}); render(); });
    function render(){
      $('#lines').html(lines.length===0
        ? '<tr><td colspan="6" class="text-center text-muted py-3 small">No items.</td></tr>'
        : lines.map((it,i)=>`<tr>
            <td><select class="form-select form-select-sm ln-prod" data-i="${i}"><option value="">— pick product —</option>${products.map(p=>`<option value="${p.id}" ${String(p.id)===String(it.product_id)?'selected':''}>${esc(p.sku)} — ${esc(p.name)} (stock ${p.stock})</option>`).join('')}</select></td>
            <td class="text-end"><input class="form-control form-control-sm text-end ln-qty" data-i="${i}" type="number" step="any" style="width:80px" value="${it.qty}"></td>
            <td class="text-end"><input class="form-control form-control-sm text-end ln-price" data-i="${i}" type="number" step="any" style="width:100px" value="${it.price}"></td>
            <td class="text-end text-num small text-muted">${it.tax_pct ? it.tax_pct + '%' : '—'}</td>
            <td class="text-end text-num">${money(it.total)}</td>
            <td class="text-end"><button class="btn btn-sm btn-link text-danger p-0 ln-del" data-i="${i}"><i class="bi bi-trash"></i></button></td>
          </tr>`).join(''));
      $('.ln-prod').on('change', function(){
        const i=+$(this).data('i'),pid=this.value;
        const p=products.find(x=>String(x.id)===pid);
        if(p){
          lines[i].product_id=p.id; lines[i].sku=p.sku; lines[i].name=p.name; lines[i].price=Number(p.sale_price);
          const tt=taxtypes.find(x=>String(x.id)===String(p.taxtype_id));
          lines[i].tax_pct=tt?Number(tt.percentage):0;
        } else {
          lines[i].tax_pct=0;
        }
        lines[i].total=Number(lines[i].qty)*Number(lines[i].price);
        render();
      });
      $('.ln-qty').on('input', function(){ const i=+$(this).data('i'); lines[i].qty=Number(this.value||0); lines[i].total=lines[i].qty*Number(lines[i].price||0); render(); });
      $('.ln-price').on('input', function(){ const i=+$(this).data('i'); lines[i].price=Number(this.value||0); lines[i].total=lines[i].qty*lines[i].price; render(); });
      $('.ln-del').on('click', function(){ lines.splice(+$(this).data('i'),1); render(); });
      totals();
    }
    function totals(){
      const sub = lines.reduce((s,it)=>s+Number(it.total||0),0);
      const taxAmt = lines.reduce((s,it)=>s+(Number(it.total||0)*Number(it.tax_pct||0)/100),0);
      const disc = Number($('#discount').val()||0);
      const beforeRound = sub - disc + taxAmt;
      const rounded = Math.round(beforeRound);
      const roff = +(rounded - beforeRound).toFixed(2);
      $('#subtotal').text(money(sub));
      $('#tax').val(taxAmt.toFixed(2));
      $('#roundoff').text(roff.toFixed(2));
      $('#total').text(money(rounded));
      $('#balance').text(money(Math.max(0, rounded - Number($('#paid').val()||0))));
    }
    $('#discount,#paid').on('input', totals);
    $('#updateSale').on('click', function(){
      const id = $(this).data('id');
      if(lines.length===0) return iziToast.error({title:'Error',message:'Add at least one line item',position:'bottomRight'});
      if(lines.some(it=>!it.product_id||Number(it.qty)<=0)) return iziToast.error({title:'Error',message:'Each line needs a product and qty>0',position:'bottomRight'});
      const taxAmt = lines.reduce((s,it)=>s+(Number(it.total||0)*Number(it.tax_pct||0)/100),0);
      api.put('/api/sales/'+id, {
        customer_id: $('#customerSelect').val() || null,
        customer_name: $('#customerName').val(),
        items: lines, discount: Number($('#discount').val()||0), tax: taxAmt, roundoff: Number($('#roundoff').text()||0),
        paid: Number($('#paid').val()||0), notes: $('#notes').val(),
      }).then(()=>{ iziToast.success({title:'Invoice updated',position:'bottomRight'}); setTimeout(()=>location.href=baseUrl+'/sales/'+id,400); });
    });
  }

  function purchases(){
    let products=[], suppliers=[], lines=[];
    const modalEl = document.getElementById('purchaseModal');
    const modal = new bootstrap.Modal(modalEl);

    const load = () => {
      const q = $('#searchInput').val()||'';
      api.get('/api/purchases' + (q?`?q=${encodeURIComponent(q)}`:'')).then(rows=>{
        if (!rows.length) return $('#rows').html('<tr><td colspan="9" class="text-center text-muted py-3">No purchases yet.</td></tr>');
        $('#rows').html(rows.map(r=>`<tr>
          <td class="text-num small">${esc(r.ref_no)}</td><td>${esc(r.supplier_name)}</td>
          <td class="small text-muted">${r.purchase_date}</td><td class="text-muted small">${r.item_count||0}</td>
          <td class="text-end text-num fw-semibold">${money(r.total)}</td>
          <td class="text-end text-num">${money(r.paid||0)}</td>
          <td class="text-end text-num ${Number(r.balance)>0?'text-danger':''}">${money(r.balance||0)}</td>
          <td><span class="badge sf-badge sf-status-${r.status||'unpaid'}">${(r.status||'unpaid').toUpperCase()}</span></td>
          <td class="text-end"><button class="btn btn-sm btn-link p-1 text-danger del" data-id="${r.id}" data-ref="${esc(r.ref_no)}"><i class="bi bi-trash"></i></button></td>
        </tr>`).join(''));
        $('#rows .del').on('click', function(){
          const id=$(this).data('id'), ref=$(this).data('ref');
          confirmAction(`Delete purchase ${ref}?`, ()=> api.del('/api/purchases/'+id).then(()=>{ iziToast.success({title:'Deleted',position:'bottomRight'}); load(); }));
        });
      });
    };

    const todayStr = () => new Date().toISOString().slice(0,10);
    $('#newBtn').on('click', ()=>{
      lines=[]; $('#notes').val(''); $('#pTax').val(0); $('#supplierName').val(''); $('#supplierSel').val('');
      $('#purchasedate').val(todayStr()); $('#supplierinvno').val(''); $('#supplierinvdate').val(todayStr());
      $('#supplierinvamt').val(0); $('#supplierinvtaxamt').val(0); $('#supplierinvtotamt').val(0);
      Promise.all([api.get('/api/products'), api.get('/api/suppliers')]).then(([p,s])=>{
        products=p; suppliers=s;
        $('#supplierSel').html('<option value="">— pick a supplier —</option>'+suppliers.map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''));
        renderLines(); modal.show();
      });
    });
    $('#supplierSel').on('change', function(){ const s=suppliers.find(x=>String(x.id)===this.value); $('#supplierName').val(s?s.name:''); });
    $('#addLine').on('click', ()=>{ lines.push({product_id:'',sku:'',name:'',qty:1,price:0,total:0}); renderLines(); });
    $('#supplierinvamt, #supplierinvtaxamt').on('input', function(){
      const amt = Number($('#supplierinvamt').val()||0);
      const tax = Number($('#supplierinvtaxamt').val()||0);
      $('#supplierinvtotamt').val((amt + tax).toFixed(2));
    });

    function renderLines(){
      $('#lines').html(lines.length===0
        ? '<tr><td colspan="5" class="text-center text-muted small py-2">No items.</td></tr>'
        : lines.map((it,i)=>`<tr>
            <td><select class="form-select form-select-sm pl-prod" data-i="${i}"><option value="">— pick product —</option>${products.map(p=>`<option value="${p.id}" ${String(p.id)===String(it.product_id)?'selected':''}>${esc(p.sku)} — ${esc(p.name)}</option>`).join('')}</select></td>
            <td class="text-ends"><input class="form-control form-control-sm text-ends pl-qty" data-i="${i}" type="text" step="any" style="width:80px" value="${it.qty}"></td>
            <td class="text-ends"><input class="form-control form-control-sm text-ends pl-price" data-i="${i}" type="text" step="any" style="width:100px" value="${it.price}"></td>
            <td class="text-ends text-num">${money(it.total)}</td>
            <td class="text-end"><button class="btn btn-sm btn-link text-danger p-0 pl-del" data-i="${i}"><i class="bi bi-trash"></i></button></td>
          </tr>`).join(''));
      $('.pl-prod').on('change',function(){ const i=+$(this).data('i'),pid=this.value; const p=products.find(x=>String(x.id)===pid); if(p){lines[i].product_id=p.id;lines[i].sku=p.sku;lines[i].name=p.name;lines[i].price=Number(p.cost_price);} lines[i].total=Number(lines[i].qty)*Number(lines[i].price); renderLines(); });
      $('.pl-qty').on('input',function(){ const i=+$(this).data('i'); lines[i].qty=Number(this.value||0); lines[i].total=lines[i].qty*Number(lines[i].price||0); renderLines(); });
      $('.pl-price').on('input',function(){ const i=+$(this).data('i'); lines[i].price=Number(this.value||0); lines[i].total=lines[i].qty*lines[i].price; renderLines(); });
      $('.pl-del').on('click',function(){ lines.splice(+$(this).data('i'),1); renderLines(); });
      const sub = lines.reduce((s,it)=>s+Number(it.total||0),0);
      $('#pSub').text(money(sub)); $('#pTotal').text(money(sub + Number($('#pTax').val()||0)));
    }
    $('#pTax').on('input', ()=>{ const sub=lines.reduce((s,it)=>s+Number(it.total||0),0); $('#pTotal').text(money(sub + Number($('#pTax').val()||0))); });
    $('#savePurchase').on('click', ()=>{
      if(!$('#supplierName').val().trim()) return iziToast.error({title:'Error',message:'Supplier name required',position:'bottomRight'});
      if(!$('#supplierinvno').val().trim()) return iziToast.error({title:'Error',message:'Supplier invoice number is required',position:'bottomRight'});
      if(lines.length===0) return iziToast.error({title:'Error',message:'Add at least one item',position:'bottomRight'});
      const calcSub = lines.reduce((s,it)=>s+Number(it.total||0),0);
      const calcTax = Number($('#pTax').val()||0);
      const calcTot = calcSub + calcTax;
      const invAmt = Number($('#supplierinvamt').val()||0);
      const invTax = Number($('#supplierinvtaxamt').val()||0);
      const invTot = Number($('#supplierinvtotamt').val()||0);
      if (Math.abs(calcSub - invAmt) > 0.009) return iziToast.error({title:'Mismatch',message:`Inv Amt (${invAmt.toFixed(2)}) doesn't match subtotal (${calcSub.toFixed(2)})`,position:'bottomRight'});
      if (Math.abs(calcTax - invTax) > 0.009) return iziToast.error({title:'Mismatch',message:`Tax Amt (${invTax.toFixed(2)}) doesn't match calculated tax (${calcTax.toFixed(2)})`,position:'bottomRight'});
      if (Math.abs(calcTot - invTot) > 0.009) return iziToast.error({title:'Mismatch',message:`Total Inv Amt (${invTot.toFixed(2)}) doesn't match calculated total (${calcTot.toFixed(2)})`,position:'bottomRight'});
      api.post('/api/purchases', {
        supplier_id: $('#supplierSel').val() || null,
        supplier_name: $('#supplierName').val(),
        purchase_date: $('#purchasedate').val(),
        supplier_inv_no: $('#supplierinvno').val().trim(),
        supplier_inv_date: $('#supplierinvdate').val(),
        items: lines, tax: Number($('#pTax').val()||0), notes: $('#notes').val(),
      }).then((r)=>{ iziToast.success({title:`Purchase ${r.ref_no} recorded`,position:'bottomRight'}); modal.hide(); load(); });
    });
    $('#searchInput').on('input', debounce(load,250));
    load();
  }

  function reports(){
    api.get('/api/reports/sales-by-customer').then(rows=>{
      $('#rByCust').html(rows.length===0?'<tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>'
        : rows.map(r=>`<tr><td class="fw-semibold">${esc(r.customer)}</td><td class="text-end text-num">${r.invoices}</td><td class="text-end text-num">${money(r.total)}</td><td class="text-end text-num ${Number(r.balance)>0?'text-danger':''}">${money(r.balance)}</td></tr>`).join(''));
    });
    api.get('/api/reports/sales-by-product').then(rows=>{
      $('#rByProd').html(rows.length===0?'<tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>'
        : rows.map(r=>`<tr><td class="text-num small">${esc(r.sku)}</td><td class="fw-semibold">${esc(r.product)}</td><td class="text-end text-num">${r.qty}</td><td class="text-end text-num">${money(r.total)}</td></tr>`).join(''));
    });
    api.get('/api/reports/invoice-aging').then(d=>{
      const buckets = ['0-30','31-60','61-90','90+'];
      $('#agingBuckets').html(buckets.map(k=>`<div class="col-6 col-lg-3"><div class="kpi"><div class="label">${k} days</div><div class="value">${money(d.buckets[k]||0)}</div></div></div>`).join(''));
      $('#rAging').html(d.rows.length===0?'<tr><td colspan="7" class="text-center text-muted py-3">No outstanding invoices.</td></tr>'
        : d.rows.map(r=>`<tr><td class="text-num small">${esc(r.invoice_no)}</td><td>${esc(r.customer)}</td><td class="small text-muted">${r.date}</td><td class="text-end text-num">${r.days_overdue}</td><td><span class="badge sf-badge bg-light text-secondary border">${r.bucket}</span></td><td class="text-end text-num">${money(r.total)}</td><td class="text-end text-num text-danger">${money(r.balance)}</td></tr>`).join(''));
    });
  }

  function partyOutstanding(){
    const load = () => {
      const type = $('#poType').val();
      api.get('/api/reports/party-outstanding?type='+encodeURIComponent(type)).then(rows=>{
        let totalOut = 0, totalInvoices = 0, custCount = 0, supCount = 0;
        rows.forEach(r => { totalOut += Number(r.outstanding); totalInvoices += Number(r.unpaid_invoices); if(r.party_type==='Customer') custCount++; else supCount++; });
        $('#poSummary').html(
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Parties with Dues</div><div class="value">${rows.length}</div></div></div>` +
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Customers</div><div class="value">${custCount}</div></div></div>` +
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Suppliers</div><div class="value">${supCount}</div></div></div>` +
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Outstanding</div><div class="value text-danger">${money(totalOut)}</div></div></div>`
        );
        $('#poRows').html(rows.length===0?'<tr><td colspan="5" class="text-center text-muted py-3">No outstanding balances.</td></tr>'
          : rows.map(r=>`<tr>
              <td class="fw-semibold">${esc(r.party_name)}</td>
              <td><span class="badge sf-badge ${r.party_type==='Customer'?'sf-status-partial':'bg-light text-secondary border'}">${r.party_type}</span></td>
              <td class="small text-muted">${esc(r.phone||'—')}</td>
              <td class="text-end text-num">${r.unpaid_invoices}</td>
              <td class="text-end text-num text-danger fw-semibold">${money(r.outstanding)}</td>
            </tr>`).join(''));
        $('#poFoot').html(rows.length>0?`<tr class="table-light fw-bold"><td colspan="3">Total</td><td class="text-end text-num">${totalInvoices}</td><td class="text-end text-num text-danger">${money(totalOut)}</td></tr>`:'');
      });
    };
    $('#poType').on('change', load);
    load();
  }

  function partyLedger(){
    const loadParties = () => {
      const type = $('#plPartyType').val();
      const endpoint = type === 'customer' ? '/api/customers' : '/api/suppliers';
      api.get(endpoint).then(rows=>{
        $('#plPartyId').html('<option value="">Select party…</option>'+rows.map(r=>`<option value="${r.id}">${esc(r.name)}</option>`).join(''));
      });
    };
    const loadLedger = () => {
      const partyType = $('#plPartyType').val();
      const partyId = $('#plPartyId').val();
      if (!partyId) { $('#plRows').html('<tr><td colspan="6" class="text-center text-muted py-3">Select a party to view ledger.</td></tr>'); $('#plFoot').html(''); $('#plInfo').html(''); return; }
      let url = `/api/reports/party-ledger?party_type=${encodeURIComponent(partyType)}&party_id=${partyId}`;
      const from = $('#plFrom').val(), to = $('#plTo').val();
      if (from) url += '&from=' + from;
      if (to) url += '&to=' + to;
      api.get(url).then(d=>{
        const entries = d.entries || [];
        const opening = d.opening || 0;
        const lastBal = entries.length>0 ? entries[entries.length-1].balance : opening;
        const totalDr = entries.reduce((s,r)=>s+Number(r.debit),0);
        const totalCr = entries.reduce((s,r)=>s+Number(r.credit),0);
        $('#plInfo').html(`<div class="row g-3">
          <div class="col-md-3"><div class="kpi"><div class="label">Opening Balance</div><div class="value">${money(opening)}</div></div></div>
          <div class="col-md-3"><div class="kpi"><div class="label">Total Debit</div><div class="value">${money(totalDr)}</div></div></div>
          <div class="col-md-3"><div class="kpi"><div class="label">Total Credit</div><div class="value">${money(totalCr)}</div></div></div>
          <div class="col-md-3"><div class="kpi"><div class="label">Closing Balance</div><div class="value ${Number(lastBal)>0?'text-danger':''}">${money(lastBal)}</div></div></div>
        </div>`);
        $('#plRows').html(entries.length===0?'<tr><td colspan="6" class="text-center text-muted py-3">No transactions found.</td></tr>'
          : entries.map(r=>`<tr>
              <td class="small">${r.date}</td>
              <td><span class="badge sf-badge ${r.type==='Invoice'||r.type==='Purchase'?'sf-status-partial':'bg-light text-secondary border'}">${esc(r.type)}</span></td>
              <td class="text-num small">${esc(r.ref_no)}</td>
              <td class="text-end text-num">${Number(r.debit)>0?money(r.debit):''}</td>
              <td class="text-end text-num">${Number(r.credit)>0?money(r.credit):''}</td>
              <td class="text-end text-num fw-semibold ${Number(r.balance)>0?'text-danger':''}">${money(r.balance)}</td>
            </tr>`).join(''));
        if(entries.length>0){
          $('#plFoot').html(`<tr class="table-light fw-bold"><td colspan="3">Closing Balance</td><td class="text-end text-num">${money(totalDr)}</td><td class="text-end text-num">${money(totalCr)}</td><td class="text-end text-num ${Number(lastBal)>0?'text-danger':''}">${money(lastBal)}</td></tr>`);
        } else { $('#plFoot').html(''); }
      });
    };
    $('#plPartyType').on('change', ()=>{ loadParties(); $('#plRows').html('<tr><td colspan="6" class="text-center text-muted py-3">Select a party to view ledger.</td></tr>'); $('#plFoot').html(''); $('#plInfo').html(''); });
    $('#plLoad').on('click', loadLedger);
    loadParties();
  }

  function saleReport(){
    const loadParties = () => api.get('/api/customers').then(rows=>{
      $('#srCustomer').html('<option value="">All Customers</option>'+rows.map(r=>`<option value="${r.id}">${esc(r.name)}</option>`).join(''));
    });
    const load = () => {
      let url = '/api/reports/sale-report?';
      const from=$('#srFrom').val(), to=$('#srTo').val(), cust=$('#srCustomer').val(), status=$('#srStatus').val();
      if (from) url += 'from='+from+'&';
      if (to) url += 'to='+to+'&';
      if (cust) url += 'customer='+cust+'&';
      if (status && status!=='all') url += 'status='+status+'&';
      api.get(url).then(d=>{
        const s = d.summary||{};
        $('#srSummary').html(
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Invoices</div><div class="value">${s.cnt||0}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Sales</div><div class="value">${money(s.total||0)}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Paid</div><div class="value">${money(s.paid||0)}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Outstanding</div><div class="value text-danger">${money(s.balance||0)}</div></div></div>`
        );
        const rows = d.rows||[];
        const stBadge = v => v==='paid'?'sf-status-partial':v==='partial'?'text-bg-warning':'bg-light text-secondary border';
        $('#srRows').html(rows.length===0?'<tr><td colspan="10" class="text-center text-muted py-3">No records found.</td></tr>'
          : rows.map(r=>`<tr>
              <td class="text-num small">${esc(r.invoice_no)}</td><td class="small">${r.date}</td><td>${esc(r.customer)}</td>
              <td class="text-end text-num">${money(r.subtotal)}</td><td class="text-end text-num">${money(r.discount)}</td><td class="text-end text-num">${money(r.tax)}</td>
              <td class="text-end text-num fw-semibold">${money(r.total)}</td><td class="text-end text-num">${money(r.paid)}</td>
              <td class="text-end text-num ${Number(r.balance)>0?'text-danger':''}">${money(r.balance)}</td>
              <td><span class="badge sf-badge ${stBadge(r.status)}">${r.status.toUpperCase()}</span></td>
            </tr>`).join(''));
        if(rows.length>0){
          $('#srFoot').html(`<tr class="table-light fw-bold"><td colspan="3">Total</td><td class="text-end text-num">${money(s.total||0)}</td><td colspan="3"></td><td class="text-end text-num">${money(s.paid||0)}</td><td class="text-end text-num text-danger">${money(s.balance||0)}</td><td></td></tr>`);
        } else { $('#srFoot').html(''); }
      });
    };
    $('#srLoad').on('click', load);
    loadParties();
  }

  function productReport(){
    const load = () => api.get('/api/reports/product-report').then(rows=>{
      let totSold=0, totRevenue=0, totPurchased=0, totCost=0;
      rows.forEach(r=>{ totSold+=Number(r.qty_sold); totRevenue+=Number(r.sales_total); totPurchased+=Number(r.qty_purchased); totCost+=Number(r.purchase_total); });
      $('#prSummary').html(
        `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Products</div><div class="value">${rows.length}</div></div></div>`+
        `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Qty Sold</div><div class="value">${totSold}</div></div></div>`+
        `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Sales Revenue</div><div class="value">${money(totRevenue)}</div></div></div>`+
        `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Purchase Cost</div><div class="value">${money(totCost)}</div></div></div>`
      );
      $('#prRows').html(rows.length===0?'<tr><td colspan="12" class="text-center text-muted py-3">No products.</td></tr>'
        : rows.map(r=>`<tr>
            <td class="text-num small">${esc(r.sku)}</td><td class="fw-semibold">${esc(r.product)}</td>
            <td class="small">${esc(r.category||'—')}</td><td class="small">${esc(r.unit)}</td>
            <td class="text-end text-num">${money(r.cost_price)}</td><td class="text-end text-num">${money(r.sale_price)}</td>
            <td class="text-end text-num ${Number(r.stock)<=Number(r.reorder_level)&&Number(r.reorder_level)>0?'text-danger fw-bold':''}">${r.stock}</td>
            <td class="text-end text-num">${r.reorder_level}</td>
            <td class="text-end text-num">${r.qty_sold}</td><td class="text-end text-num">${money(r.sales_total)}</td>
            <td class="text-end text-num">${r.qty_purchased}</td><td class="text-end text-num">${money(r.purchase_total)}</td>
          </tr>`).join(''));
      if(rows.length>0){
        $('#prFoot').html(`<tr class="table-light fw-bold"><td colspan="8">Total</td><td class="text-end text-num">${totSold}</td><td class="text-end text-num">${money(totRevenue)}</td><td class="text-end text-num">${totPurchased}</td><td class="text-end text-num">${money(totCost)}</td></tr>`);
      } else { $('#prFoot').html(''); }
    });
    load();
  }

  function purchaseReport(){
    const loadParties = () => api.get('/api/suppliers').then(rows=>{
      $('#purSupplier').html('<option value="">All Suppliers</option>'+rows.map(r=>`<option value="${r.id}">${esc(r.name)}</option>`).join(''));
    });
    const load = () => {
      let url = '/api/reports/purchase-report?';
      const from=$('#purFrom').val(), to=$('#purTo').val(), sup=$('#purSupplier').val(), status=$('#purStatus').val();
      if (from) url += 'from='+from+'&';
      if (to) url += 'to='+to+'&';
      if (sup) url += 'supplier='+sup+'&';
      if (status && status!=='all') url += 'status='+status+'&';
      api.get(url).then(d=>{
        const s = d.summary||{};
        $('#purSummary').html(
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Invoices</div><div class="value">${s.cnt||0}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Purchases</div><div class="value">${money(s.total||0)}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Paid</div><div class="value">${money(s.paid||0)}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Outstanding</div><div class="value text-danger">${money(s.balance||0)}</div></div></div>`
        );
        const rows = d.rows||[];
        const stBadge = v => v==='paid'?'sf-status-partial':v==='partial'?'text-bg-warning':'bg-light text-secondary border';
        $('#purRows').html(rows.length===0?'<tr><td colspan="9" class="text-center text-muted py-3">No records found.</td></tr>'
          : rows.map(r=>`<tr>
              <td class="text-num small">${esc(r.ref_no)}</td><td class="small">${r.date}</td><td>${esc(r.supplier)}</td>
              <td class="text-end text-num">${money(r.subtotal)}</td><td class="text-end text-num">${money(r.tax)}</td>
              <td class="text-end text-num fw-semibold">${money(r.total)}</td><td class="text-end text-num">${money(r.paid)}</td>
              <td class="text-end text-num ${Number(r.balance)>0?'text-danger':''}">${money(r.balance)}</td>
              <td><span class="badge sf-badge ${stBadge(r.status)}">${r.status.toUpperCase()}</span></td>
            </tr>`).join(''));
        if(rows.length>0){
          $('#purFoot').html(`<tr class="table-light fw-bold"><td colspan="3">Total</td><td class="text-end text-num">${money(s.total||0)}</td><td colspan="2"></td><td class="text-end text-num">${money(s.paid||0)}</td><td class="text-end text-num text-danger">${money(s.balance||0)}</td><td></td></tr>`);
        } else { $('#purFoot').html(''); }
      });
    };
    $('#purLoad').on('click', load);
    loadParties();
  }

  function daybook(){
    const load = () => {
      const date = $('#dbDate').val();
      api.get('/api/reports/daybook?date='+encodeURIComponent(date)).then(d=>{
        const s = d.summary||{};
        $('#dbSummary').html(
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Sales</div><div class="value">${money(s.sales||0)}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Purchases</div><div class="value">${money(s.purchases||0)}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Receipts</div><div class="value">${money(s.receipts||0)}</div></div></div>`+
          `<div class="col-6 col-lg-3"><div class="kpi"><div class="label">Total Payments</div><div class="value">${money(s.payments||0)}</div></div></div>`
        );
        const entries = d.entries||[];
        const typeBadge = t => {
          if(t==='Sale') return 'sf-status-partial';
          if(t==='Purchase') return 'text-bg-warning';
          if(t==='Receipt') return 'bg-light text-secondary border';
          return 'bg-light text-danger border';
        };
        $('#dbRows').html(entries.length===0?'<tr><td colspan="6" class="text-center text-muted py-3">No transactions for this date.</td></tr>'
          : entries.map(e=>`<tr>
              <td class="small">${e.date}</td>
              <td><span class="badge sf-badge ${typeBadge(e.type)}">${esc(e.type)}</span></td>
              <td class="text-num small">${esc(e.ref_no)}</td>
              <td>${esc(e.party)}</td>
              <td class="text-end text-num">${Number(e.debit)>0?money(e.debit):''}</td>
              <td class="text-end text-num">${Number(e.credit)>0?money(e.credit):''}</td>
            </tr>`).join(''));
        if(entries.length>0){
          $('#dbFoot').html(`<tr class="table-light fw-bold"><td colspan="4">Totals</td><td class="text-end text-num">${money(s.sales||0)+(s.payments||0)>0?money((s.sales||0)+(s.payments||0)):''}</td><td class="text-end text-num">${money((s.purchases||0)+(s.receipts||0))}</td></tr>`);
        } else { $('#dbFoot').html(''); }
      });
    };
    $('#dbLoad').on('click', load);
    load();
  }

  function invoiceAgeing(){
    const buckets = ['0-30','31-60','61-90','90+'];
    const renderAgeing = (prefix, d) => {
      $('#'+prefix+'Buckets').html(buckets.map(k=>`<div class="col-6 col-lg-3"><div class="kpi"><div class="label">${k} days</div><div class="value">${money(d.buckets[k]||0)}</div></div></div>`).join(''));
      $('#'+prefix+'Rows').html(d.rows.length===0?'<tr><td colspan="7" class="text-center text-muted py-3">No outstanding invoices.</td></tr>'
        : d.rows.map(r=>`<tr><td class="text-num small">${esc(r.invoice_no)}</td><td>${esc(r.party)}</td><td class="small text-muted">${r.date}</td><td class="text-end text-num">${r.days_overdue}</td><td><span class="badge sf-badge bg-light text-secondary border">${r.bucket}</span></td><td class="text-end text-num">${money(r.total)}</td><td class="text-end text-num text-danger">${money(r.balance)}</td></tr>`).join(''));
    };
    api.get('/api/reports/invoice-ageing?type=sales').then(d=> renderAgeing('sa', d));
    $('a[data-bs-target="#tab-pa"]').on('shown.bs.tab', ()=>{
      api.get('/api/reports/invoice-ageing?type=purchases').then(d=> renderAgeing('pa', d));
    });
  }

  function users(){
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const load = () => api.get('/api/users').then(rows=>{
      $('#rows').html(rows.length===0?'<tr><td colspan="5" class="text-center text-muted py-3">No users.</td></tr>'
        : rows.map(r=>`<tr>
          <td class="fw-semibold">${esc(r.name)}</td><td>${esc(r.email)}</td>
          <td><span class="badge sf-badge ${r.role==='admin'?'sf-status-partial':'bg-light text-secondary border'}">${r.role.toUpperCase()}</span></td>
          <td class="small text-muted">${(r.created_at||'').slice(0,10)}</td>
          <td class="text-end"><button class="btn btn-sm btn-link p-1 text-danger del" data-id="${r.id}" data-email="${esc(r.email)}"><i class="bi bi-trash"></i></button></td>
        </tr>`).join(''));
      $('#rows .del').on('click', function(){
        const id=$(this).data('id'), email=$(this).data('email');
        confirmAction(`Delete user ${email}?`, ()=> api.del('/api/users/'+id).then(()=>{ iziToast.success({title:'Deleted',position:'bottomRight'}); load(); }));
      });
    });
    $('#newBtn').on('click', ()=> modal.show());
    $('#userForm').on('submit', function(e){
      e.preventDefault();
      const data = Object.fromEntries(new FormData(this));
      api.post('/api/users', data).then(()=>{ iziToast.success({title:'Created',position:'bottomRight'}); modal.hide(); this.reset(); load(); });
    });
    load();
  }

  function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

  function companySettings(){
    const load = () => {
      api.get('/api/company/settings').then(s => {
        if (!s) return;
        Object.keys(s).forEach(k => {
          const el = document.querySelector(`[name="${k}"]`);
          if (el) {
            if (el.type === 'checkbox') el.checked = !!s[k];
            else el.value = s[k] ?? '';
          }
        });
        // logo preview
        if (s.company_logo) {
          const pv = $('#logoPreview');
          if (pv.is('img')) pv.attr('src', baseUrl + '/assets/images/' + s.company_logo);
          else pv.replaceWith(`<img id="logoPreview" src="${baseUrl}/assets/images/${s.company_logo}" class="img-fluid mb-3" style="max-height:120px" alt="Logo">`);
        }
        // qr preview
        if (s.bank_qr_code) {
          const qv = $('#qrPreview');
          if (qv.is('img')) qv.attr('src', baseUrl + '/assets/images/' + s.bank_qr_code);
          else qv.replaceWith(`<img id="qrPreview" src="${baseUrl}/assets/images/${s.bank_qr_code}" class="img-fluid border rounded" style="max-height:120px" alt="QR Code">`);
        }
      });
    };

    // Preview logo on file select
    $('#logoInput').on('change', function(){
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          const pv = $('#logoPreview');
          if (pv.is('img')) pv.attr('src', e.target.result);
          else pv.replaceWith(`<img id="logoPreview" src="${e.target.result}" class="img-fluid mb-3" style="max-height:120px" alt="Logo">`);
        };
        reader.readAsDataURL(this.files[0]);
      }
    });

    // Preview QR on file select
    $('#qrInput').on('change', function(){
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          const qv = $('#qrPreview');
          if (qv.is('img')) qv.attr('src', e.target.result);
          else qv.replaceWith(`<img id="qrPreview" src="${e.target.result}" class="img-fluid border rounded" style="max-height:120px" alt="QR Code">`);
        };
        reader.readAsDataURL(this.files[0]);
      }
    });

    $('#companyForm').on('submit', function(e){
      e.preventDefault();
      const btn = $('#saveBtn');
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
      const fd = new FormData(this);
      fd.set('_csrf', csrf());
      $.ajax({
        url: baseUrl + '/api/company/settings',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-Token': csrf() },
        dataType: 'json',
        success: function(){
          iziToast.success({title:'Saved', message:'Company settings updated', position:'bottomRight'});
          btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Save Settings');
          load();
        },
        error: function(xhr){
          btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Save Settings');
          const msg = (xhr.responseJSON && xhr.responseJSON.error) || xhr.statusText || 'Save failed';
          iziToast.error({title:'Error', message: String(msg), position:'bottomRight'});
        }
      });
    });

    load();
  }

  // ---- Tax Types ----
  function taxtypes(){
    const modalEl = document.getElementById('formModal');
    const modal = new bootstrap.Modal(modalEl);
    const load = () => {
      api.get('/api/taxtypes').then(rows => {
        if (!rows.length) return $('#rows').html('<tr><td colspan="5" class="text-center text-muted py-3">No tax types yet.</td></tr>');
        $('#rows').html(rows.map(r => `<tr>
          <td class="fw-semibold">${esc(r.taxname)}</td>
          <td class="text-muted">${esc(r.undergroup)}</td>
          <td class="text-muted">${esc(r.typeofduty)}</td>
          <td class="text-end text-num">${r.percentage}%</td>
          <td class="text-end">
            <button class="btn btn-sm btn-link p-1 text-secondary edit" data-id="${r.id}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-link p-1 text-danger del" data-id="${r.id}" data-name="${esc(r.taxname)}"><i class="bi bi-trash"></i></button>
          </td></tr>`).join(''));
        $('#rows .edit').on('click', function(){
          const r = rows.find(x=>String(x.id)===$(this).data('id').toString());
          const f = document.getElementById('form');
          ['id','taxname','percentage'].forEach(k => { if(f[k]) f[k].value = r[k] ?? ''; });
          if (f.undergroup) f.undergroup.value = r.undergroup || 'Duties & Taxes';
          if (f.typeofduty) f.typeofduty.value = r.typeofduty || 'GST';
          modal.show();
        });
        $('#rows .del').on('click', function(){
          const id=$(this).data('id'), name=$(this).data('name');
          confirmAction(`Delete "${name}"?`, ()=> api.del('/api/taxtypes/'+id).then(()=>{ iziToast.success({title:'Deleted',position:'bottomRight'}); load(); }));
        });
      });
    };
    $('#newBtn').on('click', ()=>{
      const f=document.getElementById('form');
      ['id','taxname','percentage'].forEach(k=>{ if(f[k]) f[k].value=''; });
      if (f.undergroup) f.undergroup.value='Duties & Taxes';
      if (f.typeofduty) f.typeofduty.value='GST';
      modal.show();
    });
    $('#form').on('submit', function(e){
      e.preventDefault();
      const data = Object.fromEntries(new FormData(this));
      data.percentage = Number(data.percentage||0);
      const id = data.id; delete data.id;
      const p = id ? api.put('/api/taxtypes/'+id, data) : api.post('/api/taxtypes', data);
      p.then(()=>{ iziToast.success({title:id?'Updated':'Created', position:'bottomRight'}); modal.hide(); load(); });
    });
    load();
  }

  // ---- Invoice Templates ----
  function invoiceTemplates(){
    const load = () => {
      api.get('/api/invoice-templates').then(templates => {
        const sel = $('#templateSelect');
        sel.find('option:not(:first)').remove();
        if (!templates.length) {
          sel.prop('disabled', true);
          $('#setDefaultBtn').prop('disabled', true);
          $('#previewArea').text('No templates found in invoice-templates/').show();
          $('#previewFrame').hide();
          return;
        }
        templates.forEach(t => sel.append(`<option value="${esc(t.id)}">${esc(t.name)}</option>`));
      });
      api.get('/api/company/settings').then(s => {
        if (s && s.invoice_template) {
          $('#currentDefault').text('Current default: ' + ucwords(s.invoice_template.replace(/[-_]/g, ' ')));
          $('#templateSelect').val(s.invoice_template);
          loadPreview(s.invoice_template);
        }
      });
    };

    const loadPreview = (id) => {
      if (!id) return;
      $('#previewArea').hide();
      const frame = $('#previewFrame');
      frame.attr('src', baseUrl + '/invoice-templates/' + id + '/preview');
      frame.show();
    };

    $('#templateSelect').on('change', function(){
      const val = $(this).val();
      $('#setDefaultBtn').prop('disabled', !val);
      if (val) loadPreview(val);
      else {
        $('#previewFrame').hide();
        $('#previewArea').text('Select a template to preview').show();
      }
    });

    $('#setDefaultBtn').on('click', function(){
      const template = $('#templateSelect').val();
      if (!template) return;
      const btn = $(this);
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
      api.post('/api/invoice-templates/default', { template, _csrf: csrf() })
        .then(() => {
          iziToast.success({title:'Default Set', message:'Invoice template updated', position:'bottomRight'});
          $('#currentDefault').text('Current default: ' + ucwords(template.replace(/[-_]/g, ' ')));
          btn.html('<i class="bi bi-check-lg me-1"></i>Set as Default').prop('disabled', false);
        })
        .catch(() => {
          btn.html('<i class="bi bi-check-lg me-1"></i>Set as Default').prop('disabled', false);
        });
    });

    load();
  }

  // ---- Receipts ----
  function receipts(){
    const load = () => {
      const q = $('#searchInput').val()||'';
      api.get('/api/receipts' + (q?`?q=${encodeURIComponent(q)}`:'')).then(rows=>{
        if (!rows.length) return $('#rows').html('<tr><td colspan="8" class="text-center text-muted py-3">No receipts yet.</td></tr>');
        $('#rows').html(rows.map(r => {
          const modeLabel = {cash:'Cash',cheque:'Cheque',upi:'UPI',transfer:'Transfer'}[r.mode]||r.mode;
          const reconBadge = r.reconciliation_status==='pending'
            ? '<span class="badge sf-badge sf-status-partial" title="Pending Reconciliation">RP</span>'
            : '<span class="badge sf-badge sf-status-paid">Reconciled</span>';
          return `<tr>
            <td class="text-num small fw-semibold">${esc(r.transaction_no)}</td>
            <td>${esc(r.party_name)}</td>
            <td class="small text-muted">${r.transaction_date}</td>
            <td class="text-end text-num">${money(r.amount)}</td>
            <td>${modeLabel}</td>
            <td class="small text-muted">${esc(r.reference_no||'—')}</td>
            <td>${reconBadge}</td>
            <td class="text-end"><a class="btn btn-sm btn-link p-1 text-secondary" href="${baseUrl}/api/receipts/${r.id}/pdf?download" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a></td>
          </tr>`;
        }).join(''));
      });
    };
    $('#searchInput').on('input', debounce(load, 250));
    load();
  }

  function receiptNew(){
    let customers=[], invoices=[], selectedAllocations=[];
    let receiptType='partial', allocMode='fifo';

    api.get('/api/customers').then(c=>{
      customers=c;
      $('#customerSelect').append(customers.map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''));
    });

    $('input[name="receiptType"]').on('change', function(){
      receiptType=this.value;
      if(receiptType==='partial'){
        $('#partialSection').show(); $('#lumpsumSection').hide();
      } else {
        $('#partialSection').hide(); $('#lumpsumSection').show();
      }
      updateSubmitState();
    });

    $('input[name="allocMode"]').on('change', function(){
      allocMode=this.value;
      if(receiptType==='lumpsum') renderAllocations();
    });

    $('input[name="payMode"]').on('change', function(){
      const v=this.value;
      if(v==='cheque'||v==='transfer') $('#refFields').show();
      else $('#refFields').hide();
    });

    $('#customerSelect').on('change', function(){
      const cid=Number(this.value);
      if(!cid){ invoices=[]; clearInvoiceSelect(); return; }
      api.get('/api/customers/'+cid+'/pending-invoices').then(d=>{
        invoices=d.invoices||[];
        populateInvoiceSelect();
        if(receiptType==='lumpsum') renderAllocations();
      });
    });

    function clearInvoiceSelect(){
      $('#invoiceSelect').html('<option value="">— No invoices —</option>').prop('disabled',true);
      $('#invoiceInfo').hide();
    }

    function populateInvoiceSelect(){
      if(!invoices.length){ clearInvoiceSelect(); return; }
      const sel=$('#invoiceSelect');
      sel.html('<option value="">— Select invoice —</option>'+invoices.map(x=>`<option value="${x.id}" data-bal="${x.balance}">${x.invoice_no} — Balance: ${money(x.balance)}</option>`).join('')).prop('disabled',false);
    }

    $('#invoiceSelect').on('change', function(){
      const inv=invoices.find(x=>String(x.id)===String(this.value));
      if(!inv){ $('#invoiceInfo').hide(); return; }
      $('#invTotal').text(money(inv.total));
      $('#invPaid').text(money(inv.paid));
      $('#invRemaining').text(money(inv.balance));
      $('#partialAmount').val('').attr('max',inv.balance).focus();
      $('#invoiceInfo').show();
    });

    $('#partialAmount').on('input', function(){
      const inv=invoices.find(x=>String(x.id)===$('#invoiceSelect').val());
      if(!inv) return;
      const amt=Math.min(Number(this.value||0),Number(inv.balance));
      const v=amt>0?amt:'';
      $('#totalReceiveDisplay').text(money(v));
      updateSubmitState();
    });

    $('#lumpsumAmount').on('input', function(){
      renderAllocations();
    });

    function renderAllocations(){
      const totalAmt=Number($('#lumpsumAmount').val()||0);
      if(!invoices.length||totalAmt<=0){
        $('#allocRows').html('<tr><td colspan="7" class="text-center text-muted py-3">No pending invoices</td></tr>');
        $('#surplusAmt').text(money(0));
        $('#totalReceiveDisplay').text(money(0));
        updateSubmitState();
        return;
      }

      if(allocMode==='fifo'){
        selectedAllocations=[];
        let remaining=totalAmt;
        for(const inv of invoices){
          if(remaining<=0) break;
          const bal=Number(inv.balance);
          const alloc=Math.min(remaining,bal);
          selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.invoice_no,amount:alloc});
          remaining-=alloc;
        }
        $('#allocRows').html(invoices.map(inv=>{
          const sa=selectedAllocations.find(a=>a.invoice_id===inv.id);
          const allocAmt=sa?sa.amount:0;
          const disabled=allocAmt===0?'disabled':'';
          return `<tr>
            <td><input class="form-check-input alloc-cb" type="checkbox" data-id="${inv.id}" ${allocAmt>0?'checked':''} ${disabled?'disabled':''}></td>
            <td class="text-num small">${esc(inv.invoice_no)}</td>
            <td class="small text-muted">${inv.sale_date}</td>
            <td class="text-end text-num">${money(inv.total)}</td>
            <td class="text-end text-num">${money(inv.paid)}</td>
            <td class="text-end text-num">${money(inv.balance)}</td>
            <td class="text-end"><input class="form-control form-control-sm text-end text-num alloc-amt" data-id="${inv.id}" type="number" step="any" min="0" max="${inv.balance}" value="${allocAmt}" ${disabled} style="width:100px"></td>
          </tr>`;
        }).join(''));
        $('#surplusAmt').text(money(Math.max(0,remaining)));
        $('#totalReceiveDisplay').text(money(totalAmt));
      } else {
        if(!selectedAllocations.length){
          $('#allocRows').html(invoices.map(inv=>{
            return `<tr>
              <td><input class="form-check-input alloc-cb" type="checkbox" data-id="${inv.id}"></td>
              <td class="text-num small">${esc(inv.invoice_no)}</td>
              <td class="small text-muted">${inv.sale_date}</td>
              <td class="text-end text-num">${money(inv.total)}</td>
              <td class="text-end text-num">${money(inv.paid)}</td>
              <td class="text-end text-num">${money(inv.balance)}</td>
              <td class="text-end"><input class="form-control form-control-sm text-end text-num alloc-amt" data-id="${inv.id}" type="number" step="any" min="0" max="${inv.balance}" value="0" disabled style="width:100px"></td>
            </tr>`;
          }).join(''));
        }
        bindAllocEvents();
      }
      updateSubmitState();
    }

    $(document).on('change','.alloc-cb',function(){
      const id=Number($(this).data('id'));
      const inv=invoices.find(x=>x.id===id);
      if(!inv) return;
      const amtInput=$(`.alloc-amt[data-id="${id}"]`);
      if(this.checked){
        amtInput.prop('disabled',false).val(inv.balance).focus();
        const existing=selectedAllocations.find(a=>a.invoice_id===id);
        if(!existing) selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.invoice_no,amount:Number(inv.balance)});
      } else {
        amtInput.prop('disabled',true).val(0);
        selectedAllocations=selectedAllocations.filter(a=>a.invoice_id!==id);
      }
      recalcLumpsum();
    });

    $(document).on('input','.alloc-amt',function(){
      const id=Number($(this).data('id'));
      const inv=invoices.find(x=>x.id===id);
      if(!inv) return;
      const val=Math.min(Number(this.value||0),Number(inv.balance));
      const existing=selectedAllocations.find(a=>a.invoice_id===id);
      if(existing) existing.amount=val;
      else selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.invoice_no,amount:val});
      recalcLumpsum();
    });

    function recalcLumpsum(){
      const totalAmt=Number($('#lumpsumAmount').val()||0);
      const totalAlloc=selectedAllocations.reduce((s,a)=>s+a.amount,0);
      const surplus=Math.max(0,totalAmt-totalAlloc);
      $('#surplusAmt').text(money(surplus));
      $('#totalReceiveDisplay').text(money(totalAmt));
      updateSubmitState();
    }

    function bindAllocEvents(){
      $(document).off('change','.alloc-cb').off('input','.alloc-amt');
      $(document).on('change','.alloc-cb',function(){
        const id=Number($(this).data('id'));
        const inv=invoices.find(x=>x.id===id);
        if(!inv) return;
        const amtInput=$(`.alloc-amt[data-id="${id}"]`);
        if(this.checked){
          amtInput.prop('disabled',false).val(inv.balance).focus();
          selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.invoice_no,amount:Number(inv.balance)});
        } else {
          amtInput.prop('disabled',true).val(0);
          selectedAllocations=selectedAllocations.filter(a=>a.invoice_id!==id);
        }
        recalcLumpsum();
      });
      $(document).on('input','.alloc-amt',function(){
        const id=Number($(this).data('id'));
        const inv=invoices.find(x=>x.id===id);
        if(!inv) return;
        const val=Math.min(Number(this.value||0),Number(inv.balance));
        const existing=selectedAllocations.find(a=>a.invoice_id===id);
        if(existing) existing.amount=val;
        else selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.invoice_no,amount:val});
        recalcLumpsum();
      });
    }

    function updateSubmitState(){
      const hasCustomer=Number($('#customerSelect').val()||0)>0;
      let hasAmount=false;
      if(receiptType==='partial'){
        hasAmount=Number($('#partialAmount').val()||0)>0;
      } else {
        hasAmount=selectedAllocations.some(a=>a.amount>0)&&Number($('#lumpsumAmount').val()||0)>0;
      }
      $('#submitReceipt').prop('disabled',!(hasCustomer&&hasAmount));
    }

    $('#submitReceipt').on('click', function(){
      const btn=$(this);
      const customerId=Number($('#customerSelect').val()||0);
      if(!customerId) return iziToast.error({title:'Error',message:'Select a customer',position:'bottomRight'});

      let amount=0, allocations=[];
      if(receiptType==='partial'){
        const inv=invoices.find(x=>String(x.id)===$('#invoiceSelect').val());
        if(!inv) return iziToast.error({title:'Error',message:'Select an invoice',position:'bottomRight'});
        amount=Number($('#partialAmount').val()||0);
        if(amount<=0) return iziToast.error({title:'Error',message:'Enter a valid amount',position:'bottomRight'});
        if(amount>Number(inv.balance)) return iziToast.error({title:'Error',message:'Amount exceeds invoice balance',position:'bottomRight'});
        allocations=[{invoice_id:inv.id,invoice_no:inv.invoice_no,amount:amount}];
      } else {
        amount=Number($('#lumpsumAmount').val()||0);
        if(amount<=0) return iziToast.error({title:'Error',message:'Enter the received amount',position:'bottomRight'});
        const validAllocs=selectedAllocations.filter(a=>a.amount>0);
        if(!validAllocs.length) return iziToast.error({title:'Error',message:'Select at least one invoice',position:'bottomRight'});
        allocations=validAllocs;
      }

      btn.prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');
      api.post('/api/receipts',{
        customer_id:customerId, amount:amount,
        mode:$('input[name="payMode"]:checked').val(),
        transaction_date:$('#txDate').val(),
        reference_no:$('#referenceNo').val(),
        bank_name:$('#bankName').val(),
        notes:$('#txNotes').val(),
        allocations:allocations,
      }).then(r=>{
        iziToast.success({title:`Receipt ${r.transaction_no} created`,position:'bottomRight'});
        window.location.href=baseUrl+'/api/receipts/'+r.id+'/pdf?download';
        setTimeout(()=>location.href=baseUrl+'/receipts',1500);
      }).catch(()=>{
        btn.prop('disabled',false).html('<i class="bi bi-check-lg me-1"></i>Submit Receipt');
      });
    });
  }

  // ---- Payments ----
  function payments(){
    const load = () => {
      const q = $('#searchInput').val()||'';
      api.get('/api/payments' + (q?`?q=${encodeURIComponent(q)}`:'')).then(rows=>{
        if (!rows.length) return $('#rows').html('<tr><td colspan="8" class="text-center text-muted py-3">No payments yet.</td></tr>');
        $('#rows').html(rows.map(r => {
          const modeLabel = {cash:'Cash',cheque:'Cheque',upi:'UPI',transfer:'Transfer'}[r.mode]||r.mode;
          const reconBadge = r.reconciliation_status==='pending'
            ? '<span class="badge sf-badge sf-status-partial" title="Pending Reconciliation">RP</span>'
            : '<span class="badge sf-badge sf-status-paid">Reconciled</span>';
          return `<tr>
            <td class="text-num small fw-semibold">${esc(r.transaction_no)}</td>
            <td>${esc(r.party_name)}</td>
            <td class="small text-muted">${r.transaction_date}</td>
            <td class="text-end text-num">${money(r.amount)}</td>
            <td>${modeLabel}</td>
            <td class="small text-muted">${esc(r.reference_no||'—')}</td>
            <td>${reconBadge}</td>
            <td class="text-end"><a class="btn btn-sm btn-link p-1 text-secondary" href="${baseUrl}/api/payments/${r.id}/pdf?download" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a></td>
          </tr>`;
        }).join(''));
      });
    };
    $('#searchInput').on('input', debounce(load, 250));
    load();
  }

  function paymentNew(){
    let suppliers=[], invoices=[], selectedAllocations=[];
    let paymentType='partial', allocMode='fifo';

    api.get('/api/suppliers').then(s=>{
      suppliers=s;
      $('#supplierSelect').append(suppliers.map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''));
    });

    $('input[name="paymentType"]').on('change', function(){
      paymentType=this.value;
      if(paymentType==='partial'){
        $('#partialSection').show(); $('#lumpsumSection').hide();
      } else {
        $('#partialSection').hide(); $('#lumpsumSection').show();
      }
      updateSubmitState();
    });

    $('input[name="allocMode"]').on('change', function(){
      allocMode=this.value;
      selectedAllocations=[];
      if(paymentType==='lumpsum') renderAllocations();
    });

    $('input[name="payMode"]').on('change', function(){
      const v=this.value;
      if(v==='cheque'||v==='transfer') $('#refFields').show();
      else $('#refFields').hide();
    });

    $('#supplierSelect').on('change', function(){
      const sid=Number(this.value);
      if(!sid){ invoices=[]; clearInvoiceSelect(); return; }
      api.get('/api/suppliers/'+sid+'/pending-invoices').then(d=>{
        invoices=d.invoices||[];
        populateInvoiceSelect();
        if(paymentType==='lumpsum') renderAllocations();
      });
    });

    function clearInvoiceSelect(){
      $('#invoiceSelect').html('<option value="">— No invoices —</option>').prop('disabled',true);
      $('#invoiceInfo').hide();
    }

    function populateInvoiceSelect(){
      if(!invoices.length){ clearInvoiceSelect(); return; }
      const sel=$('#invoiceSelect');
      sel.html('<option value="">— Select invoice —</option>'+invoices.map(x=>`<option value="${x.id}" data-bal="${x.balance}">${x.ref_no} — Balance: ${money(x.balance)}</option>`).join('')).prop('disabled',false);
    }

    $('#invoiceSelect').on('change', function(){
      const inv=invoices.find(x=>String(x.id)===String(this.value));
      if(!inv){ $('#invoiceInfo').hide(); return; }
      $('#invTotal').text(money(inv.total));
      $('#invPaid').text(money(inv.paid));
      $('#invRemaining').text(money(inv.balance));
      $('#partialAmount').val('').attr('max',inv.balance).focus();
      $('#invoiceInfo').show();
    });

    $('#partialAmount').on('input', function(){
      const inv=invoices.find(x=>String(x.id)===$('#invoiceSelect').val());
      if(!inv) return;
      const amt=Math.min(Number(this.value||0),Number(inv.balance));
      const v=amt>0?amt:'';
      $('#totalPayDisplay').text(money(v));
      updateSubmitState();
    });

    $('#lumpsumAmount').on('input', function(){
      renderAllocations();
    });

    function renderAllocations(){
      const totalAmt=Number($('#lumpsumAmount').val()||0);
      if(!invoices.length||totalAmt<=0){
        $('#allocRows').html('<tr><td colspan="7" class="text-center text-muted py-3">No pending invoices</td></tr>');
        $('#surplusAmt').text(money(0));
        $('#totalPayDisplay').text(money(0));
        updateSubmitState();
        return;
      }

      if(allocMode==='fifo'){
        selectedAllocations=[];
        let remaining=totalAmt;
        for(const inv of invoices){
          if(remaining<=0) break;
          const bal=Number(inv.balance);
          const alloc=Math.min(remaining,bal);
          selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.ref_no,amount:alloc});
          remaining-=alloc;
        }
        $('#allocRows').html(invoices.map(inv=>{
          const sa=selectedAllocations.find(a=>a.invoice_id===inv.id);
          const allocAmt=sa?sa.amount:0;
          const disabled=allocAmt===0?'disabled':'';
          return `<tr>
            <td><input class="form-check-input alloc-cb" type="checkbox" data-id="${inv.id}" ${allocAmt>0?'checked':''} ${disabled?'disabled':''}></td>
            <td class="text-num small">${esc(inv.ref_no)}</td>
            <td class="small text-muted">${inv.purchase_date}</td>
            <td class="text-end text-num">${money(inv.total)}</td>
            <td class="text-end text-num">${money(inv.paid)}</td>
            <td class="text-end text-num">${money(inv.balance)}</td>
            <td class="text-end"><input class="form-control form-control-sm text-end text-num alloc-amt" data-id="${inv.id}" type="number" step="any" min="0" max="${inv.balance}" value="${allocAmt}" ${disabled} style="width:100px"></td>
          </tr>`;
        }).join(''));
        $('#surplusAmt').text(money(Math.max(0,remaining)));
        $('#totalPayDisplay').text(money(totalAmt));
      } else {
        $('#allocRows').html(invoices.map(inv=>{
          const sa=selectedAllocations.find(a=>a.invoice_id===inv.id);
          const allocAmt=sa?sa.amount:0;
          const checked=allocAmt>0?'checked':'';
          const amtDisabled=allocAmt===0?'disabled':'';
          return `<tr>
            <td><input class="form-check-input alloc-cb" type="checkbox" data-id="${inv.id}" ${checked}></td>
            <td class="text-num small">${esc(inv.ref_no)}</td>
            <td class="small text-muted">${inv.purchase_date}</td>
            <td class="text-end text-num">${money(inv.total)}</td>
            <td class="text-end text-num">${money(inv.paid)}</td>
            <td class="text-end text-num">${money(inv.balance)}</td>
            <td class="text-end"><input class="form-control form-control-sm text-end text-num alloc-amt" data-id="${inv.id}" type="number" step="any" min="0" max="${inv.balance}" value="${allocAmt}" ${amtDisabled} style="width:100px"></td>
          </tr>`;
        }).join(''));
        recalcLumpsum();
        syncAllocLocks();
      }
      updateSubmitState();
    }

    $(document).on('change','.alloc-cb',function(){
      if(allocMode!=='manual') return;
      const id=Number($(this).data('id'));
      const inv=invoices.find(x=>x.id===id);
      if(!inv) return;
      const amtInput=$(`.alloc-amt[data-id="${id}"]`);
      if(this.checked){
        const totalAmt=Number($('#lumpsumAmount').val()||0);
        const currentAlloc=selectedAllocations.reduce((s,a)=>s+a.amount,0);
        const remaining=Math.max(0,totalAmt-currentAlloc);
        const fillAmt=Math.min(remaining,Number(inv.balance));
        amtInput.prop('disabled',false).val(fillAmt).focus();
        const existing=selectedAllocations.find(a=>a.invoice_id===id);
        if(existing) existing.amount=fillAmt;
        else selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.ref_no,amount:fillAmt});
      } else {
        amtInput.prop('disabled',true).val(0);
        selectedAllocations=selectedAllocations.filter(a=>a.invoice_id!==id);
      }
      recalcLumpsum();
      syncAllocLocks();
    });

    $(document).on('input','.alloc-amt',function(){
      if(allocMode!=='manual') return;
      const id=Number($(this).data('id'));
      const inv=invoices.find(x=>x.id===id);
      if(!inv) return;
      const val=Math.min(Number(this.value||0),Number(inv.balance));
      const existing=selectedAllocations.find(a=>a.invoice_id===id);
      if(existing) existing.amount=val;
      else selectedAllocations.push({invoice_id:inv.id,invoice_no:inv.ref_no,amount:val});
      recalcLumpsum();
      syncAllocLocks();
    });

    function recalcLumpsum(){
      const totalAmt=Number($('#lumpsumAmount').val()||0);
      const totalAlloc=selectedAllocations.reduce((s,a)=>s+a.amount,0);
      const surplus=Math.max(0,totalAmt-totalAlloc);
      $('#surplusAmt').text(money(surplus));
      $('#totalPayDisplay').text(money(totalAmt));
      updateSubmitState();
    }

    function syncAllocLocks(){
      if(allocMode!=='manual') return;
      if(!selectedAllocations.length){
        $('#allocRows .alloc-cb').prop('disabled',false);
        return;
      }
      const totalAmt=Number($('#lumpsumAmount').val()||0);
      const totalAlloc=selectedAllocations.reduce((s,a)=>s+a.amount,0);
      const remaining=Math.max(0,totalAmt-totalAlloc);
      $('#allocRows tr').each(function(){
        const cb=$(this).find('.alloc-cb');
        const inp=$(this).find('.alloc-amt');
        const id=Number(cb.data('id'));
        const inv=invoices.find(x=>x.id===id);
        if(!inv) return;
        if(cb.prop('checked')) return;
        if(remaining<=0||Number(inv.balance)>remaining){
          cb.prop('disabled',true);
          inp.prop('disabled',true);
        } else {
          cb.prop('disabled',false);
          inp.prop('disabled',true);
        }
      });
    }

    function updateSubmitState(){
      const hasSupplier=Number($('#supplierSelect').val()||0)>0;
      let hasAmount=false;
      if(paymentType==='partial'){
        hasAmount=Number($('#partialAmount').val()||0)>0;
      } else {
        hasAmount=selectedAllocations.some(a=>a.amount>0)&&Number($('#lumpsumAmount').val()||0)>0;
      }
      $('#submitPayment').prop('disabled',!(hasSupplier&&hasAmount));
    }

    $('#submitPayment').on('click', function(){
      const btn=$(this);
      const supplierId=Number($('#supplierSelect').val()||0);
      if(!supplierId) return iziToast.error({title:'Error',message:'Select a supplier',position:'bottomRight'});

      let amount=0, allocations=[];
      if(paymentType==='partial'){
        const inv=invoices.find(x=>String(x.id)===$('#invoiceSelect').val());
        if(!inv) return iziToast.error({title:'Error',message:'Select an invoice',position:'bottomRight'});
        amount=Number($('#partialAmount').val()||0);
        if(amount<=0) return iziToast.error({title:'Error',message:'Enter a valid amount',position:'bottomRight'});
        if(amount>Number(inv.balance)) return iziToast.error({title:'Error',message:'Amount exceeds invoice balance',position:'bottomRight'});
        allocations=[{invoice_id:inv.id,invoice_no:inv.ref_no,amount:amount}];
      } else {
        amount=Number($('#lumpsumAmount').val()||0);
        if(amount<=0) return iziToast.error({title:'Error',message:'Enter the payment amount',position:'bottomRight'});
        const validAllocs=selectedAllocations.filter(a=>a.amount>0);
        if(!validAllocs.length) return iziToast.error({title:'Error',message:'Select at least one invoice',position:'bottomRight'});
        allocations=validAllocs;
      }

      btn.prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');
      api.post('/api/payments',{
        supplier_id:supplierId, amount:amount,
        mode:$('input[name="payMode"]:checked').val(),
        transaction_date:$('#txDate').val(),
        reference_no:$('#referenceNo').val(),
        bank_name:$('#bankName').val(),
        notes:$('#txNotes').val(),
        allocations:allocations,
      }).then(r=>{
        iziToast.success({title:`Payment ${r.transaction_no} created`,position:'bottomRight'});
        window.location.href=baseUrl+'/api/payments/'+r.id+'/pdf?download';
        setTimeout(()=>location.href=baseUrl+'/payments',1500);
      }).catch(()=>{
        btn.prop('disabled',false).html('<i class="bi bi-check-lg me-1"></i>Submit Payment');
      });
    });
  }

  // ---- Reconciliation ----
  function reconciliationReceipts(){
    let allData = [], filtered = [];
    const $rows = $('#rows');
    const unique = (arr, key) => [...new Set(arr.map(r => r[key]).filter(Boolean))].sort();

    const buildOptions = (sel, values) => {
      const $el = $(sel), cur = $el.val();
      $el.find('option:gt(0)').remove();
      values.forEach(v => $el.append(`<option value="${esc(v)}">${esc(v)}</option>`));
      if (cur && values.includes(cur)) $el.val(cur);
    };

    const applyFilters = () => {
      const cust = $('#fCustomer').val();
      const rno  = $('#fReceiptNo').val();
      const inv  = ($('#fInvNo').val()||'').toLowerCase();
      const ref  = ($('#fRefNo').val()||'').toLowerCase();
      filtered = allData.filter(r => {
        if (cust && r.party_name !== cust) return false;
        if (rno && r.transaction_no !== rno) return false;
        if (inv && !(r.allocations||[]).some(a => (a.invoice_no||'').toLowerCase().includes(inv))) return false;
        if (ref && !(r.reference_no||'').toLowerCase().includes(ref)) return false;
        return true;
      });
      render();
    };

    const render = () => {
      if (!filtered.length) { $rows.html('<tr><td colspan="7" class="text-center text-muted py-3">No pending receipts found.</td></tr>'); return; }
      $rows.html(filtered.map(r => {
        const modeLabel = {cash:'Cash',cheque:'Cheque',upi:'UPI',transfer:'Transfer'}[r.mode]||r.mode;
        return `<tr>
          <td class="text-num small fw-semibold">${esc(r.transaction_no)}</td>
          <td>${esc(r.party_name)}</td>
          <td class="small text-muted">${r.transaction_date}</td>
          <td class="text-end text-num">${money(r.amount)}</td>
          <td>${modeLabel}</td>
          <td class="small text-muted">${esc(r.reference_no||'—')}</td>
          <td class="text-end"><button class="btn btn-sm sf-btn-primary settle-btn" data-id="${r.id}" data-no="${esc(r.transaction_no)}">Settle</button></td>
        </tr>`;
      }).join(''));
      $('.settle-btn').on('click', function(){
        const id=$(this).data('id'), no=$(this).data('no');
        confirmAction(`Settle receipt ${no}?`, ()=>{
          api.post('/api/reconciliation/'+id+'/settle').then(()=>{
            iziToast.success({title:`${no} settled`,position:'bottomRight'});
            load();
          });
        });
      });
    };

    const load = () => {
      api.get('/api/reconciliation/receipts').then(rows => {
        allData = rows || [];
        buildOptions('#fCustomer', unique(allData, 'party_name'));
        buildOptions('#fReceiptNo', unique(allData, 'transaction_no'));
        applyFilters();
      });
    };

    $('#fCustomer, #fReceiptNo').on('change', applyFilters);
    $('#fInvNo, #fRefNo').on('input', applyFilters);
    $('#fClear').on('click', () => {
      $('#fCustomer, #fReceiptNo').val('');
      $('#fInvNo, #fRefNo').val('');
      applyFilters();
    });
    load();
  }

  function reconciliationPayments(){
    let allData = [], filtered = [];
    const $rows = $('#rows');
    const unique = (arr, key) => [...new Set(arr.map(r => r[key]).filter(Boolean))].sort();

    const buildOptions = (sel, values) => {
      const $el = $(sel), cur = $el.val();
      $el.find('option:gt(0)').remove();
      values.forEach(v => $el.append(`<option value="${esc(v)}">${esc(v)}</option>`));
      if (cur && values.includes(cur)) $el.val(cur);
    };

    const applyFilters = () => {
      const supp  = $('#fSupplier').val();
      const pno   = $('#fPaymentNo').val();
      const sInv  = ($('#fSupplierInv').val()||'').toLowerCase();
      const pInv  = ($('#fPurchaseInv').val()||'').toLowerCase();
      const ref   = ($('#fRefNo').val()||'').toLowerCase();
      const amt   = ($('#fAmount').val()||'').trim();
      filtered = allData.filter(r => {
        if (supp && r.party_name !== supp) return false;
        if (pno && r.transaction_no !== pno) return false;
        if (ref && !(r.reference_no||'').toLowerCase().includes(ref)) return false;
        if (amt && !money(r.amount).includes(amt)) return false;
        if (sInv || pInv) {
          const allocs = r.allocations || [];
          const match = allocs.some(a => {
            if (sInv && (a.supplier_inv_no||'').toLowerCase().includes(sInv)) return true;
            if (pInv && (a.invoice_no||'').toLowerCase().includes(pInv)) return true;
            return false;
          });
          if (!match) return false;
        }
        return true;
      });
      render();
    };

    const render = () => {
      if (!filtered.length) { $rows.html('<tr><td colspan="7" class="text-center text-muted py-3">No pending payments found.</td></tr>'); return; }
      $rows.html(filtered.map(r => {
        const modeLabel = {cash:'Cash',cheque:'Cheque',upi:'UPI',transfer:'Transfer'}[r.mode]||r.mode;
        return `<tr>
          <td class="text-num small fw-semibold">${esc(r.transaction_no)}</td>
          <td>${esc(r.party_name)}</td>
          <td class="small text-muted">${r.transaction_date}</td>
          <td class="text-end text-num">${money(r.amount)}</td>
          <td>${modeLabel}</td>
          <td class="small text-muted">${esc(r.reference_no||'—')}</td>
          <td class="text-end"><button class="btn btn-sm sf-btn-primary settle-btn" data-id="${r.id}" data-no="${esc(r.transaction_no)}">Settle</button></td>
        </tr>`;
      }).join(''));
      $('.settle-btn').on('click', function(){
        const id=$(this).data('id'), no=$(this).data('no');
        confirmAction(`Settle payment ${no}?`, ()=>{
          api.post('/api/reconciliation/'+id+'/settle').then(()=>{
            iziToast.success({title:`${no} settled`,position:'bottomRight'});
            load();
          });
        });
      });
    };

    const load = () => {
      api.get('/api/reconciliation/payments').then(rows => {
        allData = rows || [];
        buildOptions('#fSupplier', unique(allData, 'party_name'));
        buildOptions('#fPaymentNo', unique(allData, 'transaction_no'));
        applyFilters();
      });
    };

    $('#fSupplier, #fPaymentNo').on('change', applyFilters);
    $('#fSupplierInv, #fPurchaseInv, #fRefNo, #fAmount').on('input', applyFilters);
    $('#fClear').on('click', () => {
      $('#fSupplier, #fPaymentNo').val('');
      $('#fSupplierInv, #fPurchaseInv, #fRefNo, #fAmount').val('');
      applyFilters();
    });
    load();
  }

  return { dashboard, products, party, sales, saleNew, saleView, saleEdit, purchases, reports, partyOutstanding, partyLedger, saleReport, productReport, purchaseReport, daybook, invoiceAgeing, users, companySettings, taxtypes, invoiceTemplates, receipts, receiptNew, payments, paymentNew, reconciliationReceipts, reconciliationPayments };
})();
