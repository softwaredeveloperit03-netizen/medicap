import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';
import {
  DynamicColumn,
  DynamicRow,
  FORM_TABLE_META,
  FormTableType,
  addColumn,
  addRow,
  blankRow,
  defaultTableForType,
  ensureRowKeys,
  removeColumn,
  removeRow,
} from '../shared/dynamic-table.util';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-table-form-master',
  templateUrl: './table-form-master.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './table-form-master.component.css'],
})
export class TableFormMasterComponent implements OnInit {
  tableType: FormTableType = 'yield';
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  dosageFilter = '';
  search = '';

  modalOpen = false;
  editing = false;
  form: any = { columns: [] as DynamicColumn[], rows: [] as DynamicRow[] };

  colTypes = [
    { value: 'text', label: 'Text' },
    { value: 'number', label: 'Number' },
  ];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private esign: EsignService
  ) {}

  ngOnInit(): void {
    const t = this.route.snapshot.data['tableType'] || this.route.snapshot.queryParamMap.get('table_type');
    this.tableType = t === 'weighing' ? 'weighing' : 'yield';
    this.loadDosageForms();
    this.load();
  }

  get meta() {
    return FORM_TABLE_META[this.tableType];
  }

  get title(): string {
    return this.meta.title;
  }

  get icon(): string {
    return this.meta.icon;
  }

  get sub(): string {
    return this.meta.sub;
  }

  loadDosageForms(): void {
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => {
      this.dosageForms = Array.isArray(r) ? r : [];
    });
  }

  load(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getFormTables&table_type=' + encodeURIComponent(this.tableType);
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
        alertify.error('Failed to load table masters');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.table_code, r.title, r.stage_ref, r.dosage_form].join(' ').toLowerCase().includes(q)
    );
  }

  newRow(): void {
    this.editing = false;
    const def = defaultTableForType(this.tableType);
    this.form = {
      table_type: this.tableType,
      title: '',
      dosage_form: this.dosageFilter || '',
      stage_ref: '',
      remarks: '',
      columns: def.columns.map((c) => ({ ...c })),
      rows: def.rows.map((r) => ({ ...r })),
    };
    this.modalOpen = true;
  }

  editRow(row: any): void {
    this.editing = true;
    this.form = {
      ...row,
      table_type: this.tableType,
      columns: (row.columns || []).map((c: DynamicColumn) => ({ ...c })),
      rows: (row.rows || []).map((r: DynamicRow) => ({ ...r })),
    };
    if (!this.form.columns.length) {
      const def = defaultTableForType(this.tableType);
      this.form.columns = def.columns.map((c) => ({ ...c }));
    }
    ensureRowKeys(this.form.columns, this.form.rows);
    if (!this.form.rows.length) {
      this.form.rows = [blankRow(this.form.columns)];
    }
    this.modalOpen = true;
  }

  addFormColumn(): void {
    addColumn(this.form.columns, 'Column ' + (this.form.columns.length + 1));
    this.form.rows.forEach((row: DynamicRow) => {
      const col = this.form.columns[this.form.columns.length - 1];
      row[col.key] = '';
    });
  }

  removeFormColumn(key: string): void {
    if (this.form.columns.length <= 1) {
      alertify.warning('At least one column is required');
      return;
    }
    removeColumn(this.form.columns, this.form.rows, key);
  }

  addFormRow(): void {
    addRow(this.form.columns, this.form.rows);
  }

  removeFormRow(i: number): void {
    if (this.form.rows.length <= 1) {
      alertify.warning('At least one row is required');
      return;
    }
    removeRow(this.form.rows, i);
  }

  save(): void {
    if (!this.form.title?.trim()) {
      alertify.error('Title is required');
      return;
    }
    if (!this.form.columns?.length) {
      alertify.error('Add at least one column');
      return;
    }
    ensureRowKeys(this.form.columns, this.form.rows);
    const payload = {
      table_type: this.tableType,
      title: this.form.title,
      dosage_form: this.form.dosage_form,
      stage_ref: this.form.stage_ref,
      remarks: this.form.remarks,
      columns: this.form.columns,
      rows: this.form.rows,
    };
    const url = this.editing
      ? 'master/ebmr_bpr.php?type=updateFormTable&id=' + this.form.id
      : 'master/ebmr_bpr.php?type=saveFormTable';
    this.esign
      .request({
        meaning: this.editing ? 'Updated By' : 'Prepared By',
        module: 'master:form_table',
        recordRef: this.form.id || 0,
        detail: (this.editing ? 'Update' : 'Create') + ' ' + this.tableType + ' table: ' + this.form.title,
      })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(payload)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success('Table master saved');
            this.modalOpen = false;
            this.load();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }

  remove(row: any): void {
    alertify.confirm('Delete Table', 'Remove this table master?', () => {
      this.service.get('master/ebmr_bpr.php?type=deleteFormTable&id=' + row.id).subscribe((r: any) => {
        if (r && r.status === 'success') {
          alertify.success('Deleted');
          this.load();
        } else {
          alertify.error('Delete failed');
        }
      });
    }, () => {});
  }

  close(): void {
    this.router.navigate(['/master/ebmr-bpr/form-masters']);
  }
}
