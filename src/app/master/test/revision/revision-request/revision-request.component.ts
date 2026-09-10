import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-revision-request',
  templateUrl: './revision-request.component.html',
  styleUrls: ['./revision-request.component.css']
})
export class RevisionRequestComponent implements OnInit {
  tests: any[] = [];
  searchQuery = '';
  loading = false;
  revisionModalOpen = false;
  selectedTest: any = null;
  revisionForm: any = { reason: '', remarks: '' };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadTests();
  }

  loadTests(): void {
    this.loading = true;
    this.service.get('master/test.php?type=getTestsLog').subscribe((response: any) => {
      this.tests = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  get filteredTests(): any[] {
    if (!this.tests.length) return [];
    if (!this.searchQuery || !this.searchQuery.trim()) return this.tests;
    const q = this.searchQuery.toLowerCase().trim();
    return this.tests.filter((t: any) =>
      Object.values(t).some(v => v != null && String(v).toLowerCase().includes(q))
    );
  }

  openRevisionModal(row: any): void {
    this.selectedTest = row;
    this.revisionForm = { reason: '', remarks: '' };
    this.revisionModalOpen = true;
  }

  closeRevisionModal(): void {
    this.revisionModalOpen = false;
    this.selectedTest = null;
  }

  submitRevisionRequest(): void {
    if (!this.revisionForm.reason || !this.selectedTest) return;
    this.service.post('master/test.php?type=revisionRequest', JSON.stringify({
      test_id: this.selectedTest.id,
      reason: this.revisionForm.reason,
      remarks: this.revisionForm.remarks
    })).subscribe(() => {
      this.closeRevisionModal();
    });
  }

  getTestStatus(result: any): string {
    if (!result || result.status == null) return 'Active';
    const s = String(result.status);
    if (s === 'In-Active') return 'In-Active';
    if (s === 'Absolute') return 'Absolute';
    return 'Active';
  }
}
