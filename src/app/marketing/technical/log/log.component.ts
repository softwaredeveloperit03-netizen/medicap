import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  selectedEntry: any = {};
  list: any[] = [];

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getDocumentlist();
  }

  viewPlan(index: number) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getDocumentlist() {
    this.service.get('marketing/document.php?type=getRequests').subscribe((response: any) => {
      this.list = response || [];
    });
  }

  downloadFile(item: any) {
    const file = item && item.file;
    if (file && file !== 'NA' && item.status === 'Approved') {
      window.open(this.service.url + '../../upload/masterDocuments/' + file, '_blank');
    }
  }

  getStatusClass(status: string): string {
    switch (status) {
      case 'Pending QA Head':
        return 'label-warning';
      case 'Assigned to Department':
        return 'label';
      case 'Pending QA Approval':
        return 'label-info';
      case 'Approved':
        return 'label-success';
      case 'Rejected':
        return 'label-danger';
      default:
        return 'label';
    }
  }

  canDownload(item: any): boolean {
    return item && item.status === 'Approved' && item.file && item.file !== 'NA';
  }
}
