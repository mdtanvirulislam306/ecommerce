/**
 * NexCore admin navigation — primary sidebar + contextual sub-nav per module.
 * Paths map to Admin/ModulePageController ({module}/{page?}/{sub?}).
 */

const path = (module, page = 'overview', sub = null) => {
    if (sub) return `/admin/${module}/${page}/${sub}`;
    if (page) return `/admin/${module}/${page}`;

    return `/admin/${module}`;
};

const item = (label, module, page, sub = null) => ({
    label,
    path: path(module, page, sub),
});

const group = (label, children) => ({ label, children });

/** @type {import('./types').NavModule[]} */
export const primaryModules = [
    {
        key: 'crm',
        label: 'CRM',
        icon: 'crm',
        defaultPath: '/admin/crm/overview',
        children: [
            { label: 'Overview', path: '/admin/crm/overview' },
            group('Leads', [
                { label: 'Leads', path: '/admin/crm/leads/all' },
                { label: 'Lead Sources', path: '/admin/crm/leads/sources' },
                { label: 'Add Lead', path: '/admin/crm/leads/create' },
            ]),
            group('Customers', [
                { label: 'All Customers', path: '/admin/crm/customers/all' },
                { label: 'Add Customer', path: '/admin/crm/customers/create' },
                { label: 'Customer Groups', path: '/admin/commerce/pricing/customer-groups' },
                { label: 'Customer Segments', path: '/admin/crm/customers/segments' },
            ]),
            group('Activities', [
                { label: 'All Activities', path: '/admin/crm/activities/all' },
                { label: 'Follow-ups', path: '/admin/crm/activities/follow-ups' },
                { label: 'Calendar', path: '/admin/crm/activities/calendar' },
            ]),
            { label: 'CRM Reports', path: '/admin/crm/reports' },
        ],
    },
    {
        key: 'products',
        label: 'Products & Catalog',
        icon: 'products',
        defaultPath: path('products', 'overview'),
        children: [
            item('Overview', 'products', 'overview'),
            group('Products', [
                item('All Products', 'products', 'all-products'),
                item('Add Product', 'products', 'create'),
                item('Import / Export', 'products', 'import-export'),
            ]),
            item('Product Families', 'products', 'families'),
            item('Variants', 'products', 'variants'),
            item('Attributes', 'products', 'attributes'),
            item('Categories', 'products', 'categories'),
            item('Brands', 'products', 'brands'),
            item('Collections', 'products', 'collections'),
            group('Units', [
                item('Units', 'products', 'units'),
                item('Unit Conversions', 'products', 'units', 'conversions'),
            ]),
            item('Product Approval', 'products', 'approval'),
            item('Product Settings', 'products', 'settings'),
        ],
    },
    {
        key: 'inventory',
        label: 'Inventory',
        icon: 'inventory',
        defaultPath: path('inventory', 'overview'),
        children: [
            item('Overview', 'inventory', 'overview'),
            item('Warehouses', 'inventory', 'warehouses'),
            item('Stock Overview', 'inventory', 'stock-overview'),
            item('Stock Movement', 'inventory', 'stock-movement'),
            item('Stock Adjustment', 'inventory', 'stock-adjustment'),
            item('Stock Transfer', 'inventory', 'stock-transfer'),
            item('Low Stock', 'inventory', 'low-stock'),
            item('Out of Stock', 'inventory', 'out-of-stock'),
            item('Batches', 'inventory', 'batches'),
            item('Serial Numbers', 'inventory', 'serial-numbers'),
            item('Stock Valuation', 'inventory', 'stock-valuation'),
            item('Inventory Reports', 'inventory', 'reports'),
        ],
    },
    {
        key: 'purchase',
        label: 'Purchase',
        icon: 'purchase',
        defaultPath: '/admin/purchase/overview',
        children: [
            { label: 'Overview', path: '/admin/purchase/overview' },
            group('Suppliers', [
                { label: 'All Suppliers', path: '/admin/purchase/suppliers/all' },
                { label: 'Supplier Groups', path: '/admin/purchase/suppliers/groups' },
            ]),
            group('Purchase Orders', [
                { label: 'All Purchase Orders', path: '/admin/purchase/orders/all' },
                { label: 'Create PO', path: '/admin/purchase/orders/create' },
            ]),
            { label: 'Purchase Receive', path: '/admin/purchase/receive' },
            { label: 'Purchase Returns', path: '/admin/purchase/returns' },
            { label: 'Supplier Payments', path: '/admin/purchase/payments' },
            { label: 'Purchase Reports', path: '/admin/purchase/reports' },
        ],
    },
    {
        key: 'sales',
        label: 'Sales',
        icon: 'sales',
        defaultPath: path('sales', 'overview'),
        children: [
            item('Overview', 'sales', 'overview'),
            group('Quotations', [
                item('All Quotations', 'sales', 'quotations', 'all'),
                item('Create Quotation', 'sales', 'quotations', 'create'),
            ]),
            group('Orders', [
                item('All Orders', 'sales', 'orders', 'all'),
                item('Create Order', 'sales', 'orders', 'create'),
            ]),
            group('Invoices', [
                item('All Invoices', 'sales', 'invoices', 'all'),
                item('Create Invoice', 'sales', 'invoices', 'create'),
            ]),
            item('Payments', 'sales', 'payments'),
            item('Sales Returns', 'sales', 'returns'),
            item('Credit Notes', 'sales', 'credit-notes'),
            item('Sales Reports', 'sales', 'reports'),
        ],
    },
    {
        key: 'pos',
        label: 'POS',
        icon: 'pos',
        defaultPath: '/admin/pos/registers',
        children: [
            { label: 'Terminal', path: '/admin/pos/terminal' },
            { label: 'Registers', path: '/admin/pos/registers' },
            { label: 'Open Sessions', path: '/admin/pos/open-sessions' },
            { label: 'Session History', path: '/admin/pos/session-history' },
            { label: 'Cash Management', path: '/admin/pos/cash-management' },
            { label: 'POS Orders', path: '/admin/pos/orders' },
            { label: 'POS Returns', path: '/admin/pos/returns' },
            { label: 'POS Reports', path: '/admin/pos/reports' },
        ],
    },
    {
        key: 'ecommerce',
        label: 'Ecommerce',
        icon: 'ecommerce',
        defaultPath: '/admin/ecommerce/overview',
        children: [
            { label: 'Overview', path: '/admin/ecommerce/overview' },
            group('Store', [
                { label: 'Store Dashboard', path: '/admin/ecommerce/store/dashboard' },
                { label: 'Store Settings', path: '/admin/ecommerce/store/settings' },
                { label: 'Domains', path: '/admin/ecommerce/store/domains' },
                { label: 'SEO', path: '/admin/ecommerce/store/seo' },
            ]),
            group('Theme', [
                { label: 'Theme Library', path: '/admin/ecommerce/theme/library' },
                { label: 'Installed Themes', path: '/admin/ecommerce/theme/installed' },
                { label: 'Customize', path: '/admin/ecommerce/theme/customize' },
                { label: 'Publish', path: '/admin/ecommerce/theme/publish' },
            ]),
            group('Pages', [
                { label: 'All Pages', path: '/admin/ecommerce/pages/all' },
                { label: 'Page Builder', path: '/admin/ecommerce/pages/builder' },
                { label: 'Menus & Navigation', path: '/admin/ecommerce/pages/menus' },
            ]),
            { label: 'Online Products', path: '/admin/ecommerce/online-products' },
            { label: 'Online Orders', path: '/admin/ecommerce/online-orders' },
            { label: 'Open Shop', path: '/shop' },
            { label: 'Product Reviews', path: '/admin/ecommerce/reviews' },
            { label: 'Coupons', path: '/admin/ecommerce/coupons' },
            { label: 'Promotions', path: '/admin/ecommerce/promotions' },
            { label: 'Shipping', path: '/admin/ecommerce/shipping' },
            { label: 'Payments & Checkout', path: '/admin/ecommerce/checkout' },
            { label: 'Order Notifications', path: '/admin/ecommerce/notifications' },
            { label: 'Ecommerce Reports', path: '/admin/ecommerce/reports' },
        ],
    },
    {
        key: 'accounting',
        label: 'Accounting',
        icon: 'accounting',
        defaultPath: '/admin/accounting/overview',
        children: [
            { label: 'Overview', path: '/admin/accounting/overview' },
            group('Chart of Accounts', [
                { label: 'Account Tree', path: '/admin/accounting/accounts/tree' },
                { label: 'Assets', path: '/admin/accounting/accounts/assets' },
                { label: 'Liabilities', path: '/admin/accounting/accounts/liabilities' },
                { label: 'Equity', path: '/admin/accounting/accounts/equity' },
                { label: 'Income', path: '/admin/accounting/accounts/income' },
                { label: 'Expenses', path: '/admin/accounting/accounts/expenses' },
            ]),
            { label: 'Journal Entries', path: '/admin/accounting/journal-entries' },
            { label: 'New Journal', path: '/admin/accounting/journal-entries/create' },
            { label: 'General Ledger', path: '/admin/accounting/general-ledger' },
            { label: 'Cashbook', path: '/admin/accounting/cashbook' },
            { label: 'Receivables', path: '/admin/accounting/receivables' },
            { label: 'Payables', path: '/admin/accounting/payables' },
            { label: 'Expenses', path: '/admin/accounting/expenses' },
            { label: 'Income', path: '/admin/accounting/income' },
            { label: 'Bank Accounts', path: '/admin/accounting/bank-accounts' },
            { label: 'Bank Transactions', path: '/admin/accounting/bank-transactions' },
            { label: 'Bank Reconciliation', path: '/admin/accounting/bank-reconciliation' },
            { label: 'Cost Centers', path: '/admin/accounting/cost-centers' },
            { label: 'Profit Centers', path: '/admin/accounting/profit-centers' },
            { label: 'Budgets', path: '/admin/accounting/budgets' },
            group('Fixed Assets', [
                { label: 'Assets', path: '/admin/accounting/fixed-assets/assets' },
                { label: 'Categories', path: '/admin/accounting/fixed-assets/categories' },
                { label: 'Depreciation', path: '/admin/accounting/fixed-assets/depreciation' },
            ]),
            { label: 'Fiscal Years', path: '/admin/accounting/fiscal-years' },
            { label: 'Financial Closing', path: '/admin/accounting/financial-closing' },
            group('Financial Reports', [
                { label: 'Trial Balance', path: '/admin/accounting/reports/trial-balance' },
                { label: 'Profit & Loss', path: '/admin/accounting/reports/profit-loss' },
                { label: 'Balance Sheet', path: '/admin/accounting/reports/balance-sheet' },
                { label: 'Cash Flow', path: '/admin/accounting/reports/cash-flow' },
                { label: 'General Ledger Report', path: '/admin/accounting/reports/general-ledger' },
            ]),
        ],
    },
    {
        key: 'hrm',
        label: 'HRM',
        icon: 'hrm',
        defaultPath: path('hrm', 'overview'),
        children: [
            { label: 'Overview', path: '/admin/hrm/overview' },
            { label: 'Employees', path: '/admin/hrm/employees' },
            { label: 'Departments', path: '/admin/hrm/departments' },
            { label: 'Designations', path: '/admin/hrm/designations' },
            { label: 'Attendance', path: '/admin/hrm/attendance' },
            { label: 'Leave Management', path: '/admin/hrm/leave' },
            { label: 'Payroll', path: '/admin/hrm/payroll' },
            { label: 'Salary Structure', path: '/admin/hrm/salary-structure' },
            { label: 'HR Reports', path: '/admin/hrm/reports' },
        ],
    },
    {
        key: 'reports',
        label: 'Reports',
        icon: 'reports',
        defaultPath: '/admin/reports/overview',
        children: [
            { label: 'Overview', path: '/admin/reports/overview' },
            { label: 'Sales', path: '/admin/reports/sales' },
            { label: 'Purchase', path: '/admin/reports/purchase' },
            { label: 'Inventory', path: '/admin/reports/inventory' },
            { label: 'CRM', path: '/admin/reports/crm' },
            { label: 'Ecommerce', path: '/admin/reports/ecommerce' },
            { label: 'POS', path: '/admin/reports/pos' },
            { label: 'Accounting', path: '/admin/reports/accounting' },
            { label: 'HR', path: '/admin/reports/hr' },
            { label: 'Custom Reports', path: '/admin/reports/custom' },
        ],
    },
];

