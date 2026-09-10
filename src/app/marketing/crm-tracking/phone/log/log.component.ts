import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-phone-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class PhoneLogComponent implements OnInit {

  calls: any[] = [];
  loading = false;
  isView = false;
  selectedCall: any = {};
  filterClassification: string = '';
  filterStatus: string = '';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getCalls();
  }

  getCalls() {
    this.loading = true;
    let url = 'marketing/crm-tracking.php?type=getPhoneCalls';
    if (this.filterClassification) {
      url += '&classification=' + this.filterClassification;
    }
    if (this.filterStatus) {
      url += '&status=' + this.filterStatus;
    }
    this.service.get(url).subscribe((response: any) => {
      this.calls = Array.isArray(response) ? response : [];
      this.loading = false;
    }, error => {
      console.error('Error fetching phone calls:', error);
      alertify.error('Error fetching phone call logs');
      this.calls = [];
      this.loading = false;
    });
  }

  downloadPdf() {
    if (!this.calls.length) {
      alertify.error('No records to download');
      return;
    }
    let url = 'pdf1/marketing.php?type=phoneCallTrackingLog&view=log';
    if (this.filterClassification) {
      url += '&classification=' + encodeURIComponent(this.filterClassification);
    }
    if (this.filterStatus) {
      url += '&status=' + encodeURIComponent(this.filterStatus);
    }
    this.service.open(url);
  }

  view(index: number) {
    this.selectedCall = this.calls[index];
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedCall = {};
  }

  refresh() {
    this.getCalls();
  }

  filter() {
    this.getCalls();
  }

  clearFilter() {
    this.filterClassification = '';
    this.filterStatus = '';
    this.getCalls();
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

  getClassificationClass(classification: string): string {
    switch(classification) {
      case 'Domestic': return 'label label-info';
      case 'Export': return 'label label-primary';
      default: return 'label label-default';
    }
  }
}


