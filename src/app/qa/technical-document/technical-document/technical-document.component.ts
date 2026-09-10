import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-technical-document',
  templateUrl: './technical-document.component.html',
  styleUrls: ['./technical-document.component.css']
})
export class TechnicalDocumentComponent implements OnInit {
  formopen = false;
  clients;
  isView = false;
  isNew = false;
  entries;
  selectedEntry;
  departments;
  department_name ='';
  remark = '';
  configurations = [];
  isApprover;
  // tslint:disable-next-line: variable-name
  company = '';
  products = [];
  file: File;
  file_coa: File;
  file_moa: File;
  file_msds: File;
  file_mfr: File;
  // tslint:disable-next-line: variable-name
  enquiry_for = '';
  list;

  msds;
  file1;
  coa;
  mfr;
  moa;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getDocumentlist();
    this.getDepartments();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  updateDocument(action) {
    this.service.get('marketing.php?type=updateDocument&id=' + this.selectedEntry.id +
    '&action=' + action).subscribe(response => {
      alert('Updated Successfully');
      this.isView = false;
      this.remark = '';
      this.getDocumentlist();
    });
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.file1 = this.service.url + this.selectedEntry['file'];
    this.msds = this.service.url + this.selectedEntry['file_msds'];
    this.coa = this.service.url + this.selectedEntry['file_coa'];
    this.mfr = this.service.url + this.selectedEntry['file_mfr'];
    this.moa = this.service.url + this.selectedEntry['file_moa'];
    this.isView = true;
  }

  getDocumentlist() {
    this.service.get('marketing.php?type=getTechnicalDoc').subscribe(response => {
      this.list = response;
    });
  }

  onFileChange($event, name) {
    if (name == 'file') {
      this.file = $event.target.files[0];
    } else if (name == 'coa') {
      this.file_coa = $event.target.files[0];
    }  else if (name == 'moa') {
      this.file_moa = $event.target.files[0];
    } else if (name == 'msds') {
      this.file_msds = $event.target.files[0];
    } else if (name == 'mfr') {
      this.file_mfr = $event.target.files[0];
    }
   }

   saveForm(data) {
      const formData = new FormData();
      formData.append('product_name', data.value.product_name);
      formData.append('grade', data.value.grade);
      formData.append('file', this.file, this.file.name);
      formData.append('file_coa', this.file_coa, this.file_coa.name);
      formData.append('file_moa', this.file_moa, this.file_moa.name);
      formData.append('file_msds', this.file_msds, this.file_msds.name);
      formData.append('file_mfr', this.file_mfr, this.file_mfr.name);
      formData.append('other_document', data.value.other_document);
      formData.append('purpose', data.value.purpose);
      formData.append('department_name', data.value.department_name);

      this.service.post('marketing.php?type=AddTechnicalDoc', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getDocumentlist();
          this.isNew = false;
          alert('Saved Successfully');
        } else {
          alert('An error has occurred, please try again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      });
    }


  addclientbtn() {
    this.formopen = true;
  }
  closeclientbtn() {
    this.formopen = false;
  }

  close() {
    this.router.navigate(['/']);
  }

}
