import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  isView = false;
  results;
  capas=[];
  selectedFile: File;
  isUpload = 0;
  selectedReport= [];
  remark = '';
  departments;
  origins;
  incident_status='';

  plant_id:any;
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getPendingIncidents();
    this.getAssessment();
    this.plant_id = this.service.getPlantConfigFields('plant_id');

  }
  getAssessment(){
    this.origins=[
      {origin:'Customer Complaints',value:false},
      {origin:'Product Failures',value:false},
      {origin:'OOS Results',value:false},
      {origin:'Recalls',value:false},
      {origin:'Deviations',value:false},
      {origin:'Stability Study',value:false},
      {origin:'Customer and Regulatory audit and findings',value:false},
      {origin:'Other Investigation',value:false},
      {origin:'Trends from Process performance and product quality monitoring',value:false},
      {origin:'Management review meeting',value:false}
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

  add(data){
    this.capas[this.capas.length]=data.value;
    data.reset();
  }
  del(index){
    this.capas.splice(index,1);

  }
  update(value, i) {
    this.origins[i].status = value;
  }


  onFileChanged(event) {
    if (event.target.files == 0) {
      this.isUpload = 0;
    } else {
      this.selectedFile = event.target.files[0];
      this.isUpload = 1;
    }
  }

  save(data) {
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

    if (this.isUpload === 1) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
      console.log(uploadData)
    } else {
      if (temp['document'] == 'Yes') {
        alertify.error('document file is Compulsory');
        return;
      }
    }
    
    let origin1 = [];
    for (let i = 0; i < this.origins.length; i++) {
      let origin = this.origins[i];
      if (origin['status']) {
        origin1[origin1.length] = origin['origin'];
      }
    }
    uploadData.append('origin',JSON.stringify(origin1));
    uploadData.append('plan',JSON.stringify(this.capas));
    this.service.post('/qms/capa.php?type=saveCAPA',uploadData).subscribe(response=>{
      if (response['status'] === 'success') {
        data.resetForm();
        this.router.navigate(['/qa/capa']);
        alertify.success('Successfully Saved');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    })
  }

}
