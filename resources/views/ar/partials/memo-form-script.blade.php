<script>
    const makeMemoKey = () =>
        (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') ?
        crypto.randomUUID() :
        'm_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2);

    function memoForm(data) {
        const r2 = n => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

        return {
            ...data,

            init() {
                this.lines = (this.lines || []).map(l => ({
                    ...l,
                    _key: l._key || makeMemoKey(),
                }));
                if (!this.lines.length) this.addLine();
            },

            filteredInvoices() {
                if (!this.customerId) return this.invoices || [];
                return (this.invoices || []).filter(i => String(i.customer_id) === String(this.customerId));
            },

            onCustomerChange() {
                const c = (this.customers || []).find(x => String(x.id) === String(this.customerId));
                if (c && c.currency_id) this.currencyId = String(c.currency_id);
                if (this.invoiceId) {
                    const valid = this.filteredInvoices().some(i => String(i.id) === String(this.invoiceId));
                    if (!valid) this.invoiceId = '';
                }
            },

            onInvoiceChange() {
                const inv = (this.invoices || []).find(i => String(i.id) === String(this.invoiceId));
                if (!inv) return;
                if (inv.customer_id) this.customerId = String(inv.customer_id);
                if (inv.tax_option !== undefined && inv.tax_option !== null) {
                    this.taxOption = Number(inv.tax_option);
                }
            },

            addLine() {
                const firstAccount = (this.accounts && this.accounts[0]) ? String(this.accounts[0].id) : '';
                const firstVat = (this.vatOptions && this.vatOptions[0]) ? this.vatOptions[0].value : 'exempt';
                this.lines.push({
                    _key: makeMemoKey(),
                    description: '',
                    gl_account_id: firstAccount,
                    quantity: 1,
                    unit_price: 0,
                    discount_amount: 0,
                    vat: firstVat,
                });
            },

            removeLine(i) {
                if (this.lines.length > 1) this.lines.splice(i, 1);
            },

            taxBucket(code) {
                if (!code) return null;
                const c = String(code).toUpperCase();
                if (c === 'ZERO-RATED' || c === 'ZERO_RATED') return 'zero_rated';
                if (c === 'VAT-EXEMPT' || c === 'EXEMPT') return 'exempt';
                if (c === 'NON-VAT' || c === 'NON_VAT') return 'non_vat';
                return 'vatable';
            },

            cls(l) {
                if (!l.vat) return null;
                if (String(l.vat).startsWith('tax:')) {
                    const o = (this.vatOptions || []).find(v => v.value === l.vat);
                    const code = o ? (o.code || (o.label || '').split('—')[0].trim()) : 'VAT';
                    return this.taxBucket(code);
                }
                return l.vat;
            },

            taxable(l) {
                return r2((Number(l.quantity) || 0) * (Number(l.unit_price) || 0) - (Number(l.discount_amount) || 0));
            },

            taxAmount(l) {
                if (this.cls(l) !== 'vatable' || this.taxOption === 0) return 0;
                const o = (this.vatOptions || []).find(v => v.value === l.vat);
                if (!o) return 0;
                if (o.type === 'FixedAmount') return this.taxOption === 2 ? r2(o.fixed) : 0;
                const rate = Number(o.rate) || 0;
                if (this.taxOption === 1) return r2(this.taxable(l) * rate / (100 + rate));
                return r2(this.taxable(l) * rate / 100);
            },

            lineTotal(l) {
                if (this.taxOption === 1) return this.taxable(l);
                return r2(this.taxable(l) + this.taxAmount(l));
            },

            get taxableTotal() {
                return r2(this.lines.reduce((s, l) => s + this.taxable(l), 0));
            },

            get vatAmount() {
                return r2(this.lines.reduce((s, l) => s + this.taxAmount(l), 0));
            },

            get total() {
                return r2(this.lines.reduce((s, l) => s + this.lineTotal(l), 0));
            },

            fmt(n) {
                return new Intl.NumberFormat('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(n || 0);
            },
        };
    }
</script>
