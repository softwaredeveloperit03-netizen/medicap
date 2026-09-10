import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-rejected',
  templateUrl: './rejected.component.html',
  styleUrls: ['./rejected.component.css']
})
export class RejectedComponent implements OnInit {
  isView = false;
  results;
  isObservation=false;
  selectedReport = [];
  selectedTest=[];
  selectedTesting=[];
  error_type ='';
  isupdate =false;
  affecting_product = 'yes';
  affecting_equipment = 'no';
  departments;
  selectedincident=[];
  selecteddeviation=[];
  isDrum = false;
  isBag = false;
  isBox = false;
  isCOA = false;
  isDamage = false;
  receiveDetails=[];
  devdetails=[];
  productdetails=[];
  incidentProduct=[];
  isUpincedent=false;
  dev_departments = [
    { name: 'Store', status: false},
    { name: 'Quality Control', status: false},
    { name: 'Production', status: false},
    { name: 'Packing', status: false},
    { name: 'Engineering', status: false}
  ];
  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit(): void {
    this.getRejectedReceivings();
    this.getDepartments();
  }

  getRejectedReceivings() {
    this.service.get('store/raw.php?type=getRejectedReceivings').subscribe(response => {
      this.results = response;
    });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.receiveDetails=this.selectedReport['receiving_details'];
    console.log('rep',this.selectedReport);


     if(this.selectedReport['error_type']=='error2'){
      this.selecteddeviation = this.selectedReport['deviations'];
      this.productdetails=this.selecteddeviation['product_details'];
      console.log(this.productdetails);
    }else if(this.selectedReport['error_type']=='error1'){
      this.selectedincident = this.selectedReport['incidents'];
      this.incidentProduct=this.selectedincident['product_details'];
     console.log('tt',this.incidentProduct);
   }
    this.isView = true;
  }
  reporterror(){
    this.isObservation=true;
  }

  isupdatedetails(){
    this.isupdate=true;
  }

  isupdateincident(){
    this.isUpincedent=true;
  }
  checkContainerType(value) {
    if(value === 'Drum') {
      this.isDrum = true;
      this.isBag = false;
      this.isBox = false;
    } else if(value === 'Bag') {
      this.isDrum = false;
      this.isBag = true;
      this.isBox = false;
    } else if(value === 'Boxes') {
      this.isDrum = false;
      this.isBag = false;
      this.isBox = true;
    }
  }

  checkcoa(value) {
    if(value == 'Yes') {
      this.isCOA = false;
    } else {
      this.isCOA = true;
    }
  }
  getDepartments() {
    this.departments = [
      { department_name: 'Store', value: false},
      { department_name: 'Production', value: false},
      { department_name: 'Quality Control', value: false},
      { department_name: 'Packing', value: false},
      { department_name: 'Marketing', value: false},
      { department_name: 'Client', value: false},
      { department_name: 'Regulatory Department', value: false},
      { department_name: 'Management', value: false},
      { department_name: 'HR', value: false},
      { department_name: 'Engineering', value: false }
    ]
  }

  
  updateDept(value, i) {
    this.departments[i].status = value;
  }

  updateDevDept(value, i) {
    this.dev_departments[i].status = value;
  }

  updateProdDetails(data){
    let temp=data.value;
    this.service.post('store/raw.php?type=editRejectedReceiving&id='+ this.selectedReport['id'], JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  submitOOS(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
   let test = [];
   let test1 = [];
    let temp = data.value;
    if(this.error_type =='error1'){ 
      for (let i = 0; i < this.departments.length; i++) {
        let department = this.departments[i];
        if (department['status']) {
          test[test.length] = department['department_name'];
        }
      }
      temp['departments'] = test;
    }else if(this.error_type =='error2'){
      for (let i = 0; i < this.dev_departments.length; i++) {
        let department1 = this.dev_departments[i];
        if (department1['status']) {
          test1[test1.length] = department1['name'];
        }
      }
      temp['departments'] = test1;
    }
  
    temp['error_type']=this.error_type;
    this.service.post('store/raw.php?type=saveRejectedReceivingForm&id='+ this.selectedReport['id'], JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        this.router.navigate(['/raw/receiving']);
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