/** @type {import('./types').NavModule[]} */
export const moreModules = [
    {
        key: 'marketing',
        label: 'Marketing',
        icon: 'marketing',
        defaultPath: '/admin/marketing/overview',
        children: [
            { label: 'Overview', path: '/admin/marketing/overview' },
            group('Stories', [
                { label: 'All Stories', path: '/admin/marketing/stories' },
                { label: 'Create Story', path: '/admin/marketing/stories/create' },
            ]),
            { label: 'Campaigns', path: '/admin/marketing/campaigns' },
            { label: 'Email Marketing', path: '/admin/marketing/email' },
            { label: 'SMS Marketing', path: '/admin/marketing/sms' },
            { label: 'WhatsApp Marketing', path: '/admin/marketing/whatsapp' },
            { label: 'Push Notifications', path: '/admin/marketing/push' },
            { label: 'Customer Segments', path: '/admin/marketing/segments' },
            { label: 'Promotions', path: '/admin/marketing/promotions' },
            { label: 'Coupons', path: '/admin/marketing/coupons' },
            { label: 'Loyalty', path: '/admin/marketing/loyalty' },
            { label: 'Referral', path: '/admin/marketing/referrals' },
            { label: 'Marketing Reports', path: '/admin/marketing/reports' },
        ],
    },
    {
        key: 'commerce',
        label: 'Commerce',
        icon: 'commerce',
        defaultPath: '/admin/commerce/overview',
        children: [
            { label: 'Overview', path: '/admin/commerce/overview' },
            group('Pricing', [
                { label: 'Price Lists', path: '/admin/commerce/pricing/price-lists' },
                { label: 'Customer Group Pricing', path: '/admin/commerce/pricing/customer-groups' },
                { label: 'Quantity / Tier Pricing', path: '/admin/commerce/pricing/quantity' },
                { label: 'Price History', path: '/admin/commerce/pricing/history' },
            ]),
            group('Promotions', [
                { label: 'All Promotions', path: '/admin/commerce/promotions' },
                { label: 'Discount Rules', path: '/admin/commerce/promotions/discount-rules' },
                { label: 'Buy X Get Y', path: '/admin/commerce/promotions/buy-x-get-y' },
                { label: 'Free Shipping', path: '/admin/commerce/promotions/free-shipping' },
            ]),
            { label: 'Coupons', path: '/admin/commerce/coupons' },
            group('Loyalty', [
                { label: 'Loyalty Program', path: '/admin/commerce/loyalty/program' },
                { label: 'Points', path: '/admin/commerce/loyalty/points' },
                { label: 'Transactions', path: '/admin/commerce/loyalty/transactions' },
            ]),
            group('Wallet', [
                { label: 'Customer Wallets', path: '/admin/commerce/wallet/wallets' },
                { label: 'Transactions', path: '/admin/commerce/wallet/transactions' },
            ]),
            { label: 'Referral', path: '/admin/commerce/referrals' },
            { label: 'Commission', path: '/admin/commerce/commissions' },
            group('Shipping / Courier', [
                { label: 'Couriers', path: '/admin/commerce/shipping/couriers' },
                { label: 'Shipments', path: '/admin/commerce/shipping/shipments' },
                { label: 'Tracking', path: '/admin/commerce/shipping/tracking' },
            ]),
        ],
    },
    {
        key: 'support',
        label: 'Support',
        icon: 'support',
        defaultPath: path('support', 'overview'),
        children: [
            { label: 'Overview', path: '/admin/support/overview' },
            { label: 'Tickets', path: '/admin/support/tickets' },
            { label: 'My Tickets', path: '/admin/support/my-tickets' },
            { label: 'Unassigned Tickets', path: '/admin/support/unassigned' },
            { label: 'Ticket Categories', path: '/admin/support/categories' },
            { label: 'Canned Responses', path: '/admin/support/canned-responses' },
            { label: 'Support Reports', path: '/admin/support/reports' },
        ],
    },
    {
        key: 'workflow',
        label: 'Workflow',
        icon: 'workflow',
        defaultPath: '/admin/workflow/overview',
        children: [
            { label: 'Overview', path: '/admin/workflow/overview' },
            { label: 'Workflows', path: '/admin/workflow/workflows' },
            { label: 'Approval Requests', path: '/admin/workflow/approval-requests' },
            { label: 'Approval Policies', path: '/admin/workflow/approval-policies' },
            { label: 'Automation', path: '/admin/workflow/automation' },
            { label: 'Business Rules', path: '/admin/workflow/business-rules' },
            { label: 'Scheduled Tasks', path: '/admin/workflow/scheduled-tasks' },
            { label: 'Automation Logs', path: '/admin/workflow/logs' },
        ],
    },
    {
        key: 'tasks',
        label: 'Tasks',
        icon: 'tasks',
        defaultPath: '/admin/tasks/my-tasks',
        children: [
            { label: 'My Tasks', path: '/admin/tasks/my-tasks' },
            { label: 'All Tasks', path: '/admin/tasks/all-tasks' },
            { label: 'Calendar', path: '/admin/tasks/calendar' },
            { label: 'Activity Timeline', path: '/admin/tasks/timeline' },
            { label: 'Create Task', path: '/admin/tasks/create' },
        ],
    },
    {
        key: 'notifications',
        label: 'Notifications',
        icon: 'notifications',
        defaultPath: '/admin/notifications/center',
        children: [
            { label: 'Notification Center', path: '/admin/notifications/center' },
            { label: 'Templates', path: '/admin/notifications/templates' },
            { label: 'Email', path: '/admin/notifications/email' },
            { label: 'SMS', path: '/admin/notifications/sms' },
            { label: 'WhatsApp', path: '/admin/notifications/whatsapp' },
            { label: 'Push', path: '/admin/notifications/push' },
        ],
    },
    {
        key: 'files',
        label: 'Files & Media',
        icon: 'files',
        defaultPath: '/admin/files/media-library',
        children: [
            { label: 'Media Library', path: '/admin/files/media-library' },
            { label: 'Documents', path: '/admin/files/documents' },
            { label: 'Attachments', path: '/admin/files/attachments' },
            { label: 'Storage', path: '/admin/files/storage' },
        ],
    },
];

