import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
declare let alertify: any;

@Component({
  selector: 'app-visitors',
  templateUrl: './visitors.component.html',
  styleUrls: ['./visitors.component.css']
})
export class VisitorsComponent implements OnInit {
  results: any[] = [];
  loading = false;
  searchQuery = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getVisitorsByMettingWith();
  }
  
  /** Load visitors: only VISITOR_AT_RECEPTION status, where meetingwith = login user emp_id */
  getVisitorsByMettingWith(): void {
    this.loading = true;
    const emp_id = localStorage.getItem('emp_id') || '';
    const url = 'security/gatepass.php?type=getVisitorsByMettingWith&emp_id=' + encodeURIComponent(emp_id);
    this.service.get(url).subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  get filteredMaterials(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const q = this.searchQuery.toLowerCase().trim();
    return this.results.filter((r: any) =>
      Object.values(r).some(v => v != null && String(v).toLowerCase().includes(q))
    );
  }

  /** Mark meeting complete: update status to MEETING_COMPLETE. Entry then leaves this list. */
  visitCompllete(id: string | number): void {
    if (id == null) return;
    this.service.get('security/gatepass.php?type=visitCompllete&id=' + encodeURIComponent(String(id))).subscribe((response: any) => {
      if (response && response.status === 'success') {
        if (typeof alertify !== 'undefined') alertify.success('Meeting marked as Complete');
        this.getVisitorsByMettingWith();
      } else {
        if (typeof alertify !== 'undefined') alertify.error(response?.message || 'Failed to update status');
      }
    });
  }

  /** Start meeting: update status to MEETING_STARTED. */
  startMeeting(id: string | number): void {
    if (id == null) return;
    this.service.get('security/gatepass.php?type=startMeeting&id=' + encodeURIComponent(String(id))).subscribe((response: any) => {
      if (response && response.status === 'success') {
        if (typeof alertify !== 'undefined') alertify.success('Meeting started');
        this.getVisitorsByMettingWith();
      } else {
        if (typeof alertify !== 'undefined') alertify.error(response?.message || 'Failed to start meeting');
      }
    });
  }
 
}
