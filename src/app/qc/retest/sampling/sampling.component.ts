import { Component, OnInit } from '@angular/core';
import {DataAccessService} from 'src/app/data-access.service'
declare let alertify;
@Component({
  selector: 'app-sampling',
  templateUrl: './sampling.component.html',
  styleUrls: ['./sampling.component.css']
})
export class SamplingComponent implements OnInit {

  results;
  material_code='';
  material_name='';
  category='';
  grn_no='';
  selectedResult=[];
  isView = false;
  employees;
  pressure_reading = '';
  start_time = '';
  start_date: Date;
  balances;
  lafs;
  selectedSampling = [];
  selectedBalance = [];
  selectedLAF = [];
  isStart = false;
  stop_date;
  laf_start_date;
  laf_stop_date;
  isStop = false;
  isStopLAF = false;
  laminars;
  units;
  sampling_containers=0;
  sample_qty;
  containers;
  reserve_qty;
  sample_unit;
  constructor(private service :DataAccessService) {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
   }

  ngOnInit(): void {
    this.getAwaitingSamplingRetests();
    this.getLAFEquipments();
  }

  getAwaitingSamplingRetests(){
    this.service.get('qc/retest.php?type=getAwaitingSamplingRetests').subscribe(response =>{
      this.results = response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView = true;
    this.getBalances();
  }
 
  getBalances() {
    this.service.get('balance.php?type=getSamplingBalances').subscribe(response => {
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

  saveSampling(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = {
      ...data.value,
      id: this.selectedResult['id'],
      containers: this.containers,
      sampling_containers: this.sampling_containers,
      sample_qty: this.sample_qty,
      sample_unit: this.sample_unit,
      container_details: this.container_details,
      start_sampling: this.start_time,
    };
    this.service.post('qc/retest.php?type=saveRetestSampling', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Retest sampling saved successfully');
        this.isView = false;
        this.getAwaitingSamplingRetests();
      } else {
        alertify.error(response['msg'] || 'Failed: An error occured, please try again!');
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
  calculation() {
    let sampling_containers = 0;
    if (this.selectedResult['material_subtype'] == "Key Starting Material") {
      sampling_containers = +this.containers;
    } else {
      if (+this.containers > 10) {
        sampling_containers = Math.round(Math.sqrt(+this.containers)) + 1;
      } else {
        sampling_containers = +this.containers ;
      }
    }
    this.sampling_containers = sampling_containers;

    let containers = [];
    for (let i = 0; i < this.sampling_containers; i++) {
      let temp = {};
      temp['container_no'] = i + 1;
      temp['sample_qty'] = Math.round(+this.sample_qty/sampling_containers);
      temp['reserve_qty'] = Math.round(+this.reserve_qty/sampling_containers);
      temp['total_qty'] = +temp['sample_qty'] + +temp['reserve_qty'];
      temp['unit'] = this.sample_unit;
      temp['status'] = 'pending';
      containers[containers.length] = temp;
    }
    this.container_details  = containers;
    console.log(this.containers)
  }

  getLaminars() {
    this.service.get('qc/sampling.php?type=getLaminars').subscribe(response => {
      this.laminars = response;
    });
  }

   container_details=[]
  /*
  addContainer(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'Primary Packing';
    this.container_details[this.container_details.length] = temp;
    data.resetForm();
  }

  del(index) {
    this.container_details.splice(index, 1);
  }
 */
}
