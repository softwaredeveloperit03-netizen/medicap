import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;


@Component({
  selector: 'app-new-vendor',
  templateUrl: './new-vendor.component.html',
  styleUrls: ['./new-vendor.component.css'],
})
export class NewVendorComponent implements OnInit {
  vendors;
  vendor_doc;
  isDIGI: boolean;
  vendor: any;
  date: any;
  file: any;
  structureFile: File;
  selectedResult = [];

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.getVendors();
    // this.getVendors1();
    // this.getDocumentsallDoc();
    // this.get_rights();
  }

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

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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

  openDigiSign(): void {
    this.isDIGI = true;
  }
  vendor_no = '';
  checklistData ;

  getVendors() {
    this.service
      .get('master/checklist.php?type=gevendorAssesmentChecklist')
      .subscribe((response) => {
        this.checklistData = response;
      });
  }




  getVendors1() {
    this.service
      .get('purchase/vendor.php?type=getVendors1')
      .subscribe((response) => {
        this.vendor_doc = response;
      });
  }
  documents;
  getDocumentsByType(value) {
    this.service
      .get('purchase/vendor.php?type=getDocumentsByType&doc_type=' + value)
      .subscribe((response) => {
        this.documents = response;
      });
  }
  alldocuments
  getDocumentsallDoc() {
    this.service
      .get('purchase/vendor.php?type=getDocumentsallDoc')
      .subscribe((response) => {
        this.alldocuments = response;
      });
  }
  selected_vendor;
  getselectedVendor(index) {
    this.selected_vendor = this.vendors[index - 1];
    this.vendor_no = this.selected_vendor['vendor_no'];
    
  }

  getvendorData(index) {
    // this.getvendorData = this.mapped_materials[index - 1];
    console.log(this.getvendorData);
  }

  onFileChange(event) {
    this.structureFile = event.target.files[0];
  }
  addVendor(date, document_name, vendor_type, i) {
    const temp = {};
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.structureFile !== undefined) {
      uploadData.append('photo', this.structureFile, this.structureFile.name);
    }

    uploadData.append('valid_date', date);
    uploadData.append('vendor_no', this.vendor_no);
    uploadData.append('document_name', document_name);
    uploadData.append('vendor_type', vendor_type);

    this.service
      .post('purchase/vendor.php?type=saveVendorDocument', uploadData)
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success(' saved successfully');

          this.documents[i].button = 1;
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
  isView=false;
  view(index) {
    this.selectedResult = this.alldocuments[index];
    // console.log(this.selectedResult['id']);
    // let Url = 'https://paperlessgmp.in/php/upload/vendor_document/' + file;
    // window.open(Url, '_blank');
    this.isView=true;
  }


  viewFiles(file){
 let Url = 'https://paperlessgmp.in/php/upload/vendor_document/' + file;
    window.open(Url, '_blank');
  }




  AddDocToVendor(data): void {
    let temp = data.value;
    temp['data'] = this.documents;;
    this.service
      .post('purchase/vendor.php?type=addDocumentsToVendor', JSON.stringify(temp))
      .subscribe(
        (response) => {
          console.log('Response from server:', response);
          if (response['status'] == 'success') {
            this.isDIGI = false;
            alertify.success('Added Successfully');
            data.reset();
          } else {
            alert('An error occurred');
          }
        }
 
      );
    this.isDIGI = false;
   
  }














}
