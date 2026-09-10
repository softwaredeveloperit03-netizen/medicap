import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-new2',
  templateUrl: './new2.component.html',
  styleUrls: ['./new2.component.css']
})
export class New2Component implements OnInit {

  phoneCalls: any[] = [];
  loading = false;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getPhoneCalls();
  }

  getPhoneCalls() {
    this.loading = true;
    this.service.get('marketing/crm-tracking.php?type=getPhoneCalls').subscribe({
      next: (response: any) => {
        this.phoneCalls = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching phone calls:', error);
        alertify.error('Error fetching phone calls');
        this.phoneCalls = [];
        this.loading = false;
      },
    });
  }

  updateCall(callId: any) {
    if (!callId) {
      alertify.error('Invalid call record');
      return;
    }
    this.router.navigate(['/marketing/crmTracking/phone/update', callId]);
  }

  refresh() {
    this.getPhoneCalls();
  }

  downloadPdf() {
    if (!this.phoneCalls.length) {
      alertify.error('No records to download');
      return;
    }
    this.service.open('pdf1/marketing.php?type=phoneCallTrackingLog');
  }

  formatDateTime(dateTime: string): string {
    if (!dateTime) return 'N/A';
    const date = new Date(dateTime);
    return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
  }

  getClassificationClass(classification: string): string {
    switch(classification) {
      case 'Domestic': return 'label label-info';
      case 'Export': return 'label label-primary';
      default: return 'label label-default';
    }
  }

  getStatusClass(status: string): string {
    switch(status) {
      case 'Completed': return 'label label-success';
      case 'Missed': return 'label label-danger';
      case 'No Answer': return 'label label-warning';
      case 'Busy': return 'label label-info';
      default: return 'label label-default';
    }
  }
}


