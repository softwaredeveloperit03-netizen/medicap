import { Component, OnInit } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  parseClientServiceEntries,
  SavedServiceEntry,
} from '../client-service.helper';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  loading = false;
  clients: any[] = [];
  savedServiceEntries: SavedServiceEntry[] = [];
  

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getPendingClients();
    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe((event) => {
        if (event.urlAfterRedirects.includes('/marketing/clients/approval') && !this.isView) {
          this.getPendingClients();
        }
      });
  }

  getPendingClients() {
    this.loading = true;
    this.service.get('marketing/client.php?type=getPendingClients').subscribe({
      next: (response: any) => {
        this.clients = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.clients = [];
        this.loading = false;
        alertify.error('Failed to load pending clients');
      },
    });
  }

 

  selectedClient = [];
  view(item) {
    this.selectedClient = [];
    this.selectedClient = {...item};
    this.savedServiceEntries = parseClientServiceEntries(this.selectedClient);
    this.isView = true;
  }

  getSavedEntryProductsLabel(entry: SavedServiceEntry): string {
    return getSavedEntryProductsLabel(entry);
  }

  getDescriptionLines(entry: SavedServiceEntry): string[] {
    return getDescriptionLines(entry.descriptions);
  }

  updateClient(status: string) {
    const approvalBy = localStorage.getItem('emp_id') || localStorage.getItem('emp_name') || '';
    const approvedDate = new Date().toISOString().split('T')[0];
    const params = 'status=' + encodeURIComponent(status) + '&id=' + encodeURIComponent(this.selectedClient['id'])
      + '&approval_by=' + encodeURIComponent(approvalBy)
      + '&approved_date=' + encodeURIComponent(approvedDate);
    this.service.get('marketing/client.php?type=updatePendingClients&' + params).subscribe((response: any) => {
      if (response && response['status'] === 'success') {
        alert('Client Updated Successfully');
        this.isView = false;
        this.getPendingClients();
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    });
  }
  


}
