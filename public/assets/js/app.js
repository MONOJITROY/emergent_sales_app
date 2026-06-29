// StockFlow client app
const SF = (function(){
  const $ = jQuery;
  const baseUrl = $('meta[name="base-url"]').attr('content') || '';
  const csrf    = () => $('meta[name="csrf-token"]').attr('content') || '';
  const money   = (v) => Number(v||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});

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
        kpi("Today's sales", money(s.today_sales), 'USD') +
        kpi('Outstanding', money(s.outstanding), 'Across all unpaid') +
        kpi('Products', s.products_count, s.low_stock_count + ' low') +
        kpi('Customers', s.customers_count, '')
      );
      // chart
      new Chart(document.getElementById('salesChart'), {
        type:'bar',
        data:{ labels: s.chart.map(c=>c.date.slice(5)), datasets:[{ data: s.chart.map(c=>c.total), backgroundColor:'#ea580c', borderRadius:3 }]},
        options:{ plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,grid:{color:'#e2e8f0'}}, x:{grid:{display:false}} } }
      });
      $('#lowStock').html(
        (s.low_stock_items||[]).length === 0
          ? '<li class="text-muted text-center py-3">All stock levels healthy.</li>'
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
        // attach
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
      ['id','hsn','sku','name','category','unit','cost_price','sale_price','stock','reorder_level'].forEach(k => { if (f[k]) f[k].value = r ? (r[k]??'') : (k==='unit'?'pcs':(['cost_price','sale_price','stock','reorder_level'].includes(k)?0:'')); });
    };
    $('#newBtn').on('click', ()=>{ fillForm(null); modal.show(); });
    $('#form').on('submit', function(e){
      e.preventDefault();
      const f = this, data = Object.fromEntries(new FormData(f));
      ['cost_price','sale_price','stock','reorder_level'].forEach(k=> data[k] = Number(data[k]||0));
      const id = data.id; delete data.id;
      const p = id ? api.put('/api/products/'+id, data) : api.post('/api/products', data);
      p.then(()=>{ iziToast.success({title: id?'Updated':'Created', position:'bottomRight'}); modal.hide(); load(); });
    });
    $('#searchInput').on('input', debounce(load, 250));
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
    let products = [], customers = [], lines = [];
    Promise.all([api.get('/api/products'), api.get('/api/customers')]).then(([p,c])=>{
      products = p; customers = c;
      $('#customerSelect').append(customers.map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''));
    });
    $('#customerSelect').on('change', function(){
      const c = customers.find(x=>String(x.id)===this.value);
      $('#customerName').val(c?c.name:'Walk-in customer');
    });
    $('#addLine').on('click', ()=>{ lines.push({product_id:'',sku:'',name:'',qty:1,price:0,total:0}); render(); });

    function render(){
      $('#lines').html(lines.length===0
        ? '<tr><td colspan="5" class="text-center text-muted py-3 small">No items yet.</td></tr>'
        : lines.map((it,i)=>`<tr>
            <td><select class="form-select form-select-sm ln-prod" data-i="${i}"><option value="">— pick product —</option>${products.map(p=>`<option value="${p.id}" ${String(p.id)===String(it.product_id)?'selected':''}>${esc(p.sku)} — ${esc(p.name)} (stock ${p.stock})</option>`).join('')}</select></td>
            <td class="text-end"><input class="form-control form-control-sm text-end ln-qty" data-i="${i}" type="number" step="any" style="width:80px" value="${it.qty}"></td>
            <td class="text-end"><input class="form-control form-control-sm text-end ln-price" data-i="${i}" type="number" step="any" style="width:100px" value="${it.price}"></td>
            <td class="text-end text-num">${money(it.total)}</td>
            <td class="text-end"><button class="btn btn-sm btn-link text-danger p-0 ln-del" data-i="${i}"><i class="bi bi-trash"></i></button></td>
          </tr>`).join(''));
      $('.ln-prod').on('change', function(){ const i=+$(this).data('i'),pid=this.value; const p=products.find(x=>String(x.id)===pid); if(p){lines[i].product_id=p.id;lines[i].sku=p.sku;lines[i].name=p.name;lines[i].price=Number(p.sale_price);} lines[i].total=Number(lines[i].qty)*Number(lines[i].price); render(); });
      $('.ln-qty').on('input', function(){ const i=+$(this).data('i'); lines[i].qty=Number(this.value||0); lines[i].total=lines[i].qty*Number(lines[i].price||0); render(); });
      $('.ln-price').on('input', function(){ const i=+$(this).data('i'); lines[i].price=Number(this.value||0); lines[i].total=lines[i].qty*lines[i].price; render(); });
      $('.ln-del').on('click', function(){ lines.splice(+$(this).data('i'),1); render(); });
      totals();
    }
    function totals(){
      const sub = lines.reduce((s,it)=>s+Number(it.total||0),0);
      const tot = Math.max(0, sub - Number($('#discount').val()||0) + Number($('#tax').val()||0));
      $('#subtotal').text(money(sub)); $('#total').text(money(tot));
      $('#balance').text(money(Math.max(0, tot - Number($('#paid').val()||0))));
    }
    $('#discount,#tax,#paid').on('input', totals);

    $('#saveSale').on('click', ()=>{
      if(lines.length===0) return iziToast.error({title:'Error',message:'Add at least one line item',position:'bottomRight'});
      if(lines.some(it=>!it.product_id||Number(it.qty)<=0)) return iziToast.error({title:'Error',message:'Each line needs a product and qty>0',position:'bottomRight'});
      api.post('/api/sales', {
        customer_id: $('#customerSelect').val() || null,
        customer_name: $('#customerName').val(),
        items: lines, discount: Number($('#discount').val()||0), tax: Number($('#tax').val()||0), paid: Number($('#paid').val()||0), notes: $('#notes').val(),
      }).then((s)=>{
        iziToast.success({title:`Invoice ${s.invoice_no} created`,position:'bottomRight'});
        setTimeout(()=> location.href = baseUrl + '/sales/' + s.id, 400);
      });
    });
  }

  function saleView(id){
    $('#payBtn').on('click', function(){
      const amt = Number($('#payAmt').val()||0);
      if (amt<=0) return iziToast.error({title:'Error',message:'Enter a positive amount',position:'bottomRight'});
      api.post('/api/sales/'+id+'/payment', {amount: amt}).then(()=>{ iziToast.success({title:'Payment recorded',position:'bottomRight'}); setTimeout(()=>location.reload(),400); });
    });
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
    let products=[], customers=[], lines = (sale.items||[]).map(it => ({product_id:+it.product_id, sku:it.sku, name:it.name, qty:Number(it.qty), price:Number(it.price), total:Number(it.total)}));
    Promise.all([api.get('/api/products'), api.get('/api/customers')]).then(([p,c])=>{
      products = p; customers = c;
      $('#customerSelect').append(customers.map(x=>`<option value="${x.id}" ${String(x.id)===String(sale.customer_id||'')?'selected':''}>${esc(x.name)}</option>`).join(''));
      render();
    });
    $('#customerSelect').on('change', function(){ const c=customers.find(x=>String(x.id)===this.value); $('#customerName').val(c?c.name:'Walk-in customer'); });
    $('#addLine').on('click', ()=>{ lines.push({product_id:'',sku:'',name:'',qty:1,price:0,total:0}); render(); });
    function render(){
      $('#lines').html(lines.length===0
        ? '<tr><td colspan="5" class="text-center text-muted py-3 small">No items.</td></tr>'
        : lines.map((it,i)=>`<tr>
            <td><select class="form-select form-select-sm ln-prod" data-i="${i}"><option value="">— pick product —</option>${products.map(p=>`<option value="${p.id}" ${String(p.id)===String(it.product_id)?'selected':''}>${esc(p.sku)} — ${esc(p.name)} (stock ${p.stock})</option>`).join('')}</select></td>
            <td class="text-end"><input class="form-control form-control-sm text-end ln-qty" data-i="${i}" type="number" step="any" style="width:80px" value="${it.qty}"></td>
            <td class="text-end"><input class="form-control form-control-sm text-end ln-price" data-i="${i}" type="number" step="any" style="width:100px" value="${it.price}"></td>
            <td class="text-end text-num">${money(it.total)}</td>
            <td class="text-end"><button class="btn btn-sm btn-link text-danger p-0 ln-del" data-i="${i}"><i class="bi bi-trash"></i></button></td>
          </tr>`).join(''));
      $('.ln-prod').on('change', function(){ const i=+$(this).data('i'),pid=this.value; const p=products.find(x=>String(x.id)===pid); if(p){lines[i].product_id=p.id;lines[i].sku=p.sku;lines[i].name=p.name;lines[i].price=Number(p.sale_price);} lines[i].total=Number(lines[i].qty)*Number(lines[i].price); render(); });
      $('.ln-qty').on('input', function(){ const i=+$(this).data('i'); lines[i].qty=Number(this.value||0); lines[i].total=lines[i].qty*Number(lines[i].price||0); render(); });
      $('.ln-price').on('input', function(){ const i=+$(this).data('i'); lines[i].price=Number(this.value||0); lines[i].total=lines[i].qty*lines[i].price; render(); });
      $('.ln-del').on('click', function(){ lines.splice(+$(this).data('i'),1); render(); });
      totals();
    }
    function totals(){
      const sub = lines.reduce((s,it)=>s+Number(it.total||0),0);
      const tot = Math.max(0, sub - Number($('#discount').val()||0) + Number($('#tax').val()||0));
      $('#subtotal').text(money(sub)); $('#total').text(money(tot));
      $('#balance').text(money(Math.max(0, tot - Number($('#paid').val()||0))));
    }
    $('#discount,#tax,#paid').on('input', totals);
    $('#updateSale').on('click', function(){
      const id = $(this).data('id');
      if(lines.length===0) return iziToast.error({title:'Error',message:'Add at least one line item',position:'bottomRight'});
      if(lines.some(it=>!it.product_id||Number(it.qty)<=0)) return iziToast.error({title:'Error',message:'Each line needs a product and qty>0',position:'bottomRight'});
      api.put('/api/sales/'+id, {
        customer_id: $('#customerSelect').val() || null,
        customer_name: $('#customerName').val(),
        items: lines, discount: Number($('#discount').val()||0), tax: Number($('#tax').val()||0), paid: Number($('#paid').val()||0), notes: $('#notes').val(),
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
        if (!rows.length) return $('#rows').html('<tr><td colspan="8" class="text-center text-muted py-3">No purchases yet.</td></tr>');
        $('#rows').html(rows.map(r=>`<tr>
          <td class="text-num small">${esc(r.ref_no)}</td><td>${esc(r.supplier_name)}</td>
          <td class="small text-muted">${r.purchase_date}</td><td class="text-muted small">${r.item_count||0}</td>
          <td class="text-end text-num">${money(r.subtotal)}</td><td class="text-end text-num">${money(r.tax)}</td>
          <td class="text-end text-num fw-semibold">${money(r.total)}</td>
          <td class="text-end"><button class="btn btn-sm btn-link p-1 text-danger del" data-id="${r.id}" data-ref="${esc(r.ref_no)}"><i class="bi bi-trash"></i></button></td>
        </tr>`).join(''));
        $('#rows .del').on('click', function(){
          const id=$(this).data('id'), ref=$(this).data('ref');
          confirmAction(`Delete purchase ${ref}?`, ()=> api.del('/api/purchases/'+id).then(()=>{ iziToast.success({title:'Deleted',position:'bottomRight'}); load(); }));
        });
      });
    };

    $('#newBtn').on('click', ()=>{
      lines=[]; $('#notes').val(''); $('#pTax').val(0); $('#supplierName').val(''); $('#supplierSel').val('');
      Promise.all([api.get('/api/products'), api.get('/api/suppliers')]).then(([p,s])=>{
        products=p; suppliers=s;
        $('#supplierSel').html('<option value="">— pick a supplier —</option>'+suppliers.map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''));
        renderLines(); modal.show();
      });
    });
    $('#supplierSel').on('change', function(){ const s=suppliers.find(x=>String(x.id)===this.value); $('#supplierName').val(s?s.name:''); });
    $('#addLine').on('click', ()=>{ lines.push({product_id:'',sku:'',name:'',qty:1,price:0,total:0}); renderLines(); });

    function renderLines(){
      $('#lines').html(lines.length===0
        ? '<tr><td colspan="5" class="text-center text-muted small py-2">No items.</td></tr>'
        : lines.map((it,i)=>`<tr>
            <td><select class="form-select form-select-sm pl-prod" data-i="${i}"><option value="">— pick product —</option>${products.map(p=>`<option value="${p.id}" ${String(p.id)===String(it.product_id)?'selected':''}>${esc(p.sku)} — ${esc(p.name)}</option>`).join('')}</select></td>
            <td class="text-end"><input class="form-control form-control-sm text-end pl-qty" data-i="${i}" type="number" step="any" style="width:80px" value="${it.qty}"></td>
            <td class="text-end"><input class="form-control form-control-sm text-end pl-price" data-i="${i}" type="number" step="any" style="width:100px" value="${it.price}"></td>
            <td class="text-end text-num">${money(it.total)}</td>
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
      if(lines.length===0) return iziToast.error({title:'Error',message:'Add at least one item',position:'bottomRight'});
      api.post('/api/purchases', {
        supplier_id: $('#supplierSel').val() || null,
        supplier_name: $('#supplierName').val(),
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

  return { dashboard, products, party, sales, saleNew, saleView, saleEdit, purchases, reports, users };
})();