export const settingsModule = {
    key: 'settings',
    label: 'Settings',
    icon: 'settings',
    defaultPath: '/admin/settings/general',
    children: [
        { label: 'General', path: '/admin/settings/general' },
        { label: "This shop's plan", path: '/admin/billing/plans' },
        { label: 'Shop complexity', path: '/admin/billing/settings' },
        { label: 'Platform console', path: '/platform' },
        group('Business', [
            { label: 'Company', path: '/admin/settings/business/company' },
            { label: 'Branches', path: '/admin/settings/business/branches' },
            { label: 'Regions', path: '/admin/settings/business/regions' },
            { label: 'Areas', path: '/admin/settings/business/areas' },
        ]),
        { label: 'Warehouses', path: '/admin/settings/warehouses' },
        { label: 'Team', path: '/admin/settings/users' },
        { label: 'Roles & Permissions', path: '/admin/settings/roles' },
        { label: 'Modules', path: '/admin/settings/modules' },
        { label: 'Your plan', path: '/admin/settings/subscription' },
        { label: 'Numbering', path: '/admin/settings/numbering' },
        { label: 'Document Templates', path: '/admin/settings/document-templates' },
        { label: 'Tax Settings', path: '/admin/settings/tax' },
        { label: 'Currency', path: '/admin/settings/currency' },
        { label: 'Payment Methods', path: '/admin/settings/payment-methods' },
        { label: 'Shipping Methods', path: '/admin/settings/shipping-methods' },
        { label: 'Integrations', path: '/admin/settings/integrations' },
        { label: 'API & Webhooks', path: '/admin/settings/api' },
        { label: 'Localization', path: '/admin/settings/localization' },
        { label: 'Notifications', path: '/admin/settings/notifications' },
        { label: 'Audit Logs', path: '/admin/settings/audit-logs' },
        { label: 'System Settings', path: '/admin/settings/system' },
    ],
};

