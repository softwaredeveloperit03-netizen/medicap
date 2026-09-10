import { Component, OnInit } from '@angular/core';

import { DataAccessService } from 'src/app/data-access.service';
import { WebcamImage } from 'ngx-webcam';
import { DatePipe } from '@angular/common';

declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {
  visitorName = '';
  present_country= 'INDIA';
  isMobile = false;
  
  isNew = false;
  isPhoto = false;

  filterargs;
  employeeList: Array<string>;
  selectedCountry = '';
  meeting: string;
  employeeMasterList;
  newEmployee: Array<any>;
  results;
  category;
  departments1;
  employees;
  from_date = '';
  to_date = '';
  department_name = '';
  states;
  areas;
  cities;
  countries;
  results1 = [];
  validEmail:boolean = false
  items=[];
  // latest snapshot;
  public webcamImage: WebcamImage = null;

  handleImage(webcamImage: WebcamImage) {
    this.webcamImage = webcamImage;
  }

  constructor(private service: DataAccessService, private datepipe: DatePipe) {
    this.isMobile = this.service.isMobile;
    this.from_date = this.datepipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getGatepassDetails();
    this.getCountries();
    this.get_rights();
   // this.getArea();

    this.service.observableDepartment.subscribe(response => {
      this.departments1 = response;
    });
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

  onChange(newValue) {
    const validEmailRegEx = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    if (validEmailRegEx.test(newValue)) {
        this.validEmail = true;
    }else {
      this.validEmail = false;
    }

  }

  getState(value) {
    this.service.get('master/state.php?type=getStateBycountry&country=' + value).subscribe(response => {
      this.states = response;
    })
  }


  // getArea() {
  //   this.service.get('master/area.php?type=getArea').subscribe(response => {
  //     this.areas = response;
  //   })
  // }


  getCity(value) {
    this.service.get('master/area.php?type=getCityByStateName&state_name=' + value).subscribe(response => {
      this.cities = response;
    })
  }


  getCountries() {
    this.service.get('master/country.php?type=getCountries').subscribe(response => {
      this.countries = response;
    })
  }


  getGatepassDetails() {
    let temp = this.selectedCountry['department_name'];
    this.service.get('security/gatepass.php?type=getGatepassDetails&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + temp).subscribe(response => {
      this.results = response;
    });
    // this.filterVisitor();
  }

  number(value){
    if (isNaN(value)){
      alertify.error('Number 10 digit Only');
      return false;
    }
  }

  AllRecord(){
    this.service.get('security/gatepass.php?type=getAllGatepassDetails').subscribe((response : any) => {
      this.results = response;
    });
    this.department_name='';
  }
  
//   AllRecord(){
//     this.items =this.results;
//     this.department_name='';
    
// }
  // filterVisitor() {
  //   this.results1 = [];
  //   for (let i = 0; i < this.results.length; i++) {
  //     let material = this.results[i];
  //     if (material['visitorName'].toUpperCase().includes(this.visitorName.toUpperCase())) {
  //       this.results1[this.results1.length] = material;
  //     }
  //   }
  // }


  saveGatepass(data) {
    if (!data.valid) {
      alertify.error("all field are required");
      return;
    }
    let temp = data.value;
    this.service.post('security/gatepass.php?type=saveGatepassForm', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Gate Pass Record Added');
        data.resetForm();
        this.getGatepassDetails();
        this.isNew = false;
      } else {
        alertify.error('An error occured');
      }
    });
  }

  exitvisitor(id) {
    this.service.get('security/gatepass.php?type=exitvisitor&id=' + id).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Visitor Exited Successfully');
        this.getGatepassDetails();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  print(id) {
    this.service.open('print/gatepass.php?id=' + id);
  }

  getEmployees(value) {
    this.service.get('employee.php?type=getDeptEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }

  download() {
    this.service.open('security/gatepass.php?type=downloadLog&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department_name);
  }
  _keyPress(event: any) {
    const pattern = /[0-9]/;
    let inputChar = String.fromCharCode(event.charCode);
    if (!pattern.test(inputChar)) {
        event.preventDefault();

    }}
}


 
