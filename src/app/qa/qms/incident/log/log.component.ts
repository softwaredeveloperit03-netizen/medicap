import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  isNew = false;
  results;
  CAPA;
  CAPA_Impliment;
  training;
  Closing_date;
  closing_justification;
  Closure_Comments;
  categories
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getInprocessCapa();    
    this.getAssessment();
  }
  getAssessment(){
    this.categories=[
      {origin:'Deviation',value:false},
      {origin:'analytical Testing',value:false},
      {origin:'OOS',value:false},
      {origin:'OOT',value:false},
      {origin:'OOC',value:false},
      {origin:'PQR',value:false},
      {origin:'Product Complaint',value:false},
      {origin:'Recall / Mock Recall',value:false},
      {origin:'Self Inspection',value:false},
      {origin:'External Audit',value:false},
      {origin:'Internal Audit',value:false},
      {origin:'Invalid',value:false},
      {origin:'Risk Assessment',value:false},
      {origin:'Impact Assessment',value:false},
      {origin:'Change Control',value:false},
      {origin:'Non-conformance',value:false},
      {origin:'Incident',value:false},
      {origin:'Other',value:false}      
    ]
  }
  update(value, i) {
    this.categories[i].status = value;
  }
  getInprocessCapa(){
    this.service.get('qms/newIncident.php?type=getIncidentsLog&dep_name='+localStorage.getItem('department')).subscribe(response => {
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




 
}
