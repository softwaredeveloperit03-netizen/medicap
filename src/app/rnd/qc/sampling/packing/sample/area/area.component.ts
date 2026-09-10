import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-area',
  templateUrl: './area.component.html',
  styleUrls: ['./area.component.css']
})
export class AreaComponent implements OnInit {
  isNew = false;
  results;

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
  }

  getPendingAreaCheckpoints() {
    this.service.get('qc/sampling/packing.php?type=getPendingAreaCheckpoints').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    if (this.selectedSampling['specification_no'] == '') {
      alertify.error('Specification Not Available');
    } else {
      this.isNew = true;
      this.getBalances();
    }
  }

  getBalances() {
    this.service.get('balance.php?type=getQCBalances').subscribe(response => {
      this.balances = response;
    });
  }

  getLAFEquipments() {
    this.service.get('equipments.php?type=getLAFEquipments').subscribe(response=> {
      this.lafs = response;
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
    if (this.selectedBalance['status'] == 'pending') {
      alertify.error('Balance Calibration pending, you can not submit form without balance calibration!');
      return;
    }
    if(this.selectedLAF['clean'] == 'no'){
      alertify.error('LAF Cleanig Status is Pending, you can not submit form without LAF cleaning status!');
      return;
    }
    let temp = data.value;
    temp['laf_start'] = this.start_date;
    this.service.post('qc/sampling/packing.php?type=saveAreaCheckpoints&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success'){
        alertify.success('Area checkpoints saved successfully');
        this.isNew = false;
        this.getPendingAreaCheckpoints();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getCurrentTime() {
    if(this.selectedLAF['clean'] == 'no'){
      alertify.error('LAF Cleanig Status is Pending, you can not start LAF without cleaning status!');
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

}
