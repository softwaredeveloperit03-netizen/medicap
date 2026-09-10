import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

export interface DisplayDocument {
  srNo: number;
  documentName: string;
  documentNo: string;
  effectiveDate: string;
  versionNo: string;
  filePath?: string;
}

@Component({
  selector: 'app-display-documents',
  templateUrl: './display-documents.component.html',
  styleUrls: ['./display-documents.component.css'],
})
export class DisplayDocumentsComponent implements OnInit {
  activeTab: 'upload' | 'log' = 'upload';
  showNewForm = false;

  form = {
    documentName: '',
    documentNo: '',
    effectiveDate: '',
    versionNo: '',
  };
  selectedFiles: File[] = [];

  logList: DisplayDocument[] = [];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadLog();
  }

  setTab(tab: 'upload' | 'log'): void {
    this.activeTab = tab;
  }

  openNewForm(): void {
    this.showNewForm = true;
    this.form = { documentName: '', documentNo: '', effectiveDate: '', versionNo: '' };
    this.selectedFiles = [];
  }

  cancelNewForm(): void {
    this.showNewForm = false;
    this.selectedFiles = [];
  }

  onFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length) {
      this.selectedFiles = Array.from(input.files);
    }
  }

  removeFile(index: number): void {
    this.selectedFiles.splice(index, 1);
  }

  uploadDocuments(): void {
    if (!this.form.documentName?.trim() || !this.form.documentNo?.trim()) {
      alertify.error('Document Name and Document No are required.');
      return;
    }
    if (!this.selectedFiles.length) {
      alertify.error('Please select at least one file to upload.');
      return;
    }

    const formData = new FormData();
    formData.append('document_name', this.form.documentName.trim());
    formData.append('document_no', this.form.documentNo.trim());
    formData.append('effective_date', this.form.effectiveDate || '');
    formData.append('version_no', this.form.versionNo || '');
    this.selectedFiles.forEach((f, i) => formData.append('file_' + i, f));

    this.service.post('qa/display_documents.php?type=upload', formData).subscribe({
      next: (res: any) => {
        if (res && (res.status === 'success' || res.status === true)) {
          alertify.success('Document(s) uploaded successfully.');
          this.cancelNewForm();
          this.loadLog();
        } else {
          alertify.error(res?.message || 'Upload failed.');
        }
      },
      error: () => {
        this.addToLogLocal();
      },
    });
  }

  private addToLogLocal(): void {
    const nextSr = (this.logList.length && Math.max(...this.logList.map((d) => d.srNo))) + 1 || 1;
    this.logList = [
      ...this.logList,
      {
        srNo: nextSr,
        documentName: this.form.documentName,
        documentNo: this.form.documentNo,
        effectiveDate: this.form.effectiveDate || '—',
        versionNo: this.form.versionNo || '—',
      },
    ];
    alertify.success('Document(s) added to log.');
    this.cancelNewForm();
  }

  loadLog(): void {
    this.service.get('qa/display_documents.php?type=getLog').subscribe({
      next: (res: any) => {
        if (Array.isArray(res)) {
          this.logList = res.map((r: any, i: number) => ({
            srNo: i + 1,
            documentName: r.document_name || r.documentName || '—',
            documentNo: r.document_no || r.documentNo || '—',
            effectiveDate: r.effective_date || r.effectiveDate || '—',
            versionNo: r.version_no || r.versionNo || '—',
            filePath: r.file_path || r.filePath,
          }));
        }
      },
      error: () => {
        if (!this.logList.length) {
          this.logList = [];
        }
      },
    });
  }

  viewDocument(doc: DisplayDocument): void {
    if (doc.filePath) {
      const url = this.service.url + (doc.filePath.startsWith('/') ? doc.filePath : '/' + doc.filePath);
      window.open(url, '_blank');
    } else {
      alertify.message('No file link available.');
    }
  }
}
