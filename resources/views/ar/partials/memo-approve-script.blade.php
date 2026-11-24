<script>
    function approveMemoForm(data) {
        const r2 = n => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

        return {
            type: data.type,
            taxOption: data.taxOption,
            vatOptions: data.vatOptions || [],
            lineData: data.lineData || {},

            taxBucket(code) {
                if (!code) return null;
                const c = String(code).toUpperCase();
                if (c === 'ZERO-RATED' || c === 'ZERO_RATED') return 'zero_rated';
                if (c === 'VAT-EXEMPT' || c === 'EXEMPT') return 'exempt';
                if (c === 'NON-VAT' || c === 'NON_VAT') return 'non_vat';
                return 'vatable';
            },

            cls(lineId) {
                const l = this.lineData[lineId];
                if (!l || !l.vat) return null;
                if (String(l.vat).startsWith('tax:')) {
                    const o = this.vatOptions.find(v => v.value === l.vat);
                    const code = o ? (o.code || (o.label || '').split('—')[0].trim()) : 'VAT';
                    return this.taxBucket(code);
                }
                return l.vat;
            },

            taxable(lineId) {
                const l = this.lineData[lineId];
                if (!l) return 0;
                return r2((Number(l.quantity) || 0) * (Number(l.unit_price) || 0) - (Number(l.discount_amount) || 0));
            },

            taxAmount(lineId) {
                if (this.cls(lineId) !== 'vatable' || this.taxOption === 0) return 0;
                const l = this.lineData[lineId];
                const o = this.vatOptions.find(v => v.value === l.vat);
                if (!o) return 0;
                if (o.type === 'FixedAmount') return this.taxOption === 2 ? r2(o.fixed) : 0;
                const rate = Number(o.rate) || 0;
                if (this.taxOption === 1) return r2(this.taxable(lineId) * rate / (100 + rate));
                return r2(this.taxable(lineId) * rate / 100);
            },

            lineTotal(lineId) {
                if (this.taxOption === 1) return this.taxable(lineId);
                return r2(this.taxable(lineId) + this.taxAmount(lineId));
            },

            get taxableTotal() {
                return r2(Object.keys(this.lineData).reduce((s, id) => s + this.taxable(id), 0));
            },

            get vatAmount() {
                return r2(Object.keys(this.lineData).reduce((s, id) => s + this.taxAmount(id), 0));
            },

            get total() {
                return r2(Object.keys(this.lineData).reduce((s, id) => s + this.lineTotal(id), 0));
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
