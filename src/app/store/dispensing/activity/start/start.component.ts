import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-start',
  templateUrl: './start.component.html',
  styleUrls: ['./start.component.css']
})
export class StartComponent implements OnInit {

  isView = false;
  results;
  selectedResult=[];
  laf_pressure='';
  lafs;
  selectedLAF = [];
  start_time = '';
  start_date: Date;
  constructor(private service:DataAccessService) {
   }

  ngOnInit(): void {
    this.getAcceptedRequests();
    this.getLAFEquipments();
  }

  getAcceptedRequests(){
    this.service.get('store/dispensing.php?type=getDispensingAcceptedRequests&material_type=Raw Material').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  getLAFEquipments() {
    this.service.get('equipments.php?type=getLAFEquipments').subscribe(response=> {
      this.lafs = response;
    });
  }

  getCurrentTime() {
    if (this.selectedLAF.length == 0) {
      alertify.error('Select LAF');
      return;
    }
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.start_time = h + ':' + m;
    this.start_date = new Date();
  }

  
  selectLAF(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedLAF = this.lafs[index];
    } else {
      this.selectedLAF = [];
    }
  }


  startRLAF(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }

    let temp = data.value;
    this.service.get('store/dispensing.php?type=startRLAF&id='+this.selectedResult['id']).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('RLAF start successfuly');
        this.getAcceptedRequests();
        this.isView=false;
      }else{
        alertify.error('some error occured!');
      }
    });
  }
 
}
