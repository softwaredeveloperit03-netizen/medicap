import { Injectable } from '@angular/core';
import { DepartmentGuideBook, GuideModule, GuideOpenContext } from './department-guide.models';
import { ENGINEERING_GUIDE, QC_GUIDE } from './content/engineering-qc.guides';
import { GENERIC_DEPT_GUIDES } from './content/other-dept.guides';

@Injectable({ providedIn: 'root' })
export class DepartmentGuideService {
  private readonly books: DepartmentGuideBook[] = [
    ENGINEERING_GUIDE,
    QC_GUIDE,
    ...GENERIC_DEPT_GUIDES,
  ];

  normalizeKey(value: string | null | undefined): string {
    return String(value || '')
      .toLowerCase()
      .replace(/&/g, 'and')
      .replace(/[^a-z0-9]+/g, '')
      .trim();
  }

  getGuideForDepartment(department?: string | null): DepartmentGuideBook | null {
    const raw = (department || localStorage.getItem('department') || '').trim();
    if (!raw) {
      return null;
    }
    const key = this.normalizeKey(raw);

    for (const book of this.books) {
      const names = [book.department, ...(book.aliases || [])];
      if (names.some((n) => this.normalizeKey(n) === key || this.normalizeKey(n).includes(key) || key.includes(this.normalizeKey(n)))) {
        return book;
      }
    }

    return (
      this.books.find((b) => this.normalizeKey(b.department).includes(key) || key.includes(this.normalizeKey(b.department))) ||
      null
    );
  }

  /** Pick module guide from curated book using module id / section / URL keys. */
  findModuleInBook(book: DepartmentGuideBook, ctx: GuideOpenContext): GuideModule | null {
    const candidates = [ctx.moduleId, ctx.section, ctx.title]
      .map((v) => this.normalizeKey(v))
      .filter(Boolean);

    if (!candidates.length) {
      return null;
    }

    for (const mod of book.modules) {
      const keys = [mod.id, mod.title, ...(mod.routeKeys || [])].map((k) => this.normalizeKey(k));
      if (candidates.some((c) => keys.some((k) => k === c || k.includes(c) || c.includes(k)))) {
        return mod;
      }
    }
    return null;
  }

  /** Build a section-specific guide when curated content is not available. */
  buildSectionGuide(ctx: GuideOpenContext): GuideModule {
    const title = (ctx.title || ctx.section || ctx.moduleId || 'Current Module').trim();
    const section = (ctx.section || title).trim();
    const id = this.normalizeKey(ctx.moduleId || section) || 'section';

    return {
      id,
      title,
      purpose: `Operation and functioning guide for “${title}”. Follow the workflow below for this module/section screens and forms.`,
      routeKeys: ctx.moduleId ? [ctx.moduleId] : [],
      flowSteps: [
        `Open ${title}`,
        `Select the required ${section} activity / form`,
        'Fill all compulsory fields on the form',
        'Fill optional fields when applicable',
        'Save / Submit',
        'Complete Check / Approve if your role requires it',
        'Verify status in the module list / log',
      ],
      workflow: [
        `Navigate to the “${title}” module from your department dashboard or sidebar.`,
        `Open the “${section}” section or form that matches your current task.`,
        'Confirm plant, batch, equipment, document, or material identity before entry.',
        'Complete every compulsory field (usually marked required / *). Do not leave blank if the field is mandatory for save.',
        'Complete optional fields only when data is available and relevant to the activity.',
        'Review entries for accuracy, then Save or Submit.',
        'If the screen shows Check / Approve / Accept, complete that step with the authorized login.',
        'Confirm the record appears with the expected status in the module grid or log.',
      ],
      precautions: [
        `Do not mix data from another module into “${title}” forms.`,
        'Do not bypass compulsory validations or use another user’s credentials.',
        'If results / readings are out of limit, escalate per site SOP (OOS / deviation / breakdown as applicable).',
        'Keep physical labels, logbooks, and system IDs aligned.',
      ],
      compulsoryFields: [
        { name: 'Primary identity (batch / equipment / document / item)', required: true, note: 'Must match the physical / source record' },
        { name: 'Activity / entry date', required: true },
        { name: 'Mandatory form inputs shown on screen', required: true, note: 'All fields marked required' },
        { name: 'Entry by (logged-in user)', required: true },
        { name: 'Save / Submit action', required: true },
      ],
      optionalFields: [
        { name: 'Remarks / comments', required: false },
        { name: 'Attachments / supporting files', required: false, note: 'If the form supports upload' },
        { name: 'Additional reference numbers', required: false },
      ],
      instructions: [
        `This Guide is scoped to the “${section}” section currently open.`,
        'Use the left Guide TOC only if you need related modules in the same department.',
        'Use Print to keep a hard copy for training / floor reference.',
        'PM Intimation / Dept Head / QMS sidebar links are for coordination, not for skipping this module’s own procedure.',
      ],
    };
  }

  resolveOpenContextFromUrl(url: string, extras?: Partial<GuideOpenContext>): GuideOpenContext {
    const path = String(url || '').split('?')[0];
    const parts = path.split('/').filter(Boolean);
    const moduleId = extras?.moduleId || parts[parts.length - 1] || parts[1] || '';
    return {
      moduleId,
      section: extras?.section || '',
      title: extras?.title || extras?.section || moduleId,
      returnUrl: extras?.returnUrl || path || '/',
    };
  }

  getHomeRouteForDepartment(department?: string | null): string {
    const raw = (department || localStorage.getItem('department') || '').trim();
    const key = this.normalizeKey(raw);
    const map: Record<string, string> = {
      qualitycontrol: '/qc',
      qc: '/qc',
      qualityassurance: '/qa',
      qa: '/qa',
      engineering: '/engineering',
      store: '/store',
      stores: '/store',
      production: '/production',
      fproduction: '/fproduction',
      hr: '/hr',
      it: '/it',
      ehs: '/ehs',
      purchase: '/purchase',
      dispatch: '/dispatch',
      planning: '/planning',
      management: '/management',
      admin: '/admin',
      ipqc: '/ipqc',
      microbiology: '/microbiology',
      security: '/security',
      training: '/training',
      accounts: '/accounts',
      marketing: '/marketing',
      regulatory: '/deptmodule/regulatory',
      engistore: '/engi-store',
      engineeringstore: '/engi-store',
      rnd: '/rnd',
      researchanddevelopment: '/rnd',
      npd: '/npd',
      godownstore: '/godownStore',
      exports: '/exports',
      export: '/export',
    };
    return map[key] || '/';
  }

  listDepartments(): string[] {
    return this.books.map((b) => b.department);
  }
}
