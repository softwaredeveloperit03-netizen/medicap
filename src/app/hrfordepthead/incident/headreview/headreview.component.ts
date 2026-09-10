import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-headreview',
  templateUrl: './headreview.component.html',
  styleUrls: ['./headreview.component.css']
})
export class HeadreviewComponent implements OnInit {
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
    this.service.get('qms/newIncident.php?type=getPendingIncidentsFor_qa_Head_review&dep_name='+localStorage.getItem('department')).subscribe(response => {
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
  save1(status) {
    let temp={};
    temp['CAPA']=this.CAPA
    temp['CAPA_Impliment']=this.CAPA_Impliment
    temp['training']=this.training
    temp['Closing_date']=this.Closing_date
    temp['closing_justification']=this.closing_justification
    temp['Closure_Comments']=this.Closure_Comments 
    
    this.service.post('qms/newIncident.php?type=incidentQAHEADREVIEW&status='+status+'&id='+this.selectedDev['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('successfull');
this.isView=false;
 this.getInprocessCapa(); 
} else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

selectedFile : File;
selectedFile1 : File;
selectedFile2 : File;


save(data,status) {
  if(!data.valid){
    alertify.error('All fields are required!');
    return;
  }
  let temp = data.value;
  
  const uploadData = new FormData();

  for (let key in temp) {
    let value = temp[key];
    uploadData.append(key, value);
  }

  if (this.selectedFile !== undefined) {
    uploadData.append('document', this.selectedFile, this.selectedFile.name);
    console.log(uploadData)
  }  
  if (this.selectedFile1 !== undefined) {
    uploadData.append('document1', this.selectedFile1, this.selectedFile1.name);
    console.log(uploadData)
  }  
  if (this.selectedFile2 !== undefined) {
    uploadData.append('document2', this.selectedFile2, this.selectedFile2.name);
    console.log(uploadData)
  }  
  
  let origin1 = [];
  for (let i = 0; i < this.categories.length; i++) {
    let origin = this.categories[i];
    if (origin['status']) {
      origin1[origin1.length] = origin['origin'];
    }
  }
  uploadData.append('origin',JSON.stringify(origin1));
  // uploadData.append('CAPA',this.CAPA);
  // uploadData.append('CAPA_Impliment',this.CAPA_Impliment);
  // uploadData.append('training',this.training);
  // uploadData.append('Closing_date',this.Closing_date);
  // uploadData.append('closing_justification',this.closing_justification);
  // uploadData.append('Closure_Comments',this.Closure_Comments);
  // uploadData.append('plan',JSON.stringify(this.capas));
  this.service.post('qms/capa.php?type=saveCAPA1&status='+status+'&incident_id='+this.selectedDev['id']+'&capaFrom=Incident',uploadData).subscribe(response=>{
    if (response['status'] === 'success') {
      alert('Saved Successfully');
      // this.isView = false;
      // this.getPendingEvaluation();
      // this.saveControl(status);
       alertify.success('Successfully Saved');
    } else {
      alertify.error(this.service.t('common.errorOccurred'));
    }
  })
}
}
