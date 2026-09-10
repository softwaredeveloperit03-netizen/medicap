import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-document',
  templateUrl: './document.component.html',
  styleUrls: ['./document.component.css']
})
export class DocumentComponent implements OnInit {

  isView = false;
  selectedEntry: any = {};
  list: any[] = [];
  assignedDept = '';
  qaRemarks = '';
  documentFile: File;

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getDocumentlist();
  }

  viewPlan(index: number) {
    this.selectedEntry = this.list[index];
    this.assignedDept = this.selectedEntry.assigned_dept || '';
    this.qaRemarks = '';
    this.documentFile = undefined;
    this.isView = true;
  }

  assignDept() {
    if (!this.assignedDept) {
      alertify.error('Please select department');
      return;
    }
    const payload = {
      id: this.selectedEntry.id,
      assigned_dept: this.assignedDept
    };
    this.service.post('marketing/document.php?type=assignRequestDept', JSON.stringify(payload)).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Request assigned to ' + this.assignedDept);
        this.isView = false;
        this.getDocumentlist();
      } else {
        alertify.error(response['message'] || 'Assignment failed');
      }
    });
  }

  approveRequest() {
    const payload = {
      id: this.selectedEntry.id,
      qa_remarks: this.qaRemarks
    };
    this.service.post('marketing/document.php?type=approveRequestDocument', JSON.stringify(payload)).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Document approved');
        this.isView = false;
        this.getDocumentlist();
      } else {
        alertify.error(response['message'] || 'Approval failed');
      }
    });
  }

  rejectRequest() {
    const payload = {
      id: this.selectedEntry.id,
      qa_remarks: this.qaRemarks
    };
    this.service.post('marketing/document.php?type=rejectRequestDocument', JSON.stringify(payload)).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Request rejected');
        this.isView = false;
        this.getDocumentlist();
      } else {
        alertify.error(response['message'] || 'Rejection failed');
      }
    });
  }

  openUploadedFile() {
    const file = this.selectedEntry['file'];
    if (file && file !== 'NA') {
      window.open(this.service.url + '../../upload/masterDocuments/' + file, '_blank');
    }
  }

  getDocumentlist() {
    this.service.get('marketing/document.php?type=getRequestsQAHead').subscribe((response: any) => {
      this.list = response || [];
    });
  }

  getStatusClass(status: string): string {
    switch (status) {
      case 'Pending QA Head':
        return 'label-warning';
      case 'Pending QA Approval':
        return 'label-info';
      case 'Assigned to Department':
        return 'label';
      default:
        return 'label';
    }
  }

  isPendingQaHead(): boolean {
    return this.selectedEntry['status'] === 'Pending QA Head';
  }

  isPendingQaApproval(): boolean {
    return this.selectedEntry['status'] === 'Pending QA Approval';
  }
}
