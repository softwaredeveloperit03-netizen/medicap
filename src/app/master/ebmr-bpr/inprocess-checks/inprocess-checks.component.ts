import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-inprocess-checks',
  templateUrl: './inprocess-checks.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './inprocess-checks.component.css'],
})
export class InprocessChecksComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  dosageFilter = '';
  search = '';

  modalOpen = false;
  editing = false;
  form: any = {};

  checkTypes = [
    { value: 'numeric', label: 'Numeric (target ± tolerance)' },
    { value: 'range', label: 'Range (min – max)' },
    { value: 'min', label: 'Minimum (NLT)' },
    { value: 'max', label: 'Maximum (NMT)' },
    { value: 'selection', label: 'Selection (dropdown options)' },
    { value: 'boolean', label: 'Pass / Fail (Yes / No)' },
    { value: 'text', label: 'Descriptive (free text)' },
  ];

  constructor(private service: DataAccessService, private router: Router, private esign: EsignService) {}

  ngOnInit(): void {
    this.loadDosageForms();
    this.load();
  }

  loadDosageForms(): void {
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => {
      this.dosageForms = Array.isArray(r) ? r : [];
    });
  }

  load(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getInprocessChecks';
    if (this.dosageFilter) {
      url += '&dosage_form=' + encodeURIComponent(this.dosageFilter);
    }
    this.service.get(url).subscribe({
      next: (r: any) => {
        this.rows = Array.isArray(r) ? r : [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load in-process checks');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.check_code, r.check_name, r.stage_ref, r.uom].join(' ').toLowerCase().includes(q)
    );
  }

  typeLabel(v: string): string {
    return (this.checkTypes.find((t) => t.value === v) || { label: v }).label;
  }

  limitSummary(r: any): string {
    switch (r.check_type) {
      case 'numeric':
        return `${r.target_value || '—'}${r.uom ? ' ' + r.uom : ''}${r.tolerance ? ' ± ' + r.tolerance : ''}`;
      case 'range':
        return `${r.min_limit || '—'} to ${r.max_limit || '—'}${r.uom ? ' ' + r.uom : ''}`;
      case 'min':
        return `NLT ${r.min_limit || '—'}${r.uom ? ' ' + r.uom : ''}`;
      case 'max':
        return `NMT ${r.max_limit || '—'}${r.uom ? ' ' + r.uom : ''}`;
      case 'selection':
        return (this.parseOptions(r.options_json) || []).join(', ') || '—';
      case 'boolean':
        return 'Pass / Fail';
      default:
        return 'Descriptive';
    }
  }

  parseOptions(json: any): string[] {
    if (!json) return [];
    if (Array.isArray(json)) return json;
    try {
      const v = JSON.parse(json);
      return Array.isArray(v) ? v : [];
    } catch {
      return [];
    }
  }

  newRow(): void {
    this.editing = false;
    this.form = {
      check_name: '',
      dosage_form: this.dosageFilter || '',
      stage_ref: '',
      check_type: 'numeric',
      uom: '',
      target_value: '',
      min_limit: '',
      max_limit: '',
      tolerance: '',
      options: [''],
      frequency: '',
      responsibility: 'Production',
      sampling_plan: '',
      instrument: '',
      acceptance_criteria: '',
      is_critical: 'No',
      remarks: '',
    };
    this.modalOpen = true;
  }

  editRow(row: any): void {
    this.editing = true;
    this.form = { ...row, options: this.parseOptions(row.options_json) };
    if (!this.form.options.length) this.form.options = [''];
    if (!this.form.responsibility) this.form.responsibility = 'Production';
    this.modalOpen = true;
  }

  addOption(): void {
    if (!this.form.options) this.form.options = [];
    this.form.options.push('');
  }
  removeOption(i: number): void {
    this.form.options.splice(i, 1);
    if (!this.form.options.length) this.form.options = [''];
  }
  trackByIndex(i: number): number {
    return i;
  }

  save(): void {
    const err = this.validate();
    if (err) {
      alertify.error(err);
      return;
    }
    const payload: any = { ...this.form };
    payload.options_json = (this.form.options || []).filter((o: string) => o && o.trim() !== '');
    delete payload.options;
    const url = this.editing
      ? 'master/ebmr_bpr.php?type=updateInprocessCheck&id=' + this.form.id
      : 'master/ebmr_bpr.php?type=saveInprocessCheck';
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:inprocess_check', detail: 'In-process check: ' + (this.form.check_name || ''), recordRef: this.form.id || '' })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(payload)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success(this.editing ? 'Check updated' : 'Check added');
            this.modalOpen = false;
            this.load();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }

  private validate(): string {
    const f = this.form;
    if (!f.check_name || !f.check_name.trim()) return 'Check name is required';
    if (!f.frequency || !f.frequency.trim()) return 'Frequency of checking is required';
    const num = (v: any) => v !== '' && v !== null && v !== undefined && !isNaN(parseFloat(v));
    switch (f.check_type) {
      case 'numeric':
        if (!num(f.target_value)) return 'Target value is required for a numeric check';
        break;
      case 'range':
        if (!num(f.min_limit) || !num(f.max_limit)) return 'Min and max limits are required for a range check';
        if (parseFloat(f.min_limit) >= parseFloat(f.max_limit)) return 'Min limit must be less than max limit';
        break;
      case 'min':
        if (!num(f.min_limit)) return 'Minimum limit is required';
        break;
      case 'max':
        if (!num(f.max_limit)) return 'Maximum limit is required';
        break;
      case 'selection': {
        const opts = (f.options || []).filter((o: string) => o && o.trim() !== '');
        if (opts.length < 2) return 'Provide at least two selection options';
        break;
      }
    }
    return '';
  }

  remove(row: any): void {
    alertify.confirm('Delete Check', `Delete in-process check "${row.check_name}"?`, () => {
      this.service.get('master/ebmr_bpr.php?type=deleteInprocessCheck&id=' + row.id).subscribe((r: any) => {
        if (r && r.status === 'success') {
          alertify.success('Deleted');
          this.load();
        }
      });
    }, () => {});
  }

  close(): void {
    this.router.navigate(['/master/ebmr-bpr']);
  }
}
