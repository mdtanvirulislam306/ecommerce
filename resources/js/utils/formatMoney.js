export const formatMoney = (amount, currency = 'BDT', { decimals = 2 } = {}) => {
    const value = Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });

    return currency === 'BDT' ? `৳${value}` : `${currency} ${value}`;
};
