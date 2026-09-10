import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-master-vendor',
  templateUrl: './master-vendor.component.html',
  styleUrls: ['./master-vendor.component.css'],
})
export class MasterVendorComponent implements OnInit {
  isDIGI: boolean = false;
  material_type: string = '';
  vendor_type: string = '';
  checklistList: any[] = [];
  document_text: string = '';
  isMaterialTypeSelected: boolean = false;
  structureFile: File | undefined;
  results: any;
  selectedReport: any[] = [];
  applicable: string = '';
  document_name: any;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.getvendordocument();
    this.get_rights();

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

  addData(form: any): void {
 
let temp=form.value;
  this.checklistList[this.checklistList.length]=temp;
    form.resetForm();
  }

  deleteVendor(item: any): void {
    const index = this.checklistList.indexOf(item);
    if (index !== -1) {
      this.checklistList.splice(index, 1);
    }
  }

  Save(): void {
    let temp = {};
    temp['data'] = this.checklistList;;
    this.service
      .post('purchase/vendor.php?type=savedocument', JSON.stringify(temp))
      .subscribe(
        (response) => {
          console.log('Response from server:', response);
          if (response['status'] == 'success') {
            this.isDIGI = false;
            // value.resetForm();
            this.router.navigate(['/log_vendor/Master_doc']);
            alertify.success('Successfully Saved');
          } else {
            alert('An error occurred');
          }
        }
 
      );
    this.isDIGI = false;
   
  }

  onFileChange(event: any): void {
    if (event.target.files.length === 1) {
      this.structureFile = event.target.files[0];
    }

    const uploadData = new FormData();

    if (this.structureFile !== undefined) {
      uploadData.append(
        'structure_file',
        this.structureFile,
        this.structureFile.name
      );

      this.service
        .post('purchase/vendor.php?type=savedocuments', uploadData)
        .subscribe(
          (response) => {
            if (response['status'] == 'success') {
              alertify.success(this.service.t('common.savedSuccess'));
            }
          },
          (error) => {
            console.error('Error:', error);
            alertify.error('Failed: An error occurred, please try again!');
          }
        );
    }
  }

  getvendordocument(): void {
    this.service
      .get('purchase/vendor.php?type=getvendordocument')
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }

  view(index: number): void {
    this.selectedReport = this.results[index];
  }

  calculateStartSrNo(): number {
    // Implement your logic here for calculating the start serial number
    return 0;
  }
}