/**
 * Entry paths that run the module gate. A locked module renders the Upgrade page.
 *
 * @type {Record<string, string>}
 */
export const moduleGatePaths = {
    catalog: '/admin/products/overview',
    crm: '/admin/crm/overview',
    inventory: '/admin/inventory/overview',
    purchase: '/admin/purchase/overview',
    sales: '/admin/sales/overview',
    pos: '/admin/pos/registers',
    ecommerce: '/admin/ecommerce/overview',
    accounting: '/admin/accounting/overview',
    hrm: '/admin/hrm/overview',
    reports: '/admin/reports/overview',
    marketing: '/admin/marketing/overview',
    commerce: '/admin/commerce/overview',
    support: '/admin/support/overview',
    workflow: '/admin/workflow/overview',
    tasks: '/admin/tasks/my-tasks',
    notifications: '/admin/notifications/center',
    files: '/admin/files/media-library',
};

export function moduleGatePath(code) {
    return moduleGatePaths[code] ?? null;
}

export const allModules = [...primaryModules, ...moreModules, settingsModule];

export function getModuleFromUrl(url) {
    const clean = url.split('?')[0];
    const match = clean.match(/^\/admin\/([^/]+)/);

    if (!match) return null;

    return allModules.find((m) => m.key === match[1]) ?? null;
}

