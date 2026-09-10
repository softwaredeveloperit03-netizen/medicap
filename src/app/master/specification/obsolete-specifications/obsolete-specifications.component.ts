import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-obsolete-specifications',
  templateUrl: './obsolete-specifications.component.html',
  styleUrls: ['./obsolete-specifications.component.css']
})
export class ObsoleteSpecificationsComponent implements OnInit {
  loading = false;
  searchQuery = '';
  logs: any[] = [];
  viewModalOpen = false;
  selected: any = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadLogs();
  }

  loadLogs(): void {
    this.loading = true;
    this.service
      .get('qc/specification/raw.php?type=getObsoleteSpecificationLogs')
      .subscribe({
        next: (response: any) => {
          this.logs = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.logs = [];
          this.loading = false;
        }
      });
  }

  get filteredLogs(): any[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.logs;
    }
    return this.logs.filter((row: any) =>
      Object.values(row || {}).some((v) => v != null && String(v).toLowerCase().includes(q))
    );
  }

  viewRow(row: any): void {
    this.selected = row;
    this.viewModalOpen = true;
  }

  closeModal(): void {
    this.viewModalOpen = false;
    this.selected = null;
  }

  get selectedSpecData(): any {
    return this.selected?.previous_spec_data || {};
  }

  get selectedTests(): any[] {
    const list = this.selectedSpecData?.spectTests;
    return Array.isArray(list) ? list : [];
  }

  get selectedRevisions(): any[] {
    const list = this.selectedSpecData?.revisionList;
    return Array.isArray(list) ? list : [];
  }
}

