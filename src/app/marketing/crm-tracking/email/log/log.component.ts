import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-email-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class EmailLogComponent implements OnInit {

  emails: any[] = [];
  loading = false;
  isView = false;
  selectedEmail: any = {};
  filterClassification: string = '';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getEmails();
  }

  getEmails() {
    this.loading = true;
    let url = 'marketing/crm-tracking.php?type=getEmails';
    if (this.filterClassification) {
      url += '&classification=' + this.filterClassification;
    }
    this.service.get(url).subscribe((response: any) => {
      this.emails = Array.isArray(response) ? response : [];
      this.loading = false;
    }, error => {
      console.error('Error fetching emails:', error);
      alertify.error('Error fetching email logs');
      this.loading = false;
    });
  }

  view(index: number) {
    this.selectedEmail = this.emails[index];
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedEmail = {};
  }

  refresh() {
    this.getEmails();
  }

  filter() {
    this.getEmails();
  }

  clearFilter() {
    this.filterClassification = '';
    this.getEmails();
  }

  getClassificationClass(classification: string): string {
    switch(classification) {
      case 'Domestic': return 'label label-info';
      case 'Export': return 'label label-primary';
      default: return 'label label-default';
    }
  }

  formatDateTime(dateTime: string): string {
    if (!dateTime) return 'N/A';
    const date = new Date(dateTime);
    return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
  }

  downloadPdf() {
    if (!this.emails?.length) {
      alertify.error('No records to download');
      return;
    }
    let url = 'pdf1/marketing.php?type=emailTrackingLog';
    if (this.filterClassification) {
      url += '&classification=' + encodeURIComponent(this.filterClassification);
    }
    this.service.open(url);
  }

  downloadDetailPdf() {
    const id = this.selectedEmail?.id;
    if (!id) {
      alertify.error('No record selected');
      return;
    }
    this.service.open('pdf1/marketing.php?type=emailTrackingLogDetail&id=' + encodeURIComponent(id));
  }
}
