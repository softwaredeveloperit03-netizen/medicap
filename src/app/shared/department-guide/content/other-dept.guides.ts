import { DepartmentGuideBook, GuideModule } from '../department-guide.models';

function f(name: string, required: boolean, note?: string) {
  return { name, required, note };
}

function m(partial: GuideModule): GuideModule {
  return partial;
}

/** Builds a consistent module guide skeleton for departments with lighter seed content. */
export function buildStandardModules(
  deptLabel: string,
  moduleDefs: { id: string; title: string; purpose: string }[]
): GuideModule[] {
  return moduleDefs.map((d) =>
    m({
      id: d.id,
      title: d.title,
      purpose: d.purpose,
      flowSteps: [
        `Open ${deptLabel} → ${d.title}`,
        'Select pending / new record',
        'Fill compulsory fields',
        'Save / submit',
        'Check / approve if required',
      ],
      workflow: [
        `From the ${deptLabel} dashboard, open “${d.title}”.`,
        'Identify the correct plant, document, or batch context.',
        'Enter all compulsory fields marked on the screen (required inputs / *).',
        'Complete optional fields only when information is available and relevant.',
        'Save or submit; complete checking / approval steps shown for your role.',
        'Verify the record appears in the module list / log with the expected status.',
      ],
      precautions: [
        'Do not skip compulsory fields — the system may block save or create incomplete GMP records.',
        'Do not use another user’s login to perform entries or approvals.',
        'Escalate abnormal results / deviations through QMS modules when quality is impacted.',
      ],
      compulsoryFields: [
        f('Primary identity (batch / document / item)', true),
        f('Date / activity date', true),
        f('Entry by (login user)', true),
        f('Status action (Save / Submit)', true),
      ],
      optionalFields: [f('Remarks', false), f('Attachments', false, 'If available on screen')],
      instructions: [
        'Use sidebar Guide anytime for this department’s full booklet.',
        'Use PM Intimation / Dept Head / QMS links from the sidebar when coordination is needed.',
        'Match physical documents and labels with the IDs selected in software.',
      ],
    })
  );
}

export const QA_GUIDE: DepartmentGuideBook = {
  department: 'Quality Assurance',
  aliases: ['qa', 'quality assurance', 'qualityassurance'],
  title: 'Quality Assurance Department — Operation & Functioning Guide',
  overview:
    'QA oversees GMP compliance: documentation, batch release support, change control, deviation, CAPA, audits, training coordination with departments, and quality system oversight in Medicap.',
  departmentFlow: [
    'Login → QA dashboard',
    'Select QMS / Documentation / Release / Audit module',
    'Review pending events',
    'Investigate / document / approve',
    'Close with evidence and linked actions',
  ],
  generalPrecautions: [
    'Do not approve incomplete change controls or deviations.',
    'Ensure CAPA effectiveness checks are scheduled where required.',
    'Protect controlled documents; use approved versions only.',
  ],
  modules: buildStandardModules('QA', [
    { id: 'qms', title: 'QMS (Deviation / Change Control / CAPA)', purpose: 'Manage quality events through investigation, impact assessment, and closure.' },
    { id: 'documents', title: 'Documents / SOP / Document Control', purpose: 'Control issuance, revision, and archival of GMP documents.' },
    { id: 'batch-release', title: 'Batch Release / Review', purpose: 'Review batch records and release decisions with supporting QC data.' },
    { id: 'audit', title: 'Audits / Self Inspection', purpose: 'Plan and record internal / external audit activities and follow-ups.' },
  ]),
};

