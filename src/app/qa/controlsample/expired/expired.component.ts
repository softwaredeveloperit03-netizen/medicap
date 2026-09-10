import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-expired',
  templateUrl: './expired.component.html',
  styleUrls: ['./expired.component.css'],
  providers: [DatePipe]
})
export class ExpiredComponent implements OnInit {

  entries;
  isDestruction=false;
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

  material_type = '';

  selectedResult=[];
  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
 //   this.getControlsamples();
    this.getDepartments();
    this.getDistructionDetailsById(this.cs_id);
    this.getEmployee();


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
    let temp ={};
    temp['step_name'] = this.step_name;
    this.steps[this.steps.length] = temp;
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
      alert('Updated Successfully');
      this.isView = false;
      this.isNew = false;
      this.getDistructionDetailsById(this.cs_id);
    });
  }

  saveForm(data) {
    const balance = this.selectedResult['balance'];
    if (data.value.quantity <= balance) {


      let temp = data.value;
      temp['cs_id'] = this.selectedResult['cs_id'];
      temp['steps'] = this.steps;
      
    

    this.service.post('control_sample.php?type=saveDistruction', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.steps = [];
        data.resetForm();
        this.getDistructionDetailsById(this.cs_id);
        this.isNew = false;
        this.getControlsamples(this.material_type);
        alert('Saved Successfully');
      } else if (response['status'] === 'low') {
        alert('Balance Quantity is Less than Withdrawal Quantity');
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
  } else {
    alert('Quantity Available To Destroy is' + balance );
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

  // onExpired(selected) {
  //   this.isNew = true;
  //   this.selectedCsid = selected;
  //   this.getControlsamplesByID(selected.cs_id);
  //   this.getDistructionDetailsById(selected.cs_id);
  // }

  // onDestoyed(selected) {
  //   this.isDestroyed = true;
  //   this.isNew = true;
  //   this.selectedCsid = selected;
  //   this.getControlsamplesByID(selected.cs_id);
  //   this.getDistructionDetailsById(selected.cs_id);
  // }

  onDestroyed(index) {
    this.isNew = true;
    this.selectedResult=this.entries[index];
    
  }


  getControlsamplesByID(cs_id) {
    this.service.get('control_sample.php?type=getControlsamplesByID&cs_id=' + cs_id).subscribe(response => {
      this.sample_product = response;
    });
  }

  getEmployee() {
    this.service.get('employee.php?type=getQAPersons').subscribe(response => {
      this.employee = response;
    });
  }

  getDistructionDetailsById(cs_id) {
    this.service.get('control_sample.php?type=getDistructionDetailsById&cs_id=' + cs_id).subscribe(response => {
      this.dist_entries = response;
    });
  }

  getControlsamples(value) {
    let date = new Date();
    let newdate = this.datePipe.transform(date, 'yyyy-MM-dd');
    console.log(newdate);
    this.service.get('control_sample.php?type=getControlsamples1&material_type='+value).subscribe(response => {
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
