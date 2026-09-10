import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { ebmrBprTemplateFor } from '../shared/ebmr-bpr-defaults';
import { EbmrBprMasterService } from '../shared/ebmr-bpr-master.service';
import {
  EbmrBprDepartment,
  EbmrBprMasterListRow,
  EbmrBprStage,
  EbmrBprStep,
  EbmrBprSubstep,
} from '../shared/ebmr-bpr.models';
import { MasterHubReturnService } from '../../master-hub-return.service';

@Component({
  selector: 'app-ebmr-bpr-product-master',
  templateUrl: './ebmr-bpr-product-master.component.html',
  styleUrls: ['./ebmr-bpr-product-master.component.css'],
})
export class EbmrBprProductMasterComponent implements OnInit {
  department: EbmrBprDepartment = 'production';
  product_code = '';
  product_name = '';
  products: any[] = [];
  productsLoading = false;
  selectedProduct: any | null = null;
  productFilter = '';

  tree: EbmrBprStage[] = [];
  masters: EbmrBprMasterListRow[] = [];
  revision: number | null = null;
  updated_at: string | null = null;
  busy = false;
  banner: { kind: 'ok' | 'err' | 'info'; text: string } | null = null;
  rawJson = '';
  editMode: 'tree' | 'json' = 'tree';

  constructor(
    private route: ActivatedRoute,
    private api: EbmrBprMasterService,
    private data: DataAccessService,
    private masterHubReturn: MasterHubReturnService
  ) {}

  ngOnInit(): void {
    this.masterHubReturn.setReturnDepartment('process-stage');
    const dept = String(this.route.snapshot.paramMap.get('department') || 'production').toLowerCase();
    this.department = dept === 'packing' ? 'packing' : 'production';
    this.loadProductsFromMaster();
    this.refreshList();
  }

  get pageTitle(): string {
    return this.department === 'packing'
      ? 'Packing BPR — product master'
      : 'Production eBMR — product master';
  }

  get deptLabel(): string {
    return this.department === 'packing' ? 'Packing / BPR' : 'Production eBMR';
  }

  loadProductsFromMaster(): void {
    this.productsLoading = true;
    this.data.get('master/product.php?type=getProductsLog').subscribe({
      next: (res: any) => {
        this.productsLoading = false;
        this.products = Array.isArray(res) ? res : [];
        this.syncSelectedProductFromFields();
      },
      error: () => {
        this.productsLoading = false;
        this.products = [];
        this.showBanner('err', 'Could not load product list from product master.');
      },
    });
  }

  productCode(p: any): string {
    if (!p) {
      return '';
    }
    const c = p.product_code != null && String(p.product_code).trim() !== '' ? p.product_code : p.product_code1;
    return c != null ? String(c).trim() : '';
  }

  get filteredProducts(): any[] {
    const q = (this.productFilter || '').trim().toLowerCase();
    if (!q) {
      return this.products;
    }
    return this.products.filter((p) => {
      const code = this.productCode(p).toLowerCase();
      const name = String(p.product_name || '').toLowerCase();
      return code.includes(q) || name.includes(q);
    });
  }

  onProductSelected(p: any | null): void {
    this.selectedProduct = p;
    if (!p) {
      this.product_code = '';
      this.product_name = '';
      return;
    }
    this.product_code = this.productCode(p);
    this.product_name = (p.product_name && String(p.product_name).trim()) || '';
  }

  private syncSelectedProductFromFields(): void {
    const code = (this.product_code || '').trim();
    if (!code || !this.products.length) {
      if (!code) {
        this.selectedProduct = null;
      }
      return;
    }
    const found = this.products.find((p) => this.productCode(p) === code);
    this.selectedProduct = found || null;
  }

  compareProducts = (a: any, b: any): boolean => {
    if (a === b) {
      return true;
    }
    if (!a || !b) {
      return false;
    }
    return this.productCode(a) === this.productCode(b);
  };

  private normalizeTree(stages: EbmrBprStage[]): EbmrBprStage[] {
    for (const st of stages) {
      if (!Array.isArray(st.Steps)) {
        st.Steps = [];
      }
      for (const tp of st.Steps) {
        if (!Array.isArray(tp.Substeps)) {
          tp.Substeps = [];
        }
      }
    }
    return stages;
  }

  private showBanner(kind: 'ok' | 'err' | 'info', text: string): void {
    this.banner = { kind, text };
    window.setTimeout(() => {
      if (this.banner?.text === text) {
        this.banner = null;
      }
    }, 8000);
  }

  refreshList(): void {
    this.busy = true;
    this.api.listMasters(this.department).subscribe({
      next: (res: any) => {
        this.busy = false;
        this.masters = res?.status === 'ok' && Array.isArray(res.masters) ? res.masters : [];
      },
      error: () => {
        this.busy = false;
        this.showBanner('err', 'Could not load saved masters.');
      },
    });
  }

  loadTemplate(): void {
    this.tree = this.normalizeTree(ebmrBprTemplateFor(this.department));
    this.syncRawFromTree();
    this.showBanner('info', `Loaded ${this.deptLabel} template (local — save to persist).`);
  }