export const PRODUCTION_GUIDE: DepartmentGuideBook = {
  department: 'Production',
  aliases: ['production', 'manufacturing', 'fproduction', 'formulation'],
  title: 'Production Department — Operation & Functioning Guide',
  overview:
    'Production executes manufacturing and packing activities using BMR/BPR workflows, line clearance, in-process checks, and coordination with Store, QC, and Engineering.',
  departmentFlow: [
    'Login → Production dashboard',
    'Select batch / line / logbook module',
    'Perform process step with compulsory entries',
    'IPQC / QA checks as required',
    'Complete batch step and hand over',
  ],
  generalPrecautions: [
    'Follow line clearance before starting a new product / batch.',
    'Do not proceed if critical equipment is under breakdown or overdue PM.',
    'Record yields and deviations promptly.',
  ],
  modules: buildStandardModules('Production', [
    { id: 'bmr', title: 'BMR / Batch Execution', purpose: 'Execute and record batch manufacturing steps.' },
    { id: 'logbooks', title: 'Equipment / Area Logbooks', purpose: 'Maintain usage and cleaning logs for production equipment and areas.' },
    { id: 'planning', title: 'Planning / Work Orders', purpose: 'View and act on planned batches and work orders.' },
    { id: 'qms', title: 'Production QMS', purpose: 'Raise / respond to deviation and change control from production.' },
  ]),
};

export const STORE_GUIDE: DepartmentGuideBook = {
  department: 'Store',
  aliases: ['store', 'stores', 'warehouse', 'godown', 'godownstore'],
  title: 'Store Department — Operation & Functioning Guide',
  overview:
    'Store manages inbound materials, GRN, quarantine / approved stock, dispensing, returns, and inventory status in coordination with Purchase, QC, and Production.',
  departmentFlow: [
    'Login → Store dashboard',
    'Receiving / GRN → Quarantine',
    'QC sampling & testing',
    'Approved stock put-away',
    'Dispensing against requests',
  ],
  generalPrecautions: [
    'Do not dispense Quarantine or Rejected stock.',
    'FEFO / FIFO as per material type and site SOP.',
    'Keep physical bin locations aligned with system locations.',
  ],
  modules: buildStandardModules('Store', [
    { id: 'receiving', title: 'Receiving / GRN', purpose: 'Receive materials and create GRN with compulsory supplier and quantity fields.' },
    { id: 'dispensing', title: 'Dispensing', purpose: 'Issue raw / packing materials against approved requests.' },
    { id: 'inventory', title: 'Inventory / Status', purpose: 'Monitor stock status, locations, and material status changes.' },
    { id: 'returns', title: 'Returns / Damage / Spillage', purpose: 'Record returned, damaged, or spilled materials with investigation links.' },
  ]),
};

export const HR_GUIDE: DepartmentGuideBook = {
  department: 'HR',
  aliases: ['hr', 'human resource', 'human resources'],
  title: 'HR Department — Operation & Functioning Guide',
  overview:
    'HR manages employee masters, attendance, training coordination, performance, and related workforce processes used across Medicap departments.',
  departmentFlow: [
    'Login → HR dashboard',
    'Select employee / training / attendance module',
    'Enter or update records',
    'Route for dept head / QA review when required',
  ],
  generalPrecautions: [
    'Protect personal data; share only with authorized roles.',
    'Training records must be complete before assigning GMP roles.',
  ],
  modules: buildStandardModules('HR', [
    { id: 'employee', title: 'Employee Master', purpose: 'Maintain employee identity, department, and role information.' },
    { id: 'training', title: 'Training', purpose: 'Plan, record, and evaluate training activities.' },
    { id: 'attendance', title: 'Attendance / Leave', purpose: 'Record attendance and leave as configured.' },
    { id: 'performance', title: 'Performance', purpose: 'Capture performance appraisals and related checklists.' },
  ]),
};

export const PURCHASE_GUIDE: DepartmentGuideBook = {
  department: 'Purchase',
  aliases: ['purchase', 'procurement'],
  title: 'Purchase Department — Operation & Functioning Guide',
  overview:
    'Purchase converts indents to orders, tracks supplier supplies, and coordinates with Store for receiving and QC for material quality.',
  departmentFlow: [
    'Indent raised by department',
    'Purchase review → PO',
    'Supplier delivery → Store GRN',
    'QC testing → approval',
  ],
  generalPrecautions: [
    'Order only approved vendors where vendor qualification applies.',
    'Match PO, delivery note, and GRN quantities carefully.',
  ],
  modules: buildStandardModules('Purchase', [
    { id: 'indent', title: 'Indent / PR', purpose: 'Review purchase requisitions from departments.' },
    { id: 'po', title: 'Purchase Order', purpose: 'Create and manage purchase orders.' },
    { id: 'reports', title: 'Purchase Reports', purpose: 'Track pending supplies and order status.' },
  ]),
};

