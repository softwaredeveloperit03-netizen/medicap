import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-area',
  templateUrl: './area.component.html',
  styleUrls: ['./area.component.css']
})
export class AreaComponent implements OnInit {

  isBalance = false;
  isNew = false;
  results;
  equipments
  balances;
  lafs;
  selectedSampling = [];
  selectedBalance = [];
  selectedLAF = [];
  start_time = '';
  start_date: Date;
  pressure_reading = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingAreaCheckpoints();
    this.getLAFEquipments();
    this.  getSelectedEquipments();
  }

  getPendingAreaCheckpoints() {
    // this.service.get('qc/sampling/raw.php?type=getPendingAreaCheckpoints').subscribe(response => {
      this.service.get('qc/sampling.php?type=getPendingAreaCheckpoints&for=oos').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    /* if (this.selectedSampling['current_status'] == 'Specification not Available') {
      alertify.success('Specification Not Available');
    } else {
      this.isNew = true;
      this.getBalances();
    } */
    this.isNew = true;
    this.getBalances();
    this.get_save_weight();
  }

  getBalances() {
    this.service.get('balance.php?type=getSamplingBalances').subscribe(response => {
      this.balances = response;
    });
  }

  savedWeight;
  get_save_weight() {
    this.service.get('master/master.php?type=get_save_weight').subscribe(response => {
      this.savedWeight = response;
    });
  }

selectedTrolly =[];
std_wt_dtl =[];

  SelectTrolly(index){

    this.selectedTrolly = this.savedWeight[index-1];
    this.std_wt_dtl = this.selectedTrolly['std_wt_dtl'];

  }



  chekVarience(index) {
    this.std_wt_dtl[index].variation = 
        Number((Number(this.std_wt_dtl[index].weight_description) - Number(this.std_wt_dtl[index].display)).toFixed(2));
    this.std_wt_dtl[index].variationPer = 
        Number(((((Number(this.std_wt_dtl[index].weight_description) - Number(this.std_wt_dtl[index].display)) * 100) / Number(this.std_wt_dtl[index].weight_description)).toFixed(2)));


        if(this.selectedBalance['equipment_type'] == 'Analytical /QC'){
          this.std_wt_dtl[index].criteria =  'ana';
        }else{
          this.std_wt_dtl[index].criteria =  'gen';
        }


      }







  getLAFEquipments() {
    this.lafs = [];
    this.service.get('equipments.php?type=getLAFEquipments').subscribe(response=> {
      let result:any = response ;
      result.forEach(element => {

        if(element.activity_start_date!="") this.lafs.push(element);
        
      });
      
    });
  }
  getSelectedEquipments() {
    this.service.get('equipments.php?type=getQCLAfRAF')
    .subscribe(response => {
      this.equipments = response;
    });
  }

  selectBalance(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedBalance = this.balances[index];
    } else {
      this.selectedBalance = [];
    }
  }

  selectLAF(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedLAF = this.lafs[index];
    } else {
      this.selectedLAF = [];
    }
  }

  saveArea(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.selectedBalance['status'] !== 'approve') {
      alertify.error('Balance Calibration pending, you can not submit form without balance calibration.');
      return;
    }
    let temp = data.value;
    temp['saveArea']=data.areaForm;
    this.service.post('qc/sampling/raw.php?type=saveAreaCheckpoints&for=oos&id=' + this.selectedSampling['id']+'&new_oos_id=' + this.selectedSampling['new_oos_id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Area checkpoints saved successfully');
        this.isNew = false;
        this.getPendingAreaCheckpoints();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
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

  checkTime() {
    let new_time = new Date(this.start_date.getTime() + 15*60000);
    var currentdate = new Date();
    if (new_time > currentdate) {
      alertify.error('15 min. not completed yet.');
      this.pressure_reading = '';
    }
  }

  saveCalibration(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }


    
    let temp ={};
    temp['equipment_code'] = this.selectedBalance['equipment_code'];
    temp['weights'] = this.std_wt_dtl;



    this.service.post('balance.php?type=saveDailyVerification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Balance Calibration Done Successfully!');
        this.isBalance = false;
        this.getBalances();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
