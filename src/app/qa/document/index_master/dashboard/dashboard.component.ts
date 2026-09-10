import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  
   constructor(private service: DataAccessService, private router: Router) { }
  ngOnInit() {
    this.getDocumentIndexLog();
    this.getRevisionHistory();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//

   
documents = [];
revisionHistory = [];
activeTab = 'indexLog';
isView = false;
isRevisionFormVisible = false;
selectedDocument: any = null;
selectedRevisionFile: File;
revisionFormModel = {
  revised_version: '',
  change_control_no: '',
  effective_date: '',
  next_review_date: '',
  remarks: ''
};
 

  getDocumentIndexLog() {
    this.service.get('qa/document.php?type=getDocumentIndexLog').subscribe((response) => {
        this.documents = response as any[];
      });
  }

  getRevisionHistory() {
    this.service.get('qa/document.php?type=getDocumentRevisionHistory').subscribe((response) => {
      this.revisionHistory = response as any[];
    });
  }

  openRevisionForm(item) {
    this.selectedDocument = item;
    this.isRevisionFormVisible = true;
    this.revisionFormModel = {
      revised_version: item.document_version || '',
      change_control_no: '',
      effective_date: item.effective_date || '',
      next_review_date: item.next_review_date || '',
      remarks: ''
    };
  }

  openChangeControl(item) {
    this.openRevisionForm(item);
  }

  closeRevisionForm() {
    this.isRevisionFormVisible = false;
    this.selectedDocument = null;
    this.selectedRevisionFile = undefined;
  }

  onRevisionFileChanged(event) {
    if (event.target.files.length === 1) {
      this.selectedRevisionFile = event.target.files[0];
    }
  }

  saveDocumentRevision(form) {
    if (!this.selectedDocument) {
      alertify.error('Please select document first');
      return;
    }
    if (!form.valid) {
      alertify.error('Please fill all required fields');
      return;
    }
    if (!this.selectedRevisionFile) {
      alertify.error('Please upload revised document');
      return;
    }

    const uploadData = new FormData();
    uploadData.append('revised_version', this.revisionFormModel.revised_version);
    uploadData.append('change_control_no', this.revisionFormModel.change_control_no);
    uploadData.append('effective_date', this.revisionFormModel.effective_date);
    uploadData.append('next_review_date', this.revisionFormModel.next_review_date);
    uploadData.append('remarks', this.revisionFormModel.remarks || '');
    uploadData.append('document', this.selectedRevisionFile, this.selectedRevisionFile.name);

    this.service.post(
      'qa/document.php?type=saveDocumentRevision&document_id=' + this.selectedDocument.id,
      uploadData
    ).subscribe((response) => {
      if (response['status'] === 'success') {
        alertify.success('Document revised successfully');
        this.closeRevisionForm();
        this.getDocumentIndexLog();
        this.getRevisionHistory();
        this.activeTab = 'revisionHistory';
      } else {
        alertify.error(response['status'] || 'Error occurred');
      }
    });
  }


  searchQuery;
  revisionSearchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.documents; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.documents.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

  get filteredRevisionHistory(): any[] {
    if (!this.revisionSearchQuery || this.revisionSearchQuery.trim() === '') {
      return this.revisionHistory;
    }
    const query = this.revisionSearchQuery.toLowerCase().trim();
    return this.revisionHistory.filter((item) =>
      Object.entries(item).some(([_, value]) => value && value.toString().toLowerCase().includes(query))
    );
  }












 
     
  viewDoc(url){
    if (!url) {
      alertify.error('No document uploaded');
      return;
    }
    url = this.service.url + '../../upload/documentIndex/' + url;
    window.open(url, '_blank');
   }
  
}

