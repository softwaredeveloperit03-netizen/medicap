import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-expired',
  templateUrl: './expired.component.html',
  styleUrls: ['./expired.component.css'],
  providers: [DatePipe]
})
export class ExpiredComponent implements OnInit {

  entries;
  dist_entries;
  isNew = false;
  isView = false;
  isDestroyed = false;
  steps = [];
  step_name = '';
  selectedEntry;
  remark ='';
  employee;
  department_name = '';
  request_by_employee = '';
  departments;
  product_name = '';
  dosage_form = '';
  dosages;
  products;
  batch;
  batch_no ='';
  sample;
  material_name;
  isApprover;
  isChecker;
  selectedCsid;
  sample_product;
  cs_id;
  fromdate;
  todate;
  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getControlsamples();
    this.getDepartments();
    this.getDistructionDetailsById(this.cs_id);

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

  addSteps() {
    this.steps[this.steps.length] = this.step_name;
    this.step_name = '';
  }

  deleteSteps(index) {
    this.steps.splice(index, 1);
  }

  viewEntry(index) {
    this.selectedEntry = this.dist_entries[index];
    this.isView = true;
  }

  updateDistruction(action) {
    this.service.get('control_sample.php?type=updateDistruction&id=' + this.selectedEntry.id +
    '&action=' + action).subscribe(response => {
      alertify.success(this.service.t('common.updatedSuccess'));
      this.isView = false;
      this.isNew = false;
      this.getDistructionDetailsById(this.cs_id);
    });
  }

  saveForm(data) {
    const balance = this.selectedCsid.balance;
    if (data.value.quantity <= balance) {

    const formData = new FormData();
    formData.append('cs_id', this.selectedCsid.cs_id);
    formData.append('product_name', data.value.product_name);
    formData.append('batch_no', data.value.batch_no);
    formData.append('quantity', data.value.quantity);
    formData.append('department_name', data.value.department_name);
    formData.append('request_by_employee', data.value.request_by_employee);
    formData.append('equipment', data.value.equipment);
    formData.append('distruction_loc', data.value.distruction_loc);
    formData.append('steps', this.steps.toString());

    this.service.post('control_sample.php?type=saveDistruction', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.steps = [];
        data.resetForm();
        this.getDistructionDetailsById(this.cs_id);
        this.isNew = false;
        this.getControlsamples();
        alertify.success(this.service.t('common.savedSuccess'));
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
  } else {
    alertify.message('Quantity Available To Destroy is' + balance );
  }
  }

  getBatch() {
    this.batch = [];
    this.service.get('control_sample.php?type=getBatchbyId&selectedmaterial_name=' + this.product_name).subscribe(response => {
      this.batch = response;
    });
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  onExpired(selected) {
    this.isNew = true;
    this.selectedCsid = selected;
    this.getControlsamplesByID(selected.cs_id);
    this.getDistructionDetailsById(selected.cs_id);
  }

  onDestoyed(selected) {
    this.isDestroyed = true;
    this.isNew = true;
    this.selectedCsid = selected;
    this.getControlsamplesByID(selected.cs_id);
    this.getDistructionDetailsById(selected.cs_id);
  }

  getControlsamplesByID(cs_id) {
    this.service.get('control_sample.php?type=getControlsamplesByID&cs_id=' + cs_id).subscribe(response => {
      this.sample_product = response;
    });
  }

  getEmployee() {
    this.employee = undefined;
    this.service.get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + this.department_name).subscribe(response => {
      this.employee = response;
    });
  }

  getDistructionDetailsById(cs_id) {
    this.service.get('control_sample.php?type=getDistructionDetailsById&cs_id=' + cs_id).subscribe(response => {
      this.dist_entries = response;
    });
  }

  getControlsamples() {
    let date = new Date();
    let newdate = this.datePipe.transform(date, 'yyyy-MM-dd');
    console.log(newdate);
    this.service.get('control_sample.php?type=getControlsamples1').subscribe(response => {
      this.entries = JSON.parse(JSON.stringify(response));
      this.entries.forEach(element => {
        if(element.exp_date < newdate) {
          element.expiry = 'true';
        } else {
           element.expiry = 'false';
        }
      });
    });
  }

  close() {
    this.router.navigate(['/control-sample-dashboard']);
  }
  getprint(){
    this.service.open('pdf1/controlsample.php?type=expiredsamplelog&fromdate='+this.fromdate+'&todate='+this.todate);
  }

}
