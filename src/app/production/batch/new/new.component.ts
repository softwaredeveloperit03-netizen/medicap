import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]
})
export class NewComponent implements OnInit {

  isView = false;
  details;
  enviornmentals;
  enviornmentes;
  heads;
  isStart = false;
  isStop = false;
  equipments;
  operators;
  start_time;
  end_time;
  yield_per = '';
  yield_qty = '';

  inprocesses;
  checkpoints = [];
  checks = [];
  constructor(private route:ActivatedRoute,private service: DataAccessService, private router: Router,public datepipe: DatePipe) { }

  ngOnInit() {
    this.route.paramMap.subscribe(params => {
      this.getStageDetails(params.get('id'));
      this.getOperators();
      this.getTemperatures();
      this.getPressures();
    });
  }

  getStageDetails(id) {
    this.service.get('production/stage.php?type=getStageDetails&id=' + id).subscribe(response => {
      this.details = response;

      /* this.checks = this.details['checks'];
      this.checkpoints = Object.keys(this.checks[0]); */

      if(this.details['ischeck'] == 'yes'){
        this.service.get('production/stage.php?type=getInprocessCheckpoints&stage=' + this.details['stage'] + '&product_code=' + this.details['product_code']).subscribe(response => {
          this.heads = response;
          this.checkpoints = this.heads['checkpoints'];
          this.getInprocessChecks();
        });
      }
      this.isView = true;
    });
  }

  getInprocessChecks(){
    this.service.get('production/inprocess.php?type=getStageInprocessCheckes&id=' + this.details['id']).subscribe(response => {
      this.inprocesses = response;
    });
  }

  getEquipments(equipment_name) {
    this.service.get('production/stage.php?type=getEquipments&equipment_name=' + equipment_name).subscribe(response => {
      this.equipments = response;
    });
  }

  getOperators() {
    this.service.get('production/stage.php?type=getOperators').subscribe(response => {
      this.operators = response;
    });
  }

  updateSamplingTime(value, index) {
    let procedures = this.details['procedures'];
    let procedure = procedures[index];
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if (value == 'start') {
      /* this.start_time = h + ':' + m;
      this.start_time = new Date().toLocaleTimeString(); */

      procedure['start_time'] = h + ':' + m;
      procedure['start_date'] = new Date().toLocaleTimeString();

      this.isStart = true;
    } else if (value == 'stop') {
      /* this.end_time = h + ':' + m;
      this.end_time = new Date().toLocaleTimeString(); */

      procedure['stop_time'] = h + ':' + m;
      procedure['stop_date'] = new Date().toLocaleTimeString();
      this.isStop = true;
    }
    procedures[index] = procedure;
    this.details['procedures'] = procedures;
  }

  saveInprocess(data){
    let temp = data.value;
    this.service.post('production/inprocess.php?type=saveInprocessCheck&id=' + this.details['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Inprocess Checks Save Successfully!');
        data.resetForm();
        this.getInprocessChecks();
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

  saveEquipment(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('production/inprocess.php?type=saveStageDetails&id=' + this.details['id'] + '&bmr_no=' + this.details['bmr_no'] + '&yield_qty=' + this.yield_qty + '&yield_per=' + this.yield_per,JSON.stringify(this.details['procedures'])).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Saved Successfully!');
        this.router.navigate(['/batch/inprocess']);
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

  saveIntimation(){
    this.service.get('production/stage.php?type=sendIntimation&stage=' + this.details['stage'] + '&bmr_no=' + this.details['bmr_no'] + '&product_code=' + this.details['product_code']).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Intimation Send Successfully!');
        this.router.navigate(['/batch/inprocess']);
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

  saveTemperature(data){
    if(!data.valid){
      alert('All fields are required!');
      return;
    }
    let temp = data.value;
    this.service.post('production/stage.php?type=saveTemperature&stage=' + this.details['stage'] + '&bmr_no=' + this.details['std_bmr_no'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.router.navigate(['/batch/inprocess']);
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

  getTemperatures(){
    this.service.get('production/stage.php?type=getTemperatures').subscribe(response => {
      this.enviornmentals = response;
    });
  }

  savePressure(data){
    if(!data.valid){
      alert('All fields are required!');
      return;
    }
    let temp = data.value;
    this.service.post('production/stage.php?type=savePressure&stage=' + this.details['stage'] + '&bmr_no=' + this.details['std_bmr_no'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.router.navigate(['/batch/inprocess']);
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

  getPressures(){
    this.service.get('production/stage.php?type=getPressures').subscribe(response => {
      this.enviornmentes = response;
    });
  }

  saveClerance(data){
    if(!data.valid){
      alert('All fields are required!');
      return;
    }
    this.service.post('production/stage.php?type=callforclearance&stage=' + this.details['stage'] + '&bmr_no=' + this.details['std_bmr_no'] + '&id=' + this.details['id'],JSON.stringify(this.details['clearances'])).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.router.navigate(['/batch/inprocess']);
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }


}
