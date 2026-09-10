import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-config-master',
  templateUrl: './config-master.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './config-master.component.css'],
})
export class ConfigMasterComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  processTypes: string[] = [];

  dosageFilter = '';
  statusFilter = '';
  search = '';

  departments = ['R & D', 'Production', 'Quality Assurance', 'Quality Control', 'Regulatory Affairs', 'Engineering'];
  designations = ['Officer', 'Executive', 'Sr. Executive', 'Manager', 'Head / Manager', 'Head of Department'];
  statuses = ['Draft', 'Under Review', 'Reviewed', 'Approved', 'Rejected'];

  modalOpen = false;
  editing = false;
  form: any = {};

  bindModalOpen = false;
  bindConfig: any = null;
  boundProducts: any[] = [];
  productSearch = '';
  productResults: any[] = [];
  newBind: any = { product_type: 'Generic', product_code: '', product_name: '' };

  constructor(private service: DataAccessService, private router: Router, private esign: EsignService) {}

  ngOnInit(): void {
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => (this.dosageForms = Array.isArray(r) ? r : []));
    this.service.get('master/ebmr_bpr.php?type=getProcessTypes').subscribe((r: any) => (this.processTypes = Array.isArray(r) ? r : []));
    this.load();
  }

  load(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getConfigs';
    if (this.dosageFilter) url += '&dosage_form=' + encodeURIComponent(this.dosageFilter);
    if (this.statusFilter) url += '&status=' + encodeURIComponent(this.statusFilter);
    this.service.get(url).subscribe({
      next: (r: any) => { this.rows = Array.isArray(r) ? r : []; this.loading = false; },
      error: () => { this.loading = false; alertify.error('Failed to load BMR configurations'); },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) => [r.bmr_no, r.dosage_form, r.process_type, r.title].join(' ').toLowerCase().includes(q));
  }

  defaultMatrix(): any {
    return {
      prepared: { department: 'R & D', designation: 'Officer' },
      reviewers: [{ department: 'Production', designation: 'Head / Manager' }],
      approver: { department: 'Quality Assurance', designation: 'Head / Manager' },
      workflow: {
        checking_applicable: true,
        approval_applicable: true,
        qa_check_applicable: false,
        qa_approval_applicable: false,
      },
    };
  }

  /* ---------- form ---------- */
  newConfig(): void {
    this.editing = false;
    this.form = { dosage_form: this.dosageFilter || '', process_type: '', title: '', matrix: this.defaultMatrix() };
    this.modalOpen = true;
  }

  editConfig(row: any): void {
    this.editing = true;
    const matrix = row.matrix && row.matrix.prepared ? JSON.parse(JSON.stringify(row.matrix)) : this.defaultMatrix();
    if (!Array.isArray(matrix.reviewers) || !matrix.reviewers.length) matrix.reviewers = this.defaultMatrix().reviewers;
    if (!matrix.workflow) matrix.workflow = this.defaultMatrix().workflow;
    this.form = { ...row, matrix };
    this.modalOpen = true;
  }
  addReviewer(): void {
    this.form.matrix.reviewers.push({ department: 'Quality Assurance', designation: 'Officer' });
  }
  removeReviewer(i: number): void {
    this.form.matrix.reviewers.splice(i, 1);
  }

  private validate(): string {
    const f = this.form;
    if (!f.dosage_form) return 'Dosage form is required';
    if (!f.process_type) return 'Process type is required';
    const m = f.matrix || {};
    if (!m.prepared?.department || !m.prepared?.designation) return 'Prepared-by department & designation are required';
    if (!Array.isArray(m.reviewers) || !m.reviewers.length) return 'Add at least one reviewer';
    if (m.reviewers.some((r: any) => !r.department || !r.designation)) return 'Each reviewer needs a department & designation';
    if (!m.approver?.department || !m.approver?.designation) return 'Approver department & designation are required';
    return '';
  }

  save(): void {
    const err = this.validate();
    if (err) { alertify.error(err); return; }
    const url = this.editing ? 'master/ebmr_bpr.php?type=updateConfig&id=' + this.form.id : 'master/ebmr_bpr.php?type=saveConfig';
    const payload = {
      dosage_form: this.form.dosage_form,
      process_type: this.form.process_type,
      title: this.form.title || '',
      matrix_json: this.form.matrix,
    };
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:bmr_config', detail: (this.editing ? 'Update' : 'Create') + ' BMR configuration — ' + this.form.dosage_form, recordRef: this.form.id || '' })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(payload)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success(this.editing ? 'Configuration updated' : 'Configuration ' + (r.bmr_no || '') + ' created');
            this.modalOpen = false;
            this.load();
          } else alertify.error((r && r.message) || 'Save failed');
        });
      });
  }

  remove(row: any): void {
    alertify.confirm('Delete Configuration', `Delete BMR configuration ${row.bmr_no}? This also removes its product bindings.`, () => {
      this.service.get('master/ebmr_bpr.php?type=deleteConfig&id=' + row.id).subscribe((r: any) => {
        if (r && r.status === 'success') { alertify.success('Deleted'); this.load(); }
      });
    }, () => {});
  }

  /* ---------- product binding ---------- */
  openBind(row: any): void {
    this.bindConfig = row;
    this.newBind = { product_type: 'Generic', product_code: '', product_name: '' };
    this.productSearch = '';
    this.productResults = [];
    this.loadBound();
    this.bindModalOpen = true;
  }
  loadBound(): void {
    this.service.get('master/ebmr_bpr.php?type=getConfigProducts&id=' + this.bindConfig.id).subscribe((r: any) => {
      this.boundProducts = Array.isArray(r) ? r : [];
    });
  }
  searchProducts(): void {
    const q = this.productSearch.trim();
    if (q.length < 2) { this.productResults = []; return; }
    this.service.get('master/ebmr_bpr.php?type=getProductsForBinding&search=' + encodeURIComponent(q)).subscribe((r: any) => {
      this.productResults = Array.isArray(r) ? r : [];
    });
  }
  pickProduct(p: any): void {
    this.newBind.product_code = p.product_code;
    this.newBind.product_name = p.product_name;
    this.productResults = [];
    this.productSearch = p.product_code + ' — ' + p.product_name;
  }
  bindProduct(): void {
    if (!this.newBind.product_code) { alertify.error('Select a product from Product Master'); return; }
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:bmr_config', recordRef: this.bindConfig.id, detail: 'Bind ' + this.newBind.product_code + ' to ' + this.bindConfig.bmr_no })
      .then((sig) => {
        if (!sig) return;
        const payload = { config_id: this.bindConfig.id, product_code: this.newBind.product_code, product_name: this.newBind.product_name, product_type: this.newBind.product_type };
        this.service.post('master/ebmr_bpr.php?type=bindConfigProduct', JSON.stringify(payload)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success('Product bound');
            this.newBind = { product_type: 'Generic', product_code: '', product_name: '' };
            this.productSearch = '';
            this.loadBound();
            this.load();
          } else alertify.error((r && r.message) || 'Bind failed');
        });
      });
  }
  unbind(p: any): void {
    alertify.confirm('Unbind Product', `Remove ${p.product_code} from ${this.bindConfig.bmr_no}?`, () => {
      this.service.get('master/ebmr_bpr.php?type=unbindConfigProduct&id=' + p.id).subscribe((r: any) => {
        if (r && r.status === 'success') { alertify.success('Unbound'); this.loadBound(); this.load(); }
      });
    }, () => {});
  }

  /* ---------- helpers ---------- */
  statusClass(s: string): string {
    switch ((s || '').toLowerCase()) {
      case 'approved': return 'eb-badge-ok';
      case 'reviewed': return 'eb-badge-type';
      case 'under review': return 'eb-badge-warn';
      case 'rejected': return 'eb-badge-crit';
      default: return 'eb-badge-muted';
    }
  }
  reviewerSummary(row: any): string {
    const rv = row.matrix && row.matrix.reviewers;
    if (!Array.isArray(rv) || !rv.length) return '—';
    return rv.map((r: any) => r.department + ' (' + r.designation + ')').join(', ');
  }

  close(): void {
    this.router.navigate(['/master/ebmr-bpr']);
  }
}