export function isPathActive(currentUrl, itemPath) {
    const current = currentUrl.split('?')[0].replace(/\/$/, '');
    const target = itemPath.replace(/\/$/, '');

    return current === target || current.startsWith(target + '/');
}

export const quickCreateItems = [
    { label: 'Story', path: '/admin/marketing/stories/create' },
    { label: 'Customer', path: '/admin/crm/customers/create' },
    { label: 'Lead', path: '/admin/crm/leads/create' },
    { label: 'Product', path: path('products', 'create') },
    { label: 'Purchase Order', path: '/admin/purchase/orders/create' },
    { label: 'Quotation', path: path('sales', 'quotations', 'create') },
    { label: 'Sales Order', path: path('sales', 'orders', 'create') },
    { label: 'Invoice', path: path('sales', 'invoices', 'create') },
    { label: 'Payment', path: '/admin/sales/payments' },
    { label: 'Expense', path: '/admin/accounting/expenses' },
    { label: 'Stock Adjustment', path: '/admin/inventory/stock-adjustment' },
    { label: 'Stock Transfer', path: '/admin/inventory/stock-transfer/create' },
    { label: 'Task', path: '/admin/tasks/create' },
];

export const searchCategories = [
    'Product',
    'Customer',
    'Lead',
    'Order',
    'Invoice',
    'Purchase',
    'Supplier',
    'Employee',
    'Transaction',
];
