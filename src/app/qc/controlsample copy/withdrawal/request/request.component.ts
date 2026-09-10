import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

  isNew = false;
  entries;
  sample;
  result;
  employee;
  isApprover;
  isChecker;
  // tslint:disable-next-line: variable-name
  department_name = '';
  product_name ='';
  // tslint:disable-next-line: variable-name
  request_by_employee = '';
  departments;
  batch_no;
  batch;
  selectedCsid;
  cs_id = '';
  sample_product;
  constructor(private service: DataAccessService, private router: Router) {
   }

  ngOnInit() {
    this.getControlsamples();
    this.getControlsampleWithdrawalsbyID(this.cs_id);
    this.getDepartments();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }

    if (localStorage.getItem('checker') === 'true') {
       this.isChecker = true;
      } else {
       this.isChecker = false;
      }
  }

  getBatch() {
    this.service.get('control_sample.php?type=getBatchbyId&selectedmaterial_name=' + this.product_name).subscribe(response => {
      this.batch = response;
    });
  }

  getControlsamples() {
    this.service.get('control_sample.php?type=getControlsamples').subscribe(response => {
      this.sample = response;
    });
  }

  getControlsamplesByID(cs_id) {
    this.service.get('control_sample.php?type=getControlsamplesByID&cs_id=' + cs_id).subscribe(response => {
      this.sample_product = response;
    });
  }

  saveForm(data) {
    if (!data.valid) {
      alertify.warning('All fields are required');
      return;
    }
    const temp = data.value;
    temp['batch_no'] = this.batch_no;
    temp['cs_id'] = this.selectedCsid;
    this.service.post('control_sample.php?type=saveControlSampleWithdrawals', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        this.getControlsampleWithdrawalsbyID(this.cs_id);
        alertify.success('Successfully send for Approval');
      } else if (response['status'] === 'low') {
        alertify.message('Balance Quantity is Less than Withdrawal Quantity');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getEmployee() {
    this.employee = undefined;
    this.service.get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + this.department_name).subscribe(response => {
      this.employee = response;
    });
  }

  getControlsampleWithdrawalsbyID(cs_id) {
    this.service.get('control_sample.php?type=getControlsampleWithdrawalsbyID&cs_id=' + cs_id).subscribe(response => {
      this.result = response;
    });
  }

  getControlsampleWithdrawals() {
    this.service.get('control_sample.php?type=getControlsampleWithdrawals').subscribe(response => {
      this.entries = response;
    });
  }

  onWithdrawal(selected) {
    this.isNew = true;
    this.selectedCsid = selected.cs_id;
    this. getControlsamplesByID(selected.cs_id);
    this.getControlsampleWithdrawalsbyID(selected.cs_id);
  }

  close() {
    this.router.navigate(['/controlsample']);
  }

}