export const DISPATCH_GUIDE: DepartmentGuideBook = {
  department: 'Dispatch',
  aliases: ['dispatch', 'finished goods', 'fg'],
  title: 'Dispatch Department — Operation & Functioning Guide',
  overview:
    'Dispatch handles finished goods inventory, sales / dispatch documents, release coordination, and related intimations.',
  departmentFlow: [
    'FG available after QC / QA release',
    'Open Dispatch module',
    'Prepare dispatch / invoice documents',
    'Update inventory on dispatch',
  ],
  generalPrecautions: [
    'Do not dispatch unreleased batches.',
    'Verify batch numbers on labels vs system selection.',
  ],
  modules: buildStandardModules('Dispatch', [
    { id: 'inventory', title: 'FG Inventory', purpose: 'Monitor finished goods stock.' },
    { id: 'dispatch-docs', title: 'Dispatch / Sales Documents', purpose: 'Create and print dispatch related documents.' },
    { id: 'intimation', title: 'Intimation Received', purpose: 'Act on sales / packing intimations.' },
  ]),
};

export const IT_GUIDE: DepartmentGuideBook = {
  department: 'IT',
  aliases: ['it', 'information technology'],
  title: 'IT Department — Operation & Functioning Guide',
  overview:
    'IT supports system access, infrastructure, and digital controls that underpin Medicap operations. Follow change and access control practices.',
  departmentFlow: [
    'Receive request',
    'Assess impact',
    'Implement with backup / change record',
    'Verify and close',
  ],
  generalPrecautions: [
    'Never share admin credentials.',
    'Take backups before critical changes.',
    'Document system changes affecting GMP data.',
  ],
  modules: buildStandardModules('IT', [
    { id: 'access', title: 'User Access / Support', purpose: 'Manage access requests and support tickets.' },
    { id: 'systems', title: 'Systems / Infrastructure', purpose: 'Maintain application and infrastructure health.' },
  ]),
};

export const EHS_GUIDE: DepartmentGuideBook = {
  department: 'EHS',
  aliases: ['ehs', 'safety', 'environment'],
  title: 'EHS Department — Operation & Functioning Guide',
  overview:
    'EHS manages safety, environment, training, and related compliance activities within Medicap.',
  departmentFlow: [
    'Login → EHS dashboard',
    'Select safety / training / incident module',
    'Record activity',
    'Follow up actions to closure',
  ],
  generalPrecautions: [
    'Report incidents immediately.',
    'Do not bypass safety interlocks or PPE requirements.',
  ],
  modules: buildStandardModules('EHS', [
    { id: 'safety', title: 'Safety / Incident', purpose: 'Record safety observations and incidents.' },
    { id: 'training', title: 'EHS Training', purpose: 'Conduct and record EHS training.' },
    { id: 'systems', title: 'EHS Systems / Checks', purpose: 'Maintain fire / first-aid / hydrant and related checks.' },
  ]),
};

