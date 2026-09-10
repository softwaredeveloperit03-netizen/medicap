import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'equipment', title: 'Equipment Calibration', route: 'equipment', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'calender', title: 'Calibration Calender', route: 'calender', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  results: any = [];

  selectedResult: any = [];
  isView = false;
  from_date='';
  to_date='';
  today='';
  constructor(private service: DataAccessService,private datePipe :DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getDailyEquipments();
  }

  getDailyEquipments() {
    this.service.get('qc/calibration.php?type=getDailyEquipments&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  getCalibrationLog() {
    this.service.get('qc/calibration.php?type=getCalibrationLog&equipment_code=' + this.selectedResult['equipment_code'] + '&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.selectedResult = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  check(j) {
    let records = this.selectedResult['records'];
    let record = records[j];
    let weights = this.selectedResult['weights'];
    for (let  i = 0; i < weights.length; i++) {
      let weight = weights[i];
      if (+weight['limit_from'] <= +weight['display_weight'] && +weight['limit_to'] >= +weight['display_weight']) {
        record['status1'] = 'OK';
      } else {
        record['status1'] = 'NOT OK';
      }
      weights[i] = weight;
    }
    records[j] = record;
    this.selectedResult['records'] = records;
    this.selectedResult['weights'] = weights;
  }

  save(equipment){
    let temp={};
    temp['equipment_code']=this.selectedResult['equipment_code'];
    temp['weights']= this.selectedResult['weights'];
    temp['status1']= equipment.status1;
    temp['remark']= equipment.remark;
    temp['done_by']= equipment.done_by;
    this.service.post('/qc/calibration.php?type=saveDailyCalibration',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']==='success'){
        alertify.success("Record Save Successfully !!");
        this.isView = false;
        this.getDailyEquipments();
      }else{
        alertify.error("Error to save records !!");
      }
    });
  }

  checkby(eqp){
      this.service.get('qc/calibration.php?type=checkCalibration&id='+eqp.id+'&equipment_code='+eqp.equipment_code+'&calibration_status='+eqp.calibration_status).subscribe(function(response){
        if(response['status']==='success'){
          this.isView = false;
          this.getDailyEquipments();
          alertify.success("Record Check Successfully !!");
        }else{
          alertify.error("Error to check records !!");
        }     
      });
  }
  
  revert(id) {
    this.service.get('qc/calibration.php?type=revertCalibration&id='+  id).subscribe(response =>{
      if (response['status'] === 'success') {
        this.isView = false;
        this.getDailyEquipments();
        alertify.success('Record Rejected successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }

  downloadReport(){
    this.service.open('qc/calibration.php?type=downloadDailyEquipmentRwport&id='+this.selectedResult['id']+'&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  download(){
    this.service.open('qc/calibration.php?type=downloadDailyEquipments')
  }
}
