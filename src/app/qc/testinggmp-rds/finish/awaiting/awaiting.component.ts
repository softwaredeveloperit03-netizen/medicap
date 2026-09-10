import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {
  
  isUser = false;
  isChecker = false;
  isApprover = false;
  isInit = true;
  isMoa = false;
  testings;
  units;
  selectedTesting = [];
  selectedMOA = [];

  rechecks = [];
  pendings = [];
  tests = [];

  isStart = false;
  start_date;
  end_time;
  end_date;
  isStop = false;
    emp_id: string;
    isDIGI: boolean=false
    materialForm: any;
    isbutton: boolean=true
  constructor(private service: DataAccessService, private router: Router) {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit() {
    this.getPendingTestingForms();
  }

  getPendingTestingForms() {
    this.service.get('qc/testing/raw.php?type=getPendingTestingForms_finish').subscribe(response => {
      this.testings = response;
    });
  } 

  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  viewSpecification(index) {
    this.rechecks = [];
    this.pendings = [];
    this.selectedTesting = this.testings[index];
    this.isInit = false;

    if (this.selectedTesting['spec_status'] == 'done') {
      this.tests = this.selectedTesting['tests'];

      let i = 0, j = 0;
      this.tests.forEach(element => {
        if (element['status'] == 'recheck' && element['fail_status'] == 'correction' && element['fail_form'] == 'error1') {
          this.rechecks[i] = element;
          i++;
        } else {
          this.pendings[j] = element;
          j++;
        }
      });
      this.isInit = false;
    } else {
      alertify.error(this.selectedTesting['spec_status']);
    }
  }

  viewMOA(index) {
    let moa = this.selectedTesting['tests'];
    this.selectedMOA = moa[index];
    this.isMoa = true;
    this.isInit=true;
    this.getUnits();
  }

  updateSamplingTime(value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if (value == 'start') {
      this.start_date = new Date();
      this.isStart = true;
    } else if (value == 'end') {
      var d = new Date(),
      h = (d.getHours()<10?'0':'') + d.getHours(),
      m = (d.getMinutes()<10?'0':'') + d.getMinutes();
      let time = new Date().toLocaleTimeString();
      this.end_time = h + ':' + m;
      this.end_date = new Date();
      this.isStop = true;
    }
  }

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.materialForm=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
        this.submitTest(this.materialForm)
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
  

 submitTest(data) {
    console.log(data);
    if(!data.valid){
      alertify.error('All Field are required');
      return;
    }
    let temp = data.value;
    temp['test_no'] = this.selectedMOA["id"];
    temp['testing_no'] = this.selectedMOA["testing_no"];
    
    let descriptions = this.selectedMOA['method_details'];
    for (let i = 0; i < descriptions.length; i++) {
      let description = descriptions[i];

      if (description["option"] == "chemical") {
        let chemicals = description['list'];
        for (let j = 0; j < chemicals.length; j++) {
          let chemical = chemicals[j];
          chemical['batches'] = null;
          chemicals[j] = chemical;
        }
        description['list'] = chemicals;
      }
      descriptions[i] = description;
    }
    temp['descriptions'] = descriptions;
    
    this.service.post('qc/testing/raw.php?type=saveTestingForm_finish&fg_sampling_no='+ this.selectedMOA['sampling_no']+ '&specification_no='+this.selectedTesting['specification_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getPendingTestingForms();
        // this.isMoa = false;
        // this.isInit = true;
        this.router.navigate(['qc/testing-rds/finish/awaiting']);
        alertify.success('test successfully send for approval');
        this.isbutton=true
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }
}
