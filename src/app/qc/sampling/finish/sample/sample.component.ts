import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-sample',
  templateUrl: './sample.component.html',
  styleUrls: ['./sample.component.css']
})
export class SampleComponent implements OnInit {

  isNew = false;
  results;

  selectedSampling = [];
  laminars;
  identication_qty=0;
  composite_qty=0;
  reserve_composite=0;
  actual_composite=0;
  isStart = false;
  /* start_time = ''; */
  start_date;
  stop_date;
  laf_start_date;
  laf_stop_date;
  actual_indentification=0;
  isStop = false;
  isStopLAF = false;
  withdrawal_identication=0;
  withdrawal_composite=0;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingSamplingForm();
  }

  getPendingSamplingForm() {
    this.service.get('qc/sampling/finish.php?type=getPendingSamplingForm').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    if (this.selectedSampling['specification_no'] == '') {
      alertify.error('Specification Not Available');
    } else {
      this.isNew = true;
      // this.getLaminars();
    }
  }

  calculation(){
     this.actual_indentification=this.identication_qty*2;
     this.actual_composite=this.composite_qty*2;
     ////////withdrea Identification cal
     console.log(this.selectedSampling['containers']);
     this.withdrawal_identication= +(this.actual_indentification / this.selectedSampling['containers']).toFixed(2);
  ///////////(composite + reserve) / no. of containers:
    this.withdrawal_composite= +((this.actual_composite +this.reserve_composite) / this.selectedSampling['containers']).toFixed(2);
  }

  getLaminars() {
    this.service.get('qc/sampling.php?type=getLaminars').subscribe(response => {
      this.laminars = response;
    });
  }


  saveSamplingInfo(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    let flag = 0;
    let containers = this.selectedSampling['container_details'];
    for (let i = 0; i < containers.length; i++) {
      if (containers[i].status == 'pending') {
        flag = 1;
        break;
      }
    }

    if (flag == 1) {
      alertify.error('All containers sample required');
      return;
    }

    temp['unit'] = this.selectedSampling['unit'];
    temp['containers'] = this.selectedSampling['container_details'];
    this.service.post('qc/sampling/finish.php?type=saveSamplingInfo&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Information saved successfully');
        this.isNew = false;
        this.getPendingSamplingForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  updateContainer(index) {
    let containers = this.selectedSampling['container_details'];
    containers[index].status = 'done';
    this.selectedSampling['container_details'] = containers;
  }

  updateSamplingTime(value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if (value == 'start') {
      /* this.start_time = h + ':' + m; */
      this.start_date = new Date();
      this.isStart = true;
    } else if (value == 'stop') {
      this.stop_date = new Date();
      this.isStop = true;
    } else if (value == 'stoplaf') {
      this.laf_stop_date = new Date();
      this.isStopLAF = true;
    }
  }

}
