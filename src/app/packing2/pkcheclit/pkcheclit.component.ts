import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute, Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-pkcheclit',
  templateUrl: './pkcheclit.component.html',
  styleUrls: ['./pkcheclit.component.css']
})
export class PkcheclitComponent implements OnInit {

  selectedPage = 0;
  selectedstages:any;
  constructor(private service: DataAccessService,  private _router: Router) { }

  ngOnInit() {
    this.getadddisp_chek();
    this.GET_quality_sample_cheklist();
  
    this.getCCP2();
   
  }
  processes;
  delete_oprp_ccp2(id) {
    console.log(id)
    this.service.post('bmr/process.php?type=delete_oprp_ccp2&id='+id, JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Delete Successfully');
         this.getCCP2();
            } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  deldisp_chek(id) {
    console.log(id)
    this.service.post('bmr/process.php?type=deldisp_chek&id='+id, JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Delete Successfully');
         this.getadddisp_chek();
            } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  delquality_sample_cheklist(id) {
    console.log(id)
    this.service.post('bmr/process.php?type=delquality_sample_cheklist&id='+id, JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Delete Successfully');
         this.GET_quality_sample_cheklist();
            } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  selectPage(index, index1) {
    this.selectedPage = index;
  
     
  }

  close(){
    this._router.navigate(['/packing']);
  }
  cleanliness_hoper;
cleanliness_discharge_channel;
airpressure;
humidity;
sensitivity;
heater_working;
seal_cleanliness;
film_folds;
seal_strength;
wad_heater_working;
wad_seal_cleanliness;
wad_film_folds;
wad_seal_strength;
blender;
sifter;
checkpoint;
Cleanliness;
temp;
ccrp_Checklist;
ccp_oprp_2=[];
  saveoprpccp(data,data1){    
    let temp = data.value;
    // temp['tmep']=this.temp
    // temp['humidity']=this.humidity
    // temp['cleanliness_hoper']=this.cleanliness_hoper
    // temp['cleanliness_discharge_channel']=this.cleanliness_discharge_channel
    // temp['airpressure']=this.airpressure
    // temp['sensitivity']=this.sensitivity    
    // temp['heater_working']=this.heater_working
    // temp['seal_cleanliness']=this.seal_cleanliness
    // temp['film_folds']=this.film_folds
    // temp['seal_strength']=this.seal_strength
    // temp['wad_heater_working']=this.wad_heater_working
    // temp['wad_seal_cleanliness']=this.wad_seal_cleanliness
    // temp['wad_film_folds']=this.wad_film_folds
    // temp['wad_seal_strength']=this.wad_seal_strength
    // this.ccp_oprp_2[this.ccp_oprp_2.length ]= temp;
   
    
    this.service.post('bmr/process.php?type=saveoprpccp2', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
         this.getCCP2();
        data.reset();
        temp.length=0;
       
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  adddisp_chek(data){    
    let temp = data.value;  
  
   
    
    this.service.post('bmr/process.php?type=adddisp_chek', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
         this.getadddisp_chek();
        data.reset();
        temp.length=0;
       
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  add_quality_sample_chek(data){    
    let temp = data.value;  
  
   
    
    this.service.post('bmr/process.php?type=addquality_sample_chek', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
         this.GET_quality_sample_cheklist();
        data.reset();
        temp.length=0;
       
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  oprps;
  oprp2=[];
  checks;
  getadddisp_chek(){
    this.service.get('bmr/process.php?type=GET_adddisp_chek').subscribe(response=>{
      this.checks = response;
      this.oprp2=this.oprps['oprpccp_details'][0]
      console.log(this.oprp2);
    });
  }
  qchecks;
  GET_quality_sample_cheklist(){
    this.service.get('bmr/process.php?type=GET_quality_sample_cheklist').subscribe(response=>{
      this.qchecks = response;

    });
  }
  getCCP2(){
    this.service.get('bmr/process.php?type=GET_oprp_chek2').subscribe(response=>{
      this.oprps = response;
      this.oprp2=this.oprps['oprpccp_details'][0]
      console.log(this.oprp2);
    });
  }
}
