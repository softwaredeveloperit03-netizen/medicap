import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DepartmentGuideBook, GuideModule, GuideOpenContext } from './department-guide.models';
import { DepartmentGuideService } from './department-guide.service';

@Component({
  selector: 'app-department-guide',
  templateUrl: './department-guide.component.html',
  styleUrls: ['./department-guide.component.css'],
})
export class DepartmentGuideComponent implements OnInit {
  loggedInDept = '';
  guide: DepartmentGuideBook | null = null;
  selectedModuleId = '';
  searchQuery = '';
  focusMode = false;
  context: GuideOpenContext = {};
  /** Module currently opened from sidebar context (curated or auto-built) */
  focusedModule: GuideModule | null = null;

  constructor(
    private guideService: DepartmentGuideService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.loggedInDept = localStorage.getItem('department') || '';
    this.guide = this.guideService.getGuideForDepartment(this.loggedInDept);

    this.route.queryParamMap.subscribe((params) => {
      this.context = {
        moduleId: params.get('module') || '',
        section: params.get('section') || '',
        title: params.get('title') || '',
        returnUrl: params.get('returnUrl') || '',
      };
      this.applyContext();
    });
  }

  private applyContext(): void {
    const hasContext = !!(this.context.moduleId || this.context.section || this.context.title);
    this.focusMode = hasContext;

    if (!this.guide && hasContext) {
      // Still show a synthetic book for unknown dept labels
      this.focusedModule = this.guideService.buildSectionGuide(this.context);
      this.selectedModuleId = this.focusedModule.id;
      return;
    }

    if (!this.guide) {
      this.focusedModule = null;
      this.selectedModuleId = '';
      return;
    }

    if (!hasContext) {
      this.focusMode = false;
      this.focusedModule = null;
      this.selectedModuleId = '__overview';
      return;
    }

    const found = this.guideService.findModuleInBook(this.guide, this.context);
    this.focusedModule = found || this.guideService.buildSectionGuide(this.context);

    // Ensure focused module appears in TOC
    if (!found) {
      const exists = this.guide.modules.some((m) => m.id === this.focusedModule!.id);
      if (!exists) {
        this.guide = {
          ...this.guide,
          modules: [this.focusedModule, ...this.guide.modules],
        };
      }
    }

    this.selectedModuleId = this.focusedModule.id;
  }

  get selectedModule(): GuideModule | null {
    if (this.selectedModuleId === '__overview') {
      return null;
    }
    if (this.focusedModule && this.selectedModuleId === this.focusedModule.id) {
      return this.focusedModule;
    }
    if (!this.guide) {
      return this.focusedModule;
    }
    return this.guide.modules.find((m) => m.id === this.selectedModuleId) || this.focusedModule || this.guide.modules[0] || null;
  }

  get filteredModules(): GuideModule[] {
    const modules = this.guide?.modules || (this.focusedModule ? [this.focusedModule] : []);
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return modules;
    }
    return modules.filter(
      (m) =>
        m.title.toLowerCase().includes(q) ||
        m.purpose.toLowerCase().includes(q) ||
        m.workflow.some((w) => w.toLowerCase().includes(q))
    );
  }

  get pageHeading(): string {
    if (this.focusMode && (this.context.title || this.context.section || this.focusedModule?.title)) {
      return `Guide — ${this.context.title || this.context.section || this.focusedModule?.title}`;
    }
    return this.guide?.title || 'Module Guide';
  }

  selectModule(id: string): void {
    this.selectedModuleId = id;
  }

  trackByModuleId(_i: number, m: GuideModule): string {
    return m.id;
  }

  onClose(): void {
    const back = this.context.returnUrl || this.guideService.getHomeRouteForDepartment(this.loggedInDept);
    void this.router.navigateByUrl(back);
  }

  printGuide(): void {
    window.print();
  }
}
