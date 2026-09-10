import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';
import {
  ProcedureParagraph,
  assignProcedureNumbers,
  blankProcedureParagraph,
  procedureHasContent,
} from '../shared/procedure.util';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-procedure-master',
  templateUrl: './procedure-master.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './procedure-master.component.css'],
})
export class ProcedureMasterComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  dosageFilter = '';
  search = '';

  modalOpen = false;
  editing = false;
  form: any = { paragraphs: [] as ProcedureParagraph[] };

  levelOptions = [
    { value: 1, label: '1.0 — Main section' },
    { value: 2, label: '1.1.0 — Sub-section' },
    { value: 3, label: '1.1.1.0 — Detail point' },
  ];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private esign: EsignService,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit(): void {
    this.load();
    this.loadDosageForms();
  }

  loadDosageForms(): void {
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => {
      this.dosageForms = Array.isArray(r) ? r : [];
    });
  }

  load(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getProcedures';
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
        alertify.error('Failed to load procedures');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.procedure_code, r.title, r.stage_ref, r.dosage_form].join(' ').toLowerCase().includes(q)
    );
  }

  get numberedParagraphs(): ReturnType<typeof assignProcedureNumbers> {
    return assignProcedureNumbers(this.form.paragraphs || []);
  }

  get previewHtml(): SafeHtml {
    const parts = assignProcedureNumbers(this.form.paragraphs || [])
      .filter((p) => procedureHasContent(p.text))
      .map((p) => `<div class="proc-prev-block"><span class="proc-prev-num">${p.number}</span><div class="proc-prev-body">${p.text}</div></div>`);
    return this.sanitizer.bypassSecurityTrustHtml(parts.join(''));
  }

  newRow(): void {
    this.editing = false;
    this.form = {
      title: '',
      dosage_form: this.dosageFilter || '',
      stage_ref: '',
      remarks: '',
      paragraphs: [blankProcedureParagraph(1)],
    };
    this.modalOpen = true;
  }

  editRow(row: any): void {
    this.editing = true;
    this.form = {
      ...row,
      paragraphs: (row.paragraphs || []).map((p: ProcedureParagraph) => ({ ...p })),
    };
    if (!this.form.paragraphs.length) {
      this.form.paragraphs = [blankProcedureParagraph(1)];
    }
    this.modalOpen = true;
  }

  addParagraph(level = 1): void {
    this.form.paragraphs.push(blankProcedureParagraph(level));
  }

  removeParagraph(i: number): void {
    this.form.paragraphs.splice(i, 1);
    if (!this.form.paragraphs.length) {
      this.form.paragraphs.push(blankProcedureParagraph(1));
    }
  }

  moveParagraph(i: number, dir: number): void {
    const j = i + dir;
    if (j < 0 || j >= this.form.paragraphs.length) return;
    const arr = this.form.paragraphs;
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }

  save(): void {
    if (!this.form.title?.trim()) {
      alertify.error('Procedure title is required');
      return;
    }
    const paragraphs = (this.form.paragraphs || []).filter((p: ProcedureParagraph) => procedureHasContent(p.text || ''));
    if (!paragraphs.length) {
      alertify.error('Add at least one paragraph');
      return;
    }
    const payload = {
      title: this.form.title,
      dosage_form: this.form.dosage_form,
      stage_ref: this.form.stage_ref,
      remarks: this.form.remarks,
      paragraphs,
    };
    const url = this.editing
      ? 'master/ebmr_bpr.php?type=updateProcedure&id=' + this.form.id
      : 'master/ebmr_bpr.php?type=saveProcedure';
    this.esign
      .request({
        meaning: this.editing ? 'Updated By' : 'Prepared By',
        module: 'master:procedure',
        recordRef: this.form.id || 0,
        detail: (this.editing ? 'Update' : 'Create') + ' procedure: ' + this.form.title,
      })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(payload)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success('Procedure saved');
            this.modalOpen = false;
            this.load();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }

  remove(row: any): void {
    alertify.confirm('Delete Procedure', 'Remove this procedure from master?', () => {
      this.service.get('master/ebmr_bpr.php?type=deleteProcedure&id=' + row.id).subscribe((r: any) => {
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
