import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { FormBuilder, FormArray, FormGroup, FormControl, ValidatorFn } from '@angular/forms';
declare let alertify;
@Component({
  selector: 'app-checker',
  templateUrl: './checker.component.html',
  styleUrls: ['./checker.component.css']
})
export class CheckerComponent implements OnInit {
  isView = false;
  results;

  selectedReport= [];
  remark = '';
  departments;
  assessments;
  incident_status='';
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getPendingIncidents();
    this.getDepartments();
    this.getAssessment();
  }
  getAssessment(){
    this.assessments=[
      {impact_assessment:'SOP/Formats',value:false},
      {impact_assessment:'Specification/MOA/AWR',value:false},
      {impact_assessment:'Stability',value:false},
      {impact_assessment:'Validation/Qualification',value:false},
      {impact_assessment:'Process(MFC/BMR/LMR)',value:false},
      {impact_assessment:'Drawing',value:false},
      {impact_assessment:'Facility/Utility',value:false},
      {impact_assessment:'System',value:false},
      {impact_assessment:'GMP Document',value:false},
      {impact_assessment:'Equipment/Instrument',value:false}
    ]
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

  getPendingIncidents() {
    this.service.get('qms/incident.php?type=getPendingIncidents').subscribe(response => {
      this.results = response;
    });
  }

  viewIncident(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }


 
  updateDept(value, i) {
    this.departments[i].status = value;
  }
  update(value, i) {
    this.assessments[i].status = value;
  }



  save(data) {
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }
    let assets = [];
    for (let i = 0; i < this.assessments.length; i++) {
      let assessment = this.assessments[i];
      if (assessment['status']) {
        assets[assets.length] = assessment['impact_assessment'];
      }
    }
    temp['departments'] = test;
    temp['assessments'] = assets;
    this.service.post('qms/incident.php?type=incidentChecking&incident_no='+this.selectedReport['incident_no'], JSON.stringify(temp)).subscribe(response=>{
      if (response['status'] === 'success') {
        data.resetForm();
        this.router.navigate(['/qms/incidents']);
        alertify.success('Successfully Saved');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    })
  }

}
