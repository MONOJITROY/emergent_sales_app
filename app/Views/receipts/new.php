<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <a href="<?= View::e($_baseUrl) ?>/receipts" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back</a>
  <h5 class="mb-0 fw-bold">New Receipt</h5>
  <div></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card sf-card"><div class="card-body">
      <div class="text-uppercase small fw-semibold text-secondary mb-2" style="font-size:.7rem;letter-spacing:.06em">Customer</div>
      <div class="row g-2 mb-1">
        <div class="col-md-6">
          <select id="customerSelect" class="form-select form-select-sm" data-testid="customer-select">
            <option value="">— Select Customer —</option>
          </select>
        </div>
        <div class="col-md-6">
          <!-- <div class="text-uppercase small fw-semibold text-secondary mb-2" style="font-size:.7rem;letter-spacing:.06em">Receipt Type</div> -->
          <div class="d-flex gap-3 mb-1 mt-1">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="receiptType" id="typePartial" value="partial" checked>
              <label class="form-check-label" for="typePartial">Partial Receipt</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="receiptType" id="typeLumpsum" value="lumpsum">
              <label class="form-check-label" for="typeLumpsum">Lumpsum Receipt</label>
            </div>
          </div>
        </div>
      </div>


      <!-- Partial Receipt Section -->
      <div id="partialSection">
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label small">Select Invoice</label>
            <select id="invoiceSelect" class="form-select form-select-sm" disabled>
              <option value="">— Select customer first —</option>
            </select>
          </div>
        </div>
        <div id="invoiceInfo" class="mb-3" style="display:none">
          <div class="row g-2">
            <div class="col-md-3"><div class="kpi"><div class="label">Invoice Total</div><div class="value text-num" id="invTotal">0.00</div></div></div>
            <div class="col-md-3"><div class="kpi"><div class="label">Already Paid</div><div class="value text-num" id="invPaid">0.00</div></div></div>
            <div class="col-md-3"><div class="kpi"><div class="label">Remaining</div><div class="value text-num text-orange" id="invRemaining">0.00</div></div></div>
            <div class="col-md-3">
              <label class="form-label small">Receipt Amount</label>
              <input id="partialAmount" type="number" step="any" min="0" class="form-control form-control-sm text-num" placeholder="0.00" data-testid="partial-amount">
            </div>
          </div>
        </div>
      </div>

      <!-- Lumpsum Receipt Section -->
      <div id="lumpsumSection" style="display:none">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <label class="form-label small">Total Received Amount</label>
            <input id="lumpsumAmount" type="number" step="any" min="0" class="form-control form-control-sm text-num" placeholder="0.00" data-testid="lumpsum-amount">
          </div>
          <div class="col-md-5">
            <label class="form-label small">Allocation Mode</label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="allocMode" id="allocFifo" value="fifo" checked>
                <label class="form-check-label text-sm" for="allocFifo">Auto-Select (FIFO)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="allocMode" id="allocManual" value="manual">
                <label class="form-check-label text-sm" for="allocManual">User-Defined</label>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kpi mt-1"><div class="label">Surplus / Unallocated</div><div class="value text-num" id="surplusAmt">0.00</div></div>
          </div>
        </div>

        <div class="table-responsive"><table class="table table-sm sf-table">
          <thead><tr><th style="width:40px"></th><th>Invoice No</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th class="text-end">Allocate</th></tr></thead>
          <tbody id="allocRows"><tr><td colspan="7" class="text-center text-muted py-3">Select customer and enter amount</td></tr></tbody>
        </table></div>
      </div>
    </div></div>
  </div>

  <div class="col-lg-4">
    <div class="card sf-card"><div class="card-body">
      <div class="text-uppercase small fw-semibold text-secondary mb-2" style="font-size:.7rem;letter-spacing:.06em">Payment Details</div>

      <div class="mb-2">
        <label class="form-label small">Mode of Receipt</label>
        <div class="d-flex gap-3">
          <div class="form-check"><input class="form-check-input" type="radio" name="payMode" id="modeCash" value="cash" checked><label class="form-check-label" for="modeCash">Cash</label></div>
          <div class="form-check"><input class="form-check-input" type="radio" name="payMode" id="modeCheque" value="cheque"><label class="form-check-label" for="modeCheque">Cheque</label></div>
          <div class="form-check"><input class="form-check-input" type="radio" name="payMode" id="modeUpi" value="upi"><label class="form-check-label" for="modeUpi">UPI</label></div>
          <div class="form-check"><input class="form-check-input" type="radio" name="payMode" id="modeTransfer" value="transfer"><label class="form-check-label" for="modeTransfer">Transfer</label></div>
        </div>
      </div>

      <div id="refFields" style="display:none">
        <div class="mb-2">
          <label class="form-label small">Reference / UTR / Cheque No</label>
          <input id="referenceNo" type="text" class="form-control form-control-sm" placeholder="Transaction reference">
        </div>
        <div class="mb-2">
          <label class="form-label small">Bank Name</label>
          <input id="bankName" type="text" class="form-control form-control-sm" placeholder="Bank name">
        </div>
      </div>

      <div class="mb-2">
        <label class="form-label small">Transaction Date</label>
        <input id="txDate" type="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label small">Notes</label>
        <textarea id="txNotes" class="form-control form-control-sm" rows="2" placeholder="Optional notes"></textarea>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-muted">Total to Receive</span>
        <span class="text-num fw-bold text-orange" id="totalReceiveDisplay">0.00</span>
      </div>

      <button class="btn sf-btn-primary w-100" id="submitReceipt" disabled data-testid="submit-receipt">
        <i class="bi bi-check-lg me-1"></i>Submit Receipt
      </button>
    </div></div>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.receiptNew();</script>'; ?>
