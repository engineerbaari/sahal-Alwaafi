document.addEventListener('DOMContentLoaded', async () => {
  const page = location.pathname.split('/').pop();
  if (!['income.php', 'expenses.php', 'invoices.php'].includes(page)) return;
  let banks = [];
  try { banks = await fetch('bank_options.php', {credentials: 'same-origin'}).then(r => r.ok ? r.json() : []); } catch (_) { return; }
  if (!banks.length) {
    if (page === 'invoices.php') document.querySelectorAll('form').forEach(form => {
      const method = form.querySelector('select[name="method"]');
      if (!method || form.querySelector('.bank-account-link')) return;
      method.insertAdjacentHTML('afterend', '<a class="btn btn-sm btn-outline-primary mb-3 bank-account-link" href="bank_accounts.php" target="_blank"><i class="bi bi-bank"></i> Create Bank Account</a>');
    });
    return;
  }
  document.querySelectorAll('form').forEach(form => {
    const amount = form.querySelector('input[name="amount"]');
    if (!amount || form.querySelector('[name="bank_account_id"]')) return;
    if (page === 'invoices.php') {
      const method = form.querySelector('select[name="method"]');
      if (!method) return;
      method.previousElementSibling && (method.previousElementSibling.textContent = 'Bank Account');
      method.innerHTML = banks.map(b => `<option value="Bank Transfer" data-bank-id="${b.id}">${b.name} · ${b.bank_name} · ${b.account_number}</option>`).join('');
      const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'bank_account_id'; hidden.value = banks[0].id;
      method.after(hidden);
      hidden.insertAdjacentHTML('afterend', '<a class="btn btn-sm btn-outline-secondary mb-3 bank-account-link" href="bank_accounts.php" target="_blank"><i class="bi bi-bank"></i> Manage Bank Accounts</a>');
      method.addEventListener('change', () => hidden.value = method.selectedOptions[0].dataset.bankId);
      return;
    }
    const label = document.createElement('label'); label.className = 'form-label'; label.textContent = 'Bank Account';
    const select = document.createElement('select'); select.name = 'bank_account_id'; select.className = 'form-select mb-3'; select.required = true;
    select.innerHTML = '<option value="">Select bank account</option>' + banks.map(b => `<option value="${b.id}">${b.name} · ${b.bank_name} · ${b.account_number}</option>`).join('');
    amount.before(label); amount.before(select);
  });
});
