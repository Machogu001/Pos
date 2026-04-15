@php
    $hrmTheme = 'classic';
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('admin_settings') && \Illuminate\Support\Facades\Schema::hasColumn('admin_settings', 'hrm_theme')) {
            $storedTheme = \App\AdminSetting::query()->value('hrm_theme');
            if (in_array($storedTheme, ['classic', 'corporate', 'minimal'], true)) {
                $hrmTheme = $storedTheme;
            }
        }
    } catch (\Throwable $e) {
        $hrmTheme = 'classic';
    }
@endphp

<style>
/* Premium classic HRM theme: loaded only on pages using this partial. */
body.hrm-classic-theme {
    --hrm-ink: #22313f;
    --hrm-navy: #17344c;
    --hrm-navy-soft: #2a5675;
    --hrm-bronze: #ad8350;
    --hrm-bronze-dark: #8d6840;
    --hrm-paper: #fffdf8;
    --hrm-cream: #f4ecdc;
    --hrm-line: #deceb0;
    --hrm-muted: #6f624d;
    --hrm-danger: #8e3429;
    background:
        radial-gradient(1400px 460px at 100% -14%, rgba(173, 131, 80, 0.18), transparent 62%),
        radial-gradient(1200px 360px at 0% 0%, rgba(23, 52, 76, 0.1), transparent 58%),
        linear-gradient(180deg, #f7f3e7 0%, #fdfbf5 100%);
}

body.hrm-classic-theme .content-header {
    position: relative;
    background: linear-gradient(120deg, var(--hrm-navy) 0%, var(--hrm-navy-soft) 62%, #2f647f 100%);
    border: 1px solid #d9c49c;
    border-radius: 14px;
    margin: 12px 15px 0 15px;
    padding: 16px 18px;
    box-shadow: 0 8px 20px rgba(23, 52, 76, 0.18);
    overflow: hidden;
    animation: hrmHeaderFade 260ms ease-out;
}

body.hrm-classic-theme .content-header:after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(100deg, transparent 0%, rgba(255, 255, 255, 0.08) 44%, transparent 88%);
    pointer-events: none;
}

body.hrm-classic-theme .content-header h1,
body.hrm-classic-theme .content-header .tw-text-black {
    color: #fbf7ec !important;
    letter-spacing: 0.35px;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
}

body.hrm-classic-theme .content-header .text-muted,
body.hrm-classic-theme .content-header p {
    color: #e8deca !important;
}

body.hrm-classic-theme .content {
    margin-top: 10px;
}

body.hrm-classic-theme .box {
    border: 1px solid var(--hrm-line);
    border-top: 3px solid var(--hrm-bronze);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 18px rgba(62, 49, 28, 0.08);
    background: var(--hrm-paper);
}

body.hrm-classic-theme .box .box-header {
    background: linear-gradient(180deg, #f8f0de 0%, #efe2c1 100%);
    border-bottom: 1px solid #e2d2b1;
}

body.hrm-classic-theme .box .box-title {
    color: #392d1f;
    font-weight: 700;
}

body.hrm-classic-theme .table>thead>tr>th {
    background: var(--hrm-cream);
    color: #342a1c;
    border-bottom: 1px solid var(--hrm-line);
}

body.hrm-classic-theme .table-striped>tbody>tr:nth-of-type(odd) {
    background-color: #fcf8ee;
}

body.hrm-classic-theme .table-hover>tbody>tr:hover {
    background-color: #f2ebda;
    transition: background-color 160ms ease;
}

body.hrm-classic-theme .btn {
    border-radius: 8px;
    font-weight: 600;
}

body.hrm-classic-theme .btn-primary,
body.hrm-classic-theme .btn-success,
body.hrm-classic-theme .btn-info {
    background: linear-gradient(180deg, var(--hrm-navy-soft) 0%, var(--hrm-navy) 100%);
    border-color: var(--hrm-navy);
    color: #f8f4e7;
}

body.hrm-classic-theme .btn-primary:hover,
body.hrm-classic-theme .btn-success:hover,
body.hrm-classic-theme .btn-info:hover,
body.hrm-classic-theme .btn-primary:focus,
body.hrm-classic-theme .btn-success:focus,
body.hrm-classic-theme .btn-info:focus {
    background: linear-gradient(180deg, #366c90 0%, #1f3f5c 100%);
    border-color: #19354d;
    color: #fff9eb;
    box-shadow: 0 4px 10px rgba(23, 52, 76, 0.24);
}

body.hrm-classic-theme .btn-default,
body.hrm-classic-theme .btn-secondary {
    background: linear-gradient(180deg, #f7efdd 0%, #eee1c4 100%);
    border-color: #d3bf95;
    color: #362d1f;
}

body.hrm-classic-theme .btn-default:hover,
body.hrm-classic-theme .btn-secondary:hover {
    background: linear-gradient(180deg, #f0e4c8 0%, #e4d2ae 100%);
    border-color: #c4ab79;
    color: #2d2519;
}

body.hrm-classic-theme .btn-danger {
    background: linear-gradient(180deg, #a24235 0%, var(--hrm-danger) 100%);
    border-color: #7f2f25;
}

body.hrm-classic-theme .label-primary {
    background-color: var(--hrm-navy-soft);
}

body.hrm-classic-theme .pagination>.active>a,
body.hrm-classic-theme .pagination>.active>span,
body.hrm-classic-theme .pagination>.active>a:hover,
body.hrm-classic-theme .pagination>.active>span:hover,
body.hrm-classic-theme .pagination>.active>a:focus,
body.hrm-classic-theme .pagination>.active>span:focus {
    background-color: var(--hrm-navy);
    border-color: var(--hrm-navy);
}

body.hrm-classic-theme .form-control {
    border-color: #d8c8a6;
    border-radius: 8px;
    box-shadow: none;
}

body.hrm-classic-theme .form-control:focus {
    border-color: var(--hrm-bronze);
    box-shadow: 0 0 0 2px rgba(173, 131, 80, 0.15);
}

body.hrm-classic-theme .text-muted {
    color: var(--hrm-muted);
}

body.hrm-classic-theme .small-box {
    position: relative;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 10px 22px rgba(28, 36, 44, 0.14);
    overflow: hidden;
}

body.hrm-classic-theme .small-box.hrm-kpi:before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(255, 248, 226, 0.42);
}

body.hrm-classic-theme .small-box .inner {
    padding: 14px;
}

body.hrm-classic-theme .small-box .inner h3 {
    color: #fff8ea;
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
}

body.hrm-classic-theme .small-box .inner p {
    color: rgba(255, 250, 236, 0.92);
}

body.hrm-classic-theme .small-box .icon {
    color: rgba(255, 248, 226, 0.26);
}

body.hrm-classic-theme .small-box .small-box-footer {
    color: #fff6e0;
    background: rgba(0, 0, 0, 0.16);
    font-weight: 600;
}

body.hrm-classic-theme .small-box.bg-aqua {
    background: linear-gradient(135deg, #1f5f7d 0%, #2d789b 100%) !important;
}

body.hrm-classic-theme .small-box.bg-green {
    background: linear-gradient(135deg, #2f5b3f 0%, #3f7653 100%) !important;
}

body.hrm-classic-theme .small-box.bg-yellow {
    background: linear-gradient(135deg, #a5792f 0%, #be9350 100%) !important;
}

body.hrm-classic-theme .small-box.bg-red {
    background: linear-gradient(135deg, #873329 0%, #a34739 100%) !important;
}

body.hrm-classic-theme .small-box.bg-navy {
    background: linear-gradient(135deg, #1c3f5d 0%, #2c5d82 100%) !important;
}

body.hrm-classic-theme .small-box.bg-teal {
    background: linear-gradient(135deg, #2d5860 0%, #3d7079 100%) !important;
}

body.hrm-classic-theme .small-box.bg-orange {
    background: linear-gradient(135deg, #a05d2f 0%, #bc7849 100%) !important;
}

body.hrm-classic-theme .small-box.bg-maroon {
    background: linear-gradient(135deg, #5d2734 0%, #7f3648 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-people {
    background: linear-gradient(135deg, #2f587a 0%, #3f789f 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-active {
    background: linear-gradient(135deg, #2f5b3f 0%, #44805a 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-leave {
    background: linear-gradient(135deg, #8b6b2d 0%, #b4904f 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-risk {
    background: linear-gradient(135deg, #7b2e24 0%, #a04334 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-payroll,
body.hrm-classic-theme .small-box.hrm-kpi-finance {
    background: linear-gradient(135deg, #254864 0%, #366c95 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-attendance {
    background: linear-gradient(135deg, #2c5962 0%, #43818c 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-attention {
    background: linear-gradient(135deg, #8f542a 0%, #b57745 100%) !important;
}

body.hrm-classic-theme .small-box.hrm-kpi-structure {
    background: linear-gradient(135deg, #4f2f57 0%, #6f427a 100%) !important;
}

body.hrm-classic-theme .alert {
    border-radius: 10px;
    border-width: 1px;
}

body.hrm-classic-theme .alert-warning {
    background: #fbf3de;
    border-color: #e8d39b;
    color: #5a461f;
}

body.hrm-classic-theme .alert-danger {
    background: #f9e4df;
    border-color: #e4b2a8;
    color: #64231b;
}

body.hrm-classic-theme .box.box-primary { border-top-color: #2f5d7c; }
body.hrm-classic-theme .box.box-success { border-top-color: #3f7653; }
body.hrm-classic-theme .box.box-warning { border-top-color: #b38743; }
body.hrm-classic-theme .box.box-danger { border-top-color: #a34739; }
body.hrm-classic-theme .box.box-info { border-top-color: #2f6f87; }

body.hrm-classic-theme .box-tools .btn.btn-xs {
    border-radius: 7px;
}

body.hrm-classic-theme .box-tools .btn-success.btn-xs {
    background: linear-gradient(180deg, #447c56 0%, #335b41 100%);
    border-color: #2d513a;
}

body.hrm-classic-theme .box-tools .btn-default.btn-xs {
    background: linear-gradient(180deg, #f8efd9 0%, #eee0c0 100%);
    border-color: #d3bf95;
    color: #392f21;
}

body.hrm-classic-theme .form-group label {
    color: #433725;
    font-weight: 700;
}

body.hrm-classic-theme .hrm-kpi-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin: 6px 0 14px;
}

body.hrm-classic-theme .hrm-kpi-legend-title {
    font-size: 12px;
    font-weight: 700;
    color: #4a3b27;
    margin-right: 4px;
}

body.hrm-classic-theme .hrm-kpi-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid #dccaa4;
    border-radius: 999px;
    background: #f8f1de;
    color: #3d3120;
    font-size: 12px;
    line-height: 1;
}

body.hrm-classic-theme .hrm-kpi-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    display: inline-block;
}

body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-people { background: #3f789f; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-active { background: #44805a; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-leave { background: #b4904f; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-risk { background: #a04334; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-payroll { background: #366c95; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-finance { background: #366c95; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-attendance { background: #43818c; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-attention { background: #b57745; }
body.hrm-classic-theme .hrm-kpi-dot.hrm-kpi-structure { background: #6f427a; }

body.hrm-classic-theme .hrm-theme-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-top: 10px;
}

body.hrm-classic-theme .hrm-theme-quick-btn {
    width: 100%;
    border: 1px solid #d4c29e;
    border-radius: 10px;
    padding: 10px 8px;
    text-align: left;
    color: #f6f1e5;
    background: #2f5d7c;
    box-shadow: 0 4px 10px rgba(34, 49, 63, 0.12);
}

body.hrm-classic-theme .hrm-theme-quick-btn .title {
    display: block;
    font-weight: 700;
    font-size: 12px;
    margin-bottom: 4px;
}

body.hrm-classic-theme .hrm-theme-quick-btn .hint {
    display: block;
    font-size: 11px;
    opacity: 0.9;
}

body.hrm-classic-theme .hrm-theme-quick-btn.active {
    outline: 2px solid #b78d45;
    outline-offset: 1px;
}

body.hrm-classic-theme .hrm-theme-quick-btn.theme-classic {
    background: linear-gradient(135deg, #1f3f5b 0%, #2f5d7c 100%);
}

body.hrm-classic-theme .hrm-theme-quick-btn.theme-corporate {
    background: linear-gradient(135deg, #1f3b57 0%, #2a6f9f 100%);
}

body.hrm-classic-theme .hrm-theme-quick-btn.theme-minimal {
    color: #2f3a45;
    border-color: #cdd5de;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
}

/* Corporate overrides layered on top of classic baseline styles. */
body.hrm-corporate-theme {
    background:
        radial-gradient(1300px 440px at 100% -12%, rgba(54, 119, 174, 0.16), transparent 62%),
        radial-gradient(1100px 300px at 0% 0%, rgba(31, 59, 87, 0.1), transparent 58%),
        linear-gradient(180deg, #eef3f8 0%, #f7fafc 100%);
}

body.hrm-corporate-theme .content-header {
    background: linear-gradient(120deg, #1f3b57 0%, #2c5f87 65%, #3f7fac 100%);
    border-color: #9bb7d1;
}

body.hrm-corporate-theme .content-header .text-muted,
body.hrm-corporate-theme .content-header p {
    color: #dce9f4 !important;
}

body.hrm-corporate-theme .box {
    background: #ffffff;
    border-color: #d6e0ea;
    border-top-color: #3b7098;
    box-shadow: 0 8px 18px rgba(33, 64, 92, 0.09);
}

body.hrm-corporate-theme .box .box-header {
    background: linear-gradient(180deg, #f2f7fb 0%, #e8f0f7 100%);
    border-bottom-color: #d6e2ee;
}

body.hrm-corporate-theme .box .box-title,
body.hrm-corporate-theme .form-group label {
    color: #2a4055;
}

body.hrm-corporate-theme .table>thead>tr>th {
    background: #e9f1f8;
    border-bottom-color: #d5e1ec;
    color: #2a4156;
}

body.hrm-corporate-theme .table-striped>tbody>tr:nth-of-type(odd) {
    background-color: #f7fbfe;
}

body.hrm-corporate-theme .table-hover>tbody>tr:hover {
    background-color: #edf5fb;
}

body.hrm-corporate-theme .btn-primary,
body.hrm-corporate-theme .btn-success,
body.hrm-corporate-theme .btn-info {
    background: linear-gradient(180deg, #2f6f9c 0%, #24557a 100%);
    border-color: #214f72;
}

body.hrm-corporate-theme .btn-primary:hover,
body.hrm-corporate-theme .btn-success:hover,
body.hrm-corporate-theme .btn-info:hover,
body.hrm-corporate-theme .btn-primary:focus,
body.hrm-corporate-theme .btn-success:focus,
body.hrm-corporate-theme .btn-info:focus {
    background: linear-gradient(180deg, #3d7daa 0%, #2c638c 100%);
    border-color: #1f4c6d;
}

body.hrm-corporate-theme .btn-default,
body.hrm-corporate-theme .btn-secondary {
    background: linear-gradient(180deg, #f4f8fc 0%, #e7eff7 100%);
    border-color: #c6d6e7;
    color: #2f465c;
}

body.hrm-corporate-theme .small-box {
    border-color: rgba(26, 56, 82, 0.18);
    box-shadow: 0 10px 22px rgba(26, 56, 82, 0.14);
}

body.hrm-corporate-theme .small-box .small-box-footer {
    background: rgba(5, 20, 34, 0.18);
}

body.hrm-corporate-theme .small-box.hrm-kpi-people { background: linear-gradient(135deg, #316693 0%, #4085ba 100%) !important; }
body.hrm-corporate-theme .small-box.hrm-kpi-active { background: linear-gradient(135deg, #2d6a54 0%, #3f8f74 100%) !important; }
body.hrm-corporate-theme .small-box.hrm-kpi-leave { background: linear-gradient(135deg, #56708b 0%, #6f8eac 100%) !important; }
body.hrm-corporate-theme .small-box.hrm-kpi-risk { background: linear-gradient(135deg, #7b3e36 0%, #9e554a 100%) !important; }
body.hrm-corporate-theme .small-box.hrm-kpi-payroll,
body.hrm-corporate-theme .small-box.hrm-kpi-finance { background: linear-gradient(135deg, #224b74 0%, #3673ab 100%) !important; }
body.hrm-corporate-theme .small-box.hrm-kpi-attendance { background: linear-gradient(135deg, #2d6379 0%, #3f84a2 100%) !important; }
body.hrm-corporate-theme .small-box.hrm-kpi-attention { background: linear-gradient(135deg, #5f607f 0%, #7b7ea2 100%) !important; }
body.hrm-corporate-theme .small-box.hrm-kpi-structure { background: linear-gradient(135deg, #384e69 0%, #4f6a89 100%) !important; }

body.hrm-corporate-theme .hrm-kpi-chip {
    background: #edf4fb;
    border-color: #c7d8ea;
    color: #2d4860;
}

body.hrm-corporate-theme .hrm-kpi-legend-title {
    color: #2d4860;
}

body.hrm-corporate-theme .hrm-theme-quick-btn {
    border-color: #b6c8db;
}

body.hrm-corporate-theme .hrm-theme-quick-btn.active {
    outline-color: #3f7fac;
}

/* Minimal light overrides layered on top of classic baseline styles. */
body.hrm-minimal-theme {
    background: linear-gradient(180deg, #f7f9fb 0%, #fbfcfe 100%);
}

body.hrm-minimal-theme .content-header {
    background: linear-gradient(180deg, #ffffff 0%, #f3f6fa 100%);
    border-color: #d8dee7;
    box-shadow: 0 6px 14px rgba(35, 50, 65, 0.08);
}

body.hrm-minimal-theme .content-header:after {
    display: none;
}

body.hrm-minimal-theme .content-header h1,
body.hrm-minimal-theme .content-header .tw-text-black {
    color: #2a3744 !important;
    text-shadow: none;
}

body.hrm-minimal-theme .content-header .text-muted,
body.hrm-minimal-theme .content-header p {
    color: #5f6f7f !important;
}

body.hrm-minimal-theme .box {
    background: #ffffff;
    border-color: #e1e6ed;
    border-top-color: #8aa3bb;
    box-shadow: 0 6px 14px rgba(41, 54, 67, 0.06);
}

body.hrm-minimal-theme .box .box-header {
    background: #f6f8fb;
    border-bottom-color: #e4eaf1;
}

body.hrm-minimal-theme .box .box-title,
body.hrm-minimal-theme .form-group label,
body.hrm-minimal-theme .hrm-kpi-legend-title {
    color: #324252;
}

body.hrm-minimal-theme .table>thead>tr>th {
    background: #f2f6fa;
    border-bottom-color: #e0e7ef;
    color: #334556;
}

body.hrm-minimal-theme .table-striped>tbody>tr:nth-of-type(odd) {
    background-color: #f9fbfd;
}

body.hrm-minimal-theme .table-hover>tbody>tr:hover {
    background-color: #eef4fa;
}

body.hrm-minimal-theme .btn-primary,
body.hrm-minimal-theme .btn-success,
body.hrm-minimal-theme .btn-info {
    background: linear-gradient(180deg, #4e6d87 0%, #3b5368 100%);
    border-color: #32485c;
    color: #f6fbff;
}

body.hrm-minimal-theme .btn-primary:hover,
body.hrm-minimal-theme .btn-success:hover,
body.hrm-minimal-theme .btn-info:hover,
body.hrm-minimal-theme .btn-primary:focus,
body.hrm-minimal-theme .btn-success:focus,
body.hrm-minimal-theme .btn-info:focus {
    background: linear-gradient(180deg, #607f9a 0%, #49657b 100%);
    border-color: #304659;
    color: #ffffff;
}

body.hrm-minimal-theme .btn-default,
body.hrm-minimal-theme .btn-secondary {
    background: #f8fafc;
    border-color: #d6dee8;
    color: #334658;
}

body.hrm-minimal-theme .small-box {
    border-color: rgba(74, 94, 114, 0.22);
    box-shadow: 0 8px 16px rgba(58, 74, 89, 0.11);
}

body.hrm-minimal-theme .small-box .small-box-footer {
    background: rgba(30, 42, 54, 0.18);
}

body.hrm-minimal-theme .small-box.hrm-kpi-people { background: linear-gradient(135deg, #577089 0%, #7b9ab8 100%) !important; }
body.hrm-minimal-theme .small-box.hrm-kpi-active { background: linear-gradient(135deg, #54776b 0%, #75a092 100%) !important; }
body.hrm-minimal-theme .small-box.hrm-kpi-leave { background: linear-gradient(135deg, #6d7384 0%, #8f98ad 100%) !important; }
body.hrm-minimal-theme .small-box.hrm-kpi-risk { background: linear-gradient(135deg, #7b5250 0%, #a07472 100%) !important; }
body.hrm-minimal-theme .small-box.hrm-kpi-payroll,
body.hrm-minimal-theme .small-box.hrm-kpi-finance { background: linear-gradient(135deg, #4b647b 0%, #6f8eac 100%) !important; }
body.hrm-minimal-theme .small-box.hrm-kpi-attendance { background: linear-gradient(135deg, #4f7183 0%, #749aac 100%) !important; }
body.hrm-minimal-theme .small-box.hrm-kpi-attention { background: linear-gradient(135deg, #6e697d 0%, #938ca7 100%) !important; }
body.hrm-minimal-theme .small-box.hrm-kpi-structure { background: linear-gradient(135deg, #4f5f74 0%, #72859f 100%) !important; }

body.hrm-minimal-theme .hrm-kpi-chip {
    background: #f2f6fa;
    border-color: #d4deea;
    color: #384b5d;
}

body.hrm-minimal-theme .hrm-theme-quick-btn {
    border-color: #d7dee7;
    box-shadow: 0 3px 8px rgba(52, 66, 80, 0.1);
}

body.hrm-minimal-theme .hrm-theme-quick-btn.active {
    outline-color: #7d95ad;
}

@keyframes hrmHeaderFade {
    0% { opacity: 0; transform: translateY(-6px); }
    100% { opacity: 1; transform: translateY(0); }
}

@media (max-width: 767px) {
    body.hrm-classic-theme .content-header {
        margin: 10px;
        padding: 14px;
    }

    body.hrm-classic-theme .btn {
        border-radius: 7px;
    }

    body.hrm-classic-theme .hrm-theme-quick-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="content-header">
    <div class="row">
        <div class="col-sm-8">
            <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black mb-1">{{ $title ?? '' }}</h1>
            @if(!empty($subtitle))
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="col-sm-4 text-right no-print" style="margin-top: 8px;">
            {!! $actions ?? '' !!}
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    var hrmTheme = @json($hrmTheme);
    document.body.classList.add('hrm-classic-theme');
    document.body.classList.remove('hrm-corporate-theme');
    document.body.classList.remove('hrm-minimal-theme');
    if (hrmTheme === 'corporate') {
        document.body.classList.add('hrm-corporate-theme');
    }
    if (hrmTheme === 'minimal') {
        document.body.classList.add('hrm-minimal-theme');
    }

    if (typeof window.hrmAlert !== 'function') {
        window.hrmAlert = function(message, type){
            var t = type || 'error';
            if (typeof window.showToast === 'function') {
                window.showToast(t, message);
                return;
            }
            if (window.toastr && typeof window.toastr[t] === 'function') {
                window.toastr[t](message);
                return;
            }
            var toast = document.createElement('div');
            toast.className = 'alert alert-' + (t === 'error' ? 'danger' : t);
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(function(){ if (toast.parentNode) toast.parentNode.removeChild(toast); }, 3000);
        };
    }

    if (typeof window.hrmConfirm !== 'function') {
        window.hrmConfirm = function(message, opts){
            var options = opts || {};
            var text = message || options.text || 'Are you sure?';
            var danger = typeof options.danger === 'boolean'
                ? options.danger
                : /delete|remove|permanent|cannot be undone/i.test(String(text));
            var confirmText = options.confirmButtonText || (danger ? 'Delete' : 'Confirm');
            var cancelText = options.cancelButtonText || 'Cancel';
            var title = options.title || (danger ? 'Confirm Action' : 'Please Confirm');
            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                return Swal.fire({
                    title: title,
                    text: text,
                    icon: options.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    confirmButtonColor: danger ? '#d33' : '#3085d6'
                }).then(function(result){
                    return !!(result && result.isConfirmed);
                });
            }

            return new Promise(function(resolve){
                var existing = document.getElementById('hrmConfirmFallback');
                if (existing) existing.remove();

                var overlay = document.createElement('div');
                overlay.id = 'hrmConfirmFallback';
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.background = 'rgba(0,0,0,0.35)';
                overlay.style.zIndex = '9998';

                var box = document.createElement('div');
                box.style.position = 'fixed';
                box.style.top = '50%';
                box.style.left = '50%';
                box.style.transform = 'translate(-50%, -50%)';
                box.style.width = '360px';
                box.style.maxWidth = 'calc(100vw - 30px)';
                box.style.background = '#fff';
                box.style.borderRadius = '10px';
                box.style.padding = '14px';
                box.style.boxShadow = '0 12px 30px rgba(0,0,0,0.15)';
                var confirmClass = danger ? 'btn btn-danger btn-sm' : 'btn btn-primary btn-sm';
                box.innerHTML = '' +
                    '<div style="font-weight:600; margin-bottom:8px;">' + title + '</div>' +
                    '<div style="font-size:13px; color:#6b7280; margin-bottom:12px;">' + text + '</div>' +
                    '<div style="display:flex; justify-content:flex-end; gap:8px;">' +
                        '<button type="button" class="btn btn-default btn-sm" id="hrmConfirmNo">' + cancelText + '</button>' +
                        '<button type="button" class="' + confirmClass + '" id="hrmConfirmYes">' + confirmText + '</button>' +
                    '</div>';

                overlay.appendChild(box);
                document.body.appendChild(overlay);

                function done(val){
                    var el = document.getElementById('hrmConfirmFallback');
                    if (el) el.remove();
                    resolve(!!val);
                }

                overlay.addEventListener('click', function(e){ if (e.target === overlay) done(false); });
                document.getElementById('hrmConfirmNo').addEventListener('click', function(){ done(false); });
                document.getElementById('hrmConfirmYes').addEventListener('click', function(){ done(true); });
            });
        };
    }

    document.addEventListener('submit', function(e){
        var form = e.target;
        if (!form || !form.matches || !form.matches('form[data-hrm-confirm]')) return;
        if (form.getAttribute('data-hrm-confirmed') === '1') {
            form.removeAttribute('data-hrm-confirmed');
            return;
        }
        e.preventDefault();
        var msg = form.getAttribute('data-hrm-confirm') || 'Are you sure?';
        var isDanger = form.getAttribute('data-hrm-danger') === '1' || /delete|remove|permanent|cannot be undone/i.test(msg);
        window.hrmConfirm(msg, {
            title: form.getAttribute('data-hrm-confirm-title') || 'Please Confirm',
            confirmButtonText: form.getAttribute('data-hrm-confirm-yes') || (isDanger ? 'Delete' : 'Confirm'),
            cancelButtonText: form.getAttribute('data-hrm-confirm-no') || 'Cancel',
            danger: isDanger
        }).then(function(confirmed){
            if (confirmed) {
                form.setAttribute('data-hrm-confirmed', '1');
                form.submit();
            }
        });
    }, true);

    document.addEventListener('click', function(e){
        var btn = e.target && e.target.closest ? e.target.closest('[data-hrm-confirm-submit]') : null;
        if (!btn) return;
        e.preventDefault();
        var form = btn.closest('form');
        if (!form) return;
        var msg = btn.getAttribute('data-hrm-confirm') || form.getAttribute('data-hrm-confirm') || 'Are you sure?';
        var isDanger = btn.getAttribute('data-hrm-danger') === '1' || form.getAttribute('data-hrm-danger') === '1' || /delete|remove|permanent|cannot be undone/i.test(msg);
        window.hrmConfirm(msg, {
            title: btn.getAttribute('data-hrm-confirm-title') || 'Please Confirm',
            confirmButtonText: btn.getAttribute('data-hrm-confirm-yes') || (isDanger ? 'Delete' : 'Confirm'),
            cancelButtonText: btn.getAttribute('data-hrm-confirm-no') || 'Cancel',
            danger: isDanger
        }).then(function(confirmed){
            if (confirmed) form.submit();
        });
    });
});
</script>
@endpush