import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-identification',
  templateUrl: './identification.component.html',
  styleUrls: ['./identification.component.css'],
})
export class IdentificationComponent implements OnInit {
  results;

  isNew = false;
  
  microscopes;
  flag = false;
  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getIdentificationCulture();
    this.getMicroscopes();
    this.get_rights();
    this.getculturesMaster();
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
          this.loggedInDept
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
  getMicroscopes() {
    this.service
      .get('equipments.php?type=getMicroscopes')
      .subscribe((response) => {
        this.microscopes = response;
      });
  }

  getIdentificationCulture() {
    this.service.get('microbiology/culture.php?type=getCultureIdentification')
      .subscribe((response) => {
        this.results = response;
      });
  }

  getculturesMaster() {
    this.service.get('microbiology/culture.php?type=getCultureMaster')
      .subscribe((response) => {
        this.cultures = response;
      });
  }

  cultures;

  download() {
    this.service.open(
      'microbiology/culture.php?type=downloadCultureIdentification'
    );
  }

  saveIdentificationCulture(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['atcc_no'] = this.selectedCulture['ATCC No.'];
    temp['feature'] = this.selectedCulture['Macroscopic Features'];
    this.service
      .post(
        'microbiology/culture.php?type=saveCultureIdentification',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isNew = false;
          this.getIdentificationCulture();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }

  selectedCulture = [];
  getCultureDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedCulture = this.cultures[index];
    }
  }

  onValueChange(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag = false;
    } else {
      this.flag = true;
    }
  }
}
