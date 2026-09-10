import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-impact',
  templateUrl: './impact.component.html',
  styleUrls: ['./impact.component.css']
})
export class ImpactComponent implements OnInit {

  
  constructor(private service: DataAccessService, private router: Router) {}
deptName;
  ngOnInit(): void {
    this.getDeviationForConsentAndReview();
       this.getDepartments();
       this.deptName=localStorage.getItem('department')
  }
 
 departments: any = [];
impactData: any[] = [];
selectedDepartment: string = '';
remarks: string = '';
name: string = '';
date: string = '';

 

 
selectedDepartments: any[] = [];

getDepartments() {
  this.service
    .get('hr/employee.php?type=get_department_by_designation')
    .subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
}

onDepartmentSelect(dept: any, event: any) {
  if (event.target.checked) {
    // Add selected department to array
    this.selectedDepartments.push({
      department_name: dept.department_name,
      status: 'Pending',
      remarks: '',
      name: '',
      date: '' 
    });
  } else {
    // Remove if unchecked
    this.selectedDepartments = this.selectedDepartments.filter(
      (d) => d.department_name !== dept.department_name
    );
  }
  console.log(this.selectedDepartments);
}


 
  result;
  isView = false;

  getDeviationForConsentAndReview() {
    this.service.get('pDeviation.php?type=getDeviationForConsentAndReview_For_Impact_departments&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.result = response;
      });
  }
  selectedResult = [];
initiateDepartment
initiateDate
prodMatStageDoc
batch_no
mfg_date
exp_date
deviation_type
actualProcedure
deviationObserved
rootCause
rootCauseFile
RiskAssessment
ActionTaken
impactingDepartments:any[]=[]
impactingDepartmentsFiltered: any[] = [];
  view(i){

    this.selectedResult = this.result[i];
    this.isView = true;
    this.initiateDepartment=this.selectedResult['initiateDepartment']
    this.initiateDate=this.selectedResult['initiateDate']
    this.prodMatStageDoc=this.selectedResult['prodMatStageDoc']
    this.batch_no=this.selectedResult['batch_no']
    this.mfg_date=this.selectedResult['mfg_date']
    this.exp_date=this.selectedResult['exp_date']
    this.deviation_type=this.selectedResult['deviation_type']
    this.actualProcedure=this.selectedResult['actualProcedure']
    this.deviationObserved=this.selectedResult['deviationObserved']
    this.rootCause=this.selectedResult['rootCause']
    this.rootCauseFile=this.selectedResult['rootCauseFile']
    this.RiskAssessment=this.selectedResult['RiskAssessment']
    this.ActionTaken=this.selectedResult['ActionTaken']
    this.impactingDepartments=this.selectedResult['impactDeparments']


     const empName = localStorage.getItem('emp_id'); // employee name
  const deptName = localStorage.getItem('department'); // department to filter
  const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

  // Filter departments and pre-fill name and date
  this.impactingDepartmentsFiltered = this.impactingDepartments.map(d => {
  if (d.department_name === deptName) {
    return {
      ...d,
      status: 'Completed',  // update only selected
      remarks: '',          // leave blank
      name: empName,
      date: today
    };
  } else {
    return { ...d };        // keep everything else unchanged
  }
});

 
  }

  viewDevDoc(url) {
     url = this.service.url + '../../upload/deviation/pritam/' + url;
    window.open(url, '_blank');
  }


 
 

 saveDeviation() {
let temp={}

  temp['impactDeparments']=this.impactingDepartmentsFiltered;
  temp['id']=this.selectedResult['id'];

  this.service.post('pDeviation.php?type=Update_impact_departments', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('Consent And Reviewed Saved Successfully !!!!!!');
          this.getDeviationForConsentAndReview();
         
          this.isView = false;
          this.selectedResult =[];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      }
    );
}


 



}
