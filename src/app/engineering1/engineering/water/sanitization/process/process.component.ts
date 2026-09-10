import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import {DatePipe} from '@angular/common';
@Component({
  selector: 'app-process',
  templateUrl: './process.component.html',
  styleUrls: ['./process.component.css'],
  providers:[DatePipe]
})
export class ProcessComponent implements OnInit {

  results: any = [];
  selectedResults=[];
  start = '';
  stop = '';
  results1;
  tank_names
  tank_name;
  tanks;
  labours;
  employees;
  name;
  sanitization_frequency;
  capacity;
  persons;
  isView = false;

  descriptions = [{"description": "Maintain minimum 60 cm level in storage tank & stop DM generation. ","status": "", "time_from":"", "time_to":""},
  {"description": "Check the operation of spray ball from manhole ","status": "", "time_from":"", "time_to":""},
  {"description": "Open the steam line valve and circulate steam in jacket of storage tank.","status": "", "time_from":"", "time_to":""},
  {"description": "Heat water by maintaining temperature 75-80 °C and pressure NLT 0.5 Kg/CM2 in return line and circulate for 60-90 min.","status": "", "time_from":"", "time_to":""},
  {"description": "Return line temperature of Loop: 01","status": "", "time_from":"", "time_to":""},
  {"description": "Return line temperature of Loop: 02.","status": "", "time_from":"", "time_to":""},
  {"description": "Return line temperature of Loop: 03.","status": "", "time_from":"", "time_to":""},
  {"description": "Return line temperature of Loop: 04.","status": "", "time_from":"", "time_to":""},
];
 constructor(private service: DataAccessService,private datePipe :DatePipe, private router: Router) { }

  ngOnInit(): void {
    this.getAwaitingSanitizationProcess();
    this.getLabours();
    this.getPersons();
  }
  view(index){
    this.selectedResults = this.results[index];
  this.isView = true;
  }
  getAwaitingSanitizationProcess() {
    this.service.get('engineering/watertank.php?type=getAwaitingSanitizationProcess').subscribe(response => {
      this.results = response;
    })
  }
  getPersons(){
    this.service.get('employee.php?type=getEngineeringPersons').subscribe(response=>{
      this.persons=response;
    })
  }
  startTime() {
    this.start = this.datePipe.transform(Date.now(), 'HH:mm:ss');
    console.log(this.start);
  }
  stopTime() {
    this.stop = this.datePipe.transform(Date.now(), 'HH:mm:ss');
    console.log(this.stop);
  }

  getLabours() {
    this.service.get('common.php?type=getOperators').subscribe(response => {
      this.labours = response;
    });
  }
  
  saveTankSanitization(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=[];
    temp=data.value;
    temp['checklist']=this.descriptions;
    temp['tank_id']=this.selectedResults['tank_id'];
    this.service.post('engineering/watertank.php?type=saveTankSanitization', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        this.router.navigate(['/engineering/water']);
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
