import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-qareview',
  templateUrl: './qareview.component.html',
  styleUrls: ['./qareview.component.css']
})
export class QareviewComponent implements OnInit {
  isView = false;
  isNew = false;
  results;
 
  
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getInprocessCapa();    
  }
 evaluation=[
  {'item':'Does it have any impact on process?'},
  {'item':'Does it have any impact on quality parameter?'},
  {'item':'Does it have any impact on Stability'},
  {'item':'Does it have any impact on Validation'},
  {'item':'impact on training'},
  {'item':'Does it require uddate of registration document / intimation to regulatory authorities or customer'},
  {'item':'Does it have any impact on Safety Health and Environment?'},
  {'item':'Does it have amy impact on Software(Material Management System)'},
  {'item':'Any Other impact please specify'}
 ]
  getInprocessCapa(){
    this.service.get('qms/newIncident.php?type=getPendingIncidentsFor_qa_review&dep_name='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }
  selectedDev=[];
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
 
 
 
    viewFile1(url) {
    url = this.service.url + '../../upload/incident/' + url;
    window.open(url, '_blank');
  }
  viewFile2(url) {
    url = this.service.url + '../../upload/incident/' + url;
    window.open(url, '_blank');
  }
  viewFile3(url) {
    url = this.service.url + '../../upload/incident/' + url;
    window.open(url, '_blank');
  }
  Description_of_Immediate_Action;
Reason_Justification_of_First_Alternate_TCD;
Reason_Justification_of_Second_Alternate_tcd;
  save(status) {
    let temp={};
 
 
    temp['evaluation']=this.evaluation;
    
    
    this.service.post('qms/newIncident.php?type=incidentChecking_dept_HEAD_review&status='+status+'&id='+this.selectedDev['id'], JSON.stringify(temp)).subscribe(response => {
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
