import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-client-document-dept',
  templateUrl: './client-document-dept.component.html',
  styleUrls: ['./client-document-dept.component.css']
})
export class ClientDocumentDeptComponent implements OnInit {

  dept = 'QC';
  closeRoute = '/qc';
  list: any[] = [];
  isView = false;
  selectedEntry: any = {};
  documentFile: File;

  constructor(private service: DataAccessService, private route: ActivatedRoute) {}

  ngOnInit() {
    const data = this.route.snapshot.data;
    if (data['dept']) {
      this.dept = data['dept'];
    }
    if (data['closeRoute']) {
      this.closeRoute = data['closeRoute'];
    }
    this.getDocumentlist();
  }

  viewPlan(index: number) {
    this.selectedEntry = this.list[index];
    this.documentFile = undefined;
    this.isView = true;
  }

  onFileChanged(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length === 1) {
      this.documentFile = input.files[0];
    }
  }

  saveDocuments() {
    if (!this.documentFile) {
      alertify.error('Please upload document file');
      return;
    }
    const uploadData = new FormData();
    uploadData.append('document', this.documentFile, this.documentFile.name);
    uploadData.append('id', this.selectedEntry.id);
    uploadData.append('request_no', this.selectedEntry.request_no);

    this.service.post(
      'marketing/document.php?type=saveDocuments&id=' + this.selectedEntry.id + '&request_no=' + this.selectedEntry.request_no,
      uploadData
    ).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Document uploaded successfully');
        this.isView = false;
        this.getDocumentlist();
      } else {
        alertify.error(response['message'] || 'Upload failed, please try again');
      }
    });
  }

  getDocumentlist() {
    this.service.get('marketing/document.php?type=getRequestsByDept&dept=' + encodeURIComponent(this.dept)).subscribe((response: any) => {
      this.list = response || [];
    });
  }

  getStatusClass(status: string): string {
    if (status === 'Assigned to Department') {
      return 'label-info';
    }
    return 'label';
  }
}