export const GENERIC_DEPT_GUIDES: DepartmentGuideBook[] = [
  QA_GUIDE,
  PRODUCTION_GUIDE,
  STORE_GUIDE,
  HR_GUIDE,
  PURCHASE_GUIDE,
  DISPATCH_GUIDE,
  IT_GUIDE,
  EHS_GUIDE,
  {
    department: 'Management',
    aliases: ['management', 'plant head', 'planthead'],
    title: 'Management — Operation & Functioning Guide',
    overview: 'Management views cross-department status, approvals, and oversight modules. Use department-specific Guide books when working inside a functional area.',
    departmentFlow: ['Login → Management', 'Select oversight module', 'Review KPIs / pending approvals', 'Decide / escalate'],
    generalPrecautions: ['Do not approve without reviewing linked evidence.', 'Maintain confidentiality of business and quality data.'],
    modules: buildStandardModules('Management', [
      { id: 'overview', title: 'Management Overview', purpose: 'Review plant / department status and pending actions.' },
      { id: 'capa', title: 'CAPA / Quality Oversight', purpose: 'Oversee critical quality actions.' },
    ]),
  },
  {
    department: 'Admin',
    aliases: ['admin', 'administration'],
    title: 'Admin Department — Operation & Functioning Guide',
    overview: 'Administration supports facility and office operations. Follow module screens for compulsory fields on each activity.',
    departmentFlow: ['Login → Admin', 'Select module', 'Enter activity', 'Save / close'],
    generalPrecautions: ['Keep visitor and security records accurate.', 'Coordinate with EHS for safety-related admin activities.'],
    modules: buildStandardModules('Admin', [
      { id: 'admin-ops', title: 'Admin Operations', purpose: 'Perform day-to-day administrative records in Medicap.' },
      { id: 'safety-training', title: 'Safety / Fire Training support', purpose: 'Support scheduled safety training records where linked.' },
    ]),
  },
  {
    department: 'Planning',
    aliases: ['planning', 'ppic'],
    title: 'Planning Department — Operation & Functioning Guide',
    overview: 'Planning coordinates production and material plans. Keep plans aligned with Store stock and Production capacity.',
    departmentFlow: ['Login → Planning', 'Create / update plan', 'Release to Production / Store', 'Monitor progress'],
    generalPrecautions: ['Do not freeze plans that conflict with QC hold / rejected materials.', 'Communicate plan changes promptly.'],
    modules: buildStandardModules('Planning', [
      { id: 'plans', title: 'Production / Material Plans', purpose: 'Create and maintain planning documents.' },
      { id: 'stplan', title: 'Stability / Special Plans', purpose: 'Maintain special planning workflows where configured.' },
    ]),
  },
  {
    department: 'Regulatory',
    aliases: ['regulatory', 'regulatory affairs'],
    title: 'Regulatory Department — Operation & Functioning Guide',
    overview: 'Regulatory manages submissions, registrations, and related documentation impacting marketed products.',
    departmentFlow: ['Login → Regulatory', 'Select dossier / correspondence module', 'Update controlled records', 'Archive evidence'],
    generalPrecautions: ['Use only approved data for submissions.', 'Track commitments and variations carefully.'],
    modules: buildStandardModules('Regulatory', [
      { id: 'filings', title: 'Filings / Registrations', purpose: 'Maintain regulatory filing records.' },
      { id: 'docs', title: 'Regulatory Documents', purpose: 'Control regulatory document sets.' },
    ]),
  },
  {
    department: 'IPQC',
    aliases: ['ipqc', 'in process quality control'],
    title: 'IPQC — Operation & Functioning Guide',
    overview: 'IPQC performs in-process checks supporting Production and QC release decisions.',
    departmentFlow: ['Login → IPQC', 'Select line / batch check', 'Record results', 'Escalate OOS / OOT'],
    generalPrecautions: ['Stop the line if critical IPQC fails as per SOP.', 'Use calibrated instruments only.'],
    modules: buildStandardModules('IPQC', [
      { id: 'ip-checks', title: 'In-Process Checks', purpose: 'Record in-process quality checks.' },
      { id: 'fg-tests', title: 'FG / Related Tests', purpose: 'Support finished product related IPQC activities.' },
    ]),
  },
  {
    department: 'R&D',
    aliases: ['rnd', 'r&d', 'research', 'npd'],
    title: 'R&D / NPD — Operation & Functioning Guide',
    overview: 'R&D and NPD modules support development batches, trials, and related documentation before commercial transfer.',
    departmentFlow: ['Login → R&D / NPD', 'Select trial / formulation module', 'Record experimental data', 'Transfer learnings to QA/Production'],
    generalPrecautions: ['Segregate development materials from commercial stock.', 'Document all formula changes.'],
    modules: buildStandardModules('R&D', [
      { id: 'trials', title: 'Trials / Development Batches', purpose: 'Record development activities.' },
      { id: 'docs', title: 'Development Documents', purpose: 'Maintain R&D documentation sets.' },
    ]),
  },
  {
    department: 'Microbiology',
    aliases: ['microbiology', 'micro'],
    title: 'Microbiology — Operation & Functioning Guide',
    overview: 'Microbiology performs environmental and product microbiological testing. Follow aseptic and incubation procedures strictly.',
    departmentFlow: ['Login → Microbiology', 'Receive sample', 'Incubate / test', 'Enter results', 'Review / release'],
    generalPrecautions: ['Maintain culture control and media growth promotion records.', 'Do not open incubators casually during critical intervals.'],
    modules: buildStandardModules('Microbiology', [
      { id: 'testing', title: 'Micro Testing', purpose: 'Perform and record microbiological tests.' },
      { id: 'em', title: 'Environmental Monitoring', purpose: 'Record EM activities and trends.' },
    ]),
  },
  {
    department: 'Security',
    aliases: ['security'],
    title: 'Security — Operation & Functioning Guide',
    overview: 'Security controls site access and related logs. Accuracy of visitor and gate records is critical.',
    departmentFlow: ['Login → Security', 'Record entry / exit', 'Escalate incidents', 'Archive daily logs'],
    generalPrecautions: ['Do not allow unauthorized entry.', 'Report incidents immediately to Admin / EHS / Management.'],
    modules: buildStandardModules('Security', [
      { id: 'gate', title: 'Gate / Access Logs', purpose: 'Record personnel and material movements.' },
    ]),
  },
  {
    department: 'Training',
    aliases: ['training'],
    title: 'Training Department — Operation & Functioning Guide',
    overview: 'Training module owners schedule, deliver, and evaluate GMP and role-based training.',
    departmentFlow: ['Login → Training', 'Schedule session', 'Record attendance', 'Evaluate / close'],
    generalPrecautions: ['Incomplete training must block role assignment where policy requires.', 'Keep training materials current to approved SOP versions.'],
    modules: buildStandardModules('Training', [
      { id: 'sessions', title: 'Training Sessions', purpose: 'Plan and record training sessions.' },
      { id: 'evaluation', title: 'Evaluation', purpose: 'Record trainee evaluation outcomes.' },
    ]),
  },
  {
    department: 'Accounts',
    aliases: ['accounts', 'finance', 'accounts-finance'],
    title: 'Accounts — Operation & Functioning Guide',
    overview: 'Accounts modules support financial records linked to plant operations. Follow internal financial controls.',
    departmentFlow: ['Login → Accounts', 'Select voucher / report', 'Enter data', 'Post / approve'],
    generalPrecautions: ['Segregation of duties for create vs approve.', 'Reconcile with Store / Purchase documents.'],
    modules: buildStandardModules('Accounts', [
      { id: 'vouchers', title: 'Vouchers / Entries', purpose: 'Create and post accounting entries.' },
      { id: 'reports', title: 'Financial Reports', purpose: 'Generate and review reports.' },
    ]),
  },
  {
    department: 'Marketing',
    aliases: ['marketing', 'exports', 'export', 'customer'],
    title: 'Marketing / Exports / Customer — Operation & Functioning Guide',
    overview: 'Commercial modules for orders, customers, and exports. Coordinate with Dispatch and Regulatory for compliant shipments.',
    departmentFlow: ['Login → Marketing / Exports', 'Select order / customer module', 'Update status', 'Coordinate dispatch'],
    generalPrecautions: ['Do not confirm dispatch of unreleased goods.', 'Keep customer and regulatory documents aligned.'],
    modules: buildStandardModules('Marketing', [
      { id: 'orders', title: 'Orders / Customers', purpose: 'Maintain commercial order and customer records.' },
      { id: 'exports', title: 'Exports Documentation', purpose: 'Prepare export-related documentation.' },
    ]),
  },
  {
    department: 'Engineering Store',
    aliases: ['engi-store', 'engineering store', 'engistore'],
    title: 'Engineering Store — Operation & Functioning Guide',
    overview: 'Engineering Store manages spares and engineering material inventory supporting maintenance.',
    departmentFlow: ['Indent / issue request', 'Issue spare', 'Update stock', 'Link to breakdown / PM if required'],
    generalPrecautions: ['Issue only approved spares for GMP equipment.', 'Keep min-max levels updated.'],
    modules: buildStandardModules('Engineering Store', [
      { id: 'spares', title: 'Spares Inventory', purpose: 'Receive and issue engineering spares.' },
      { id: 'damage', title: 'Damage / Returns', purpose: 'Record damaged engineering materials.' },
    ]),
  },
];
