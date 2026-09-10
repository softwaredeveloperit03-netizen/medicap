import { Component, Input } from '@angular/core';

export interface DocumentRow {
  srNo: number;
  documentName: string;
  documentNo: string;
  effectiveDate: string;
  versionNo: string;
  viewDocumentUrl?: string;
}

@Component({
  selector: 'app-floating-docs-popup',
  templateUrl: './floating-docs-popup.component.html',
  styleUrls: ['./floating-docs-popup.component.css'],
})
export class FloatingDocsPopupComponent {
  /** When false, hides Software Flow button (default true for other modules). */
  @Input() showSoftwareFlow = true;
  /** When false, hides Operation Guide button (default true for other modules). */
  @Input() showOperationGuide = true;

  sopModalOpen = false;
  flowModalOpen = false;
  operationGuideModalOpen = false;

  sopDocuments: DocumentRow[] = [
    { srNo: 1, documentName: 'SOP Example', documentNo: 'SOP-001', effectiveDate: '01-Jan-2025', versionNo: '1.0', viewDocumentUrl: '#' },
  ];
  flowDocuments: DocumentRow[] = [
    { srNo: 1, documentName: 'Software Flow Example', documentNo: 'FLOW-001', effectiveDate: '01-Jan-2025', versionNo: '1.0', viewDocumentUrl: '#' },
  ];
  operationGuideDocuments: DocumentRow[] = [
    { srNo: 1, documentName: 'Operation Guide Example', documentNo: 'OG-001', effectiveDate: '01-Jan-2025', versionNo: '1.0', viewDocumentUrl: '#' },
  ];

  openSopModal(): void { this.sopModalOpen = true; }
  openFlowModal(): void { this.flowModalOpen = true; }
  openOperationGuideModal(): void { this.operationGuideModalOpen = true; }

  closeSopModal(): void { this.sopModalOpen = false; }
  closeFlowModal(): void { this.flowModalOpen = false; }
  closeOperationGuideModal(): void { this.operationGuideModalOpen = false; }

  viewDocument(row: DocumentRow): void {
    if (row.viewDocumentUrl && row.viewDocumentUrl !== '#') {
      window.open(row.viewDocumentUrl, '_blank');
    }
  }
}