  loadFromServer(): void {
    const pc = (this.product_code || '').trim();
    if (!pc) {
      this.showBanner('err', 'Select a product to load.');
      return;
    }
    this.busy = true;
    this.api.getMaster(pc, this.department).subscribe({
      next: (res: any) => {
        this.busy = false;
        if (res?.status === 'not_found') {
          this.tree = [];
          this.revision = null;
          this.updated_at = null;
          this.syncRawFromTree();
          this.showBanner('info', 'No saved master — start from template or add stages.');
          return;
        }
        if (res?.status !== 'ok') {
          this.showBanner('err', res?.msg || 'Load failed');
          return;
        }
        this.product_name = res.product_name || this.product_name;
        this.syncSelectedProductFromFields();
        this.tree = this.normalizeTree(Array.isArray(res.Stages) ? res.Stages : []);
        this.revision = res.revision ?? null;
        this.updated_at = res.updated_at ?? null;
        this.syncRawFromTree();
        this.showBanner('ok', 'Loaded revision ' + (this.revision ?? '-') + '.');
      },
      error: () => {
        this.busy = false;
        this.showBanner('err', 'Could not load master from server.');
      },
    });
  }

  saveToServer(): void {
    const pc = (this.product_code || '').trim();
    if (!pc) {
      this.showBanner('err', 'Product code is required to save.');
      return;
    }
    if (this.editMode === 'json' && !this.applyRawJson()) {
      return;
    }
    this.busy = true;
    this.api
      .saveMaster({
        product_code: pc,
        product_name: (this.product_name || '').trim(),
        department: this.department,
        Stages: this.tree,
      })
      .subscribe({
        next: (res: any) => {
          this.busy = false;
          if (res?.status !== 'ok') {
            this.showBanner('err', res?.msg || 'Save failed');
            return;
          }
          this.revision = res.revision ?? null;
          this.updated_at = res.updated_at ?? null;
          this.refreshList();
          this.showBanner('ok', 'Saved — revision ' + (this.revision ?? '-') + '.');
        },
        error: () => {
          this.busy = false;
          this.showBanner('err', 'Could not save master.');
        },
      });
  }

  addStage(): void {
    this.tree.push({ stages: 'New stage', Steps: [] });
    this.syncRawFromTree();
  }

  removeStage(si: number): void {
    this.tree.splice(si, 1);
    this.syncRawFromTree();
  }

  addStep(si: number): void {
    this.tree[si].Steps.push({ id: String(this.nextStepId()), step: '', Substeps: [] });
    this.syncRawFromTree();
  }

  removeStep(si: number, ti: number): void {
    this.tree[si].Steps.splice(ti, 1);
    this.syncRawFromTree();
  }

  addSubstep(si: number, ti: number): void {
    const step = this.tree[si].Steps[ti];
    step.Substeps.push({ id: String(this.nextSubstepId(step)), substep: '' });
    this.syncRawFromTree();
  }

  removeSubstep(si: number, ti: number, ui: number): void {
    this.tree[si].Steps[ti].Substeps.splice(ui, 1);
    this.syncRawFromTree();
  }

  onTreeFieldChange(): void {
    this.syncRawFromTree();
  }

  setEditMode(m: 'tree' | 'json'): void {
    if (m === 'json' && this.editMode === 'tree') {
      this.syncRawFromTree();
    }
    if (m === 'tree' && this.editMode === 'json') {
      this.applyRawJson();
    }
    this.editMode = m;
  }

  syncRawFromTree(): void {
    try {
      this.rawJson = JSON.stringify({ Stages: this.tree }, null, 2);
    } catch {
      this.rawJson = '';
    }
  }

  applyRawJson(): boolean {
    try {
      const p = JSON.parse(this.rawJson);
      const stages = p?.Stages;
      if (!Array.isArray(stages)) {
        this.showBanner('err', 'JSON must contain a Stages array.');
        return false;
      }
      this.tree = this.normalizeTree(stages as EbmrBprStage[]);
      this.showBanner('ok', 'Parsed JSON into tree.');
      return true;
    } catch {
      this.showBanner('err', 'Invalid JSON.');
      return false;
    }
  }

  applyQuickSelect(row: EbmrBprMasterListRow): void {
    this.product_code = row.product_code || '';
    this.product_name = row.product_name || '';
    this.syncSelectedProductFromFields();
    this.loadFromServer();
  }

  closeToHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/ebmr-bpr');
  }

  private nextStepId(): number {
    let max = 0;
    for (const st of this.tree) {
      for (const s of st.Steps || []) {
        const n = parseInt(s.id, 10);
        if (!isNaN(n) && n > max) {
          max = n;
        }
      }
    }
    return max + 1;
  }

  private nextSubstepId(step: EbmrBprStep): number {
    let max = 0;
    for (const u of step.Substeps || []) {
      const n = parseInt(u.id, 10);
      if (!isNaN(n) && n > max) {
        max = n;
      }
    }
    return max + 1;
  }

  trackStage(_i: number, st: EbmrBprStage): string {
    return (st?.stages || '') + '_' + _i;
  }

  trackStep(_i: number, st: EbmrBprStep): string {
    return (st?.id || '') + '_' + _i;
  }

  trackSub(_i: number, u: EbmrBprSubstep): string {
    return (u?.id || '') + '_' + _i;
  }
}
