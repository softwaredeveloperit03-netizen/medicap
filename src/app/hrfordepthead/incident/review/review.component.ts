import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {
  isView = false;
  isNew = false;
  results;
 
  
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getInprocessCapa(); 
    this.getAssessment();
  }
specify_details;
needs_verify;
  getAssessment(){
    this.specify_details=[
      {origin:'Operation Suspended/Hold',value:false},
      {origin:'Status Labeled & Segregated/Covered',value:false},
      {origin:'Additional Samples Collected',value:false},
      {origin:'Activity Continued',value:false},
      {origin:'Others',value:false},
      {origin:'NA',value:false}  
    ],
   
    this.needs_verify=[
      {origin:'Area',value:false},
      {origin:'Machine',value:false},
      {origin:'Material',value:false} , 
      {origin:'Procedure',value:false},  
      {origin:'Person',value:false}  ,
      {origin:'Measurement',value:false}  ,
      {origin:'Other',value:false}  
    ]
  }



  update_specify_details(value, i) {
    this.specify_details[i].status = value;
  }

  
  update_needs_verify(value, i) {
    this.needs_verify[i].status = value;
  }
  
  getInprocessCapa(){
    this.service.get('qms/newIncident.php?type=getPendingIncidentsFor_dept_review&dep_name='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }
  selectedDev=[];
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
 
 
 
    viewFile1(url) {
    this.openIncidentDoc(url);
  }
  viewFile2(url) {
    this.openIncidentDoc(url);
  }
  viewFile3(url) {
    this.openIncidentDoc(url);
  }

  hasDoc(url): boolean {
    return !!(url && String(url).trim() && String(url).trim() !== 'NA');
  }

  openIncidentDoc(url): void {
    if (!this.hasDoc(url)) {
      alertify.error('Document not available');
      return;
    }
    window.open(this.service.url + '../../upload/incident/' + url, '_blank');
  }
  Description_of_Immediate_Action;
Reason_Justification_of_First_Alternate_TCD;
Reason_Justification_of_Second_Alternate_tcd;
  save(status) {
    let temp={};
    let specify_details1 = [];
    let needs_verify1 = [];
    for (let i = 0; i < this.specify_details.length; i++) {
      let specify_details = this.specify_details[i];
      if (specify_details['status']) {
        specify_details1[specify_details1.length] = specify_details['origin'];
      }
    }
    for (let i = 0; i < this.needs_verify.length; i++) {
      let needs_verify = this.needs_verify[i];
      if (needs_verify['status']) {
        needs_verify1[needs_verify1.length] = needs_verify['origin'];
      }
    }
    temp['specify_details']=specify_details1;
    temp['needs_verify']=needs_verify1;
    temp['Description_of_Immediate_Action']=this.Description_of_Immediate_Action;
    temp['Reason_Justification_of_First_Alternate_TCD']=this.Reason_Justification_of_First_Alternate_TCD;
    temp['Reason_Justification_of_Second_Alternate_tcd']=this.Reason_Justification_of_Second_Alternate_tcd;
    
    this.service.post('qms/newIncident.php?type=incidentChecking_dept_review&status='+status+'&id='+this.selectedDev['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('successfull');
this.isView=false;
 this.getInprocessCapa(); 
} else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
