import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-test-inactive',
  templateUrl: './inactive.component.html',
  styleUrls: ['./inactive.component.css']
})
export class InactiveComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getTestsLog();
  }

  results;

  getTestsLog() {
    this.service.get('master/test.php?type=getTestsByStatus&status=In-Active').subscribe((response) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  selectedResult = [];
  isView = false;

  view(id) {
    const index = this.results.findIndex((t: any) => t.id === id);
    if (index !== -1) {
      this.selectedResult = this.results[index];
    }
    this.isView = true;
  }

  changeStatus(status: string) {
    this.service.get('master/test.php?type=changeTestStatus&id=' + this.selectedResult['id'] + '&status=' + encodeURIComponent(status)).subscribe((response: any) => {
      if (response && response['status']) {
        alertify.success('Test status changed successfully');
        this.getTestsLog();
        this.isView = false;
      } else {
        alertify.error('Some error occurred');
      }
    });
  }

  searchQuery;

  formatEntryDate(val: any): string {
    if (val == null || val === '') return 'NA';
    const d = typeof val === 'string' ? new Date(val) : val;
    if (isNaN(d.getTime())) return 'NA';
    return d.toLocaleDateString();
  }

  getEntryByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.entry_by_name || result.entry_by || result.created_by || '';
    const id = result.entry_by_id || result.created_by_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  getInactivatedByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.inactivated_by_name || result.inactivated_by || '';
    const id = result.inactivated_by_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((t: any) =>
      Object.entries(t).some(([, value]) =>
        value != null && value.toString().toLowerCase().includes(query)
      )
    );
  }
}
