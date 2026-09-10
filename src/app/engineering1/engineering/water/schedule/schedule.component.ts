import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-schedule',
  templateUrl: './schedule.component.html',
  styleUrls: ['./schedule.component.css'],
  providers: [DatePipe]
})
export class ScheduleComponent implements OnInit {

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

  descriptions = [{"description": "Fill up the tank entry permission form and get approved by EHS department.","status": "",  "cleaning_by": ""},
  {"description": "Keep the status board indicating that “STORAGE TANK CLEANING WORK UNDER PROGRESS”  .","status": "",  "cleaning_by": ""},
  {"description": "Close the tank inlet valve of water.","status": "",  "cleaning_by": ""},
  {"description": "Close the tank outlet valve to the plant.","status": "",  "cleaning_by": ""},
  {"description": "Empty the tank (or) drain out water completely from the tank through drain valve or by help of pump.","status": "",  "cleaning_by": ""},
  {"description": "Clean the tank with spray of water with high pressure jet nozzle.","status": "",  "cleaning_by": ""},
  {"description": "Remove waste water from the tank by drain (or) by help of pump.","status": "",  "cleaning_by": ""},
  {"description": "Wear cap, nose mask and gloves for safety purpose.","status": "",  "cleaning_by": ""},
  {"description": "Make 5% solution of sodium hypo chloride and coat (or) spray the solution, inside the tank with the help of jet nozzle for cleaning and leave it for one hour.","status": "",  "cleaning_by": ""},
  {"description": "Scrub the inner surface of the tank thoroughly with hard nylon brush .","status": "",  "cleaning_by": ""},
  {"description": "Spray water with the help of jet nozzle for cleaning, after scrubbing.","status": "",  "cleaning_by": ""},
  {"description": "Again suck (or) drain out waste water from the tank.","status": "",  "cleaning_by": ""},
  {"description": "Again, spray water twice with the help of jet nozzle for rinsing, after cleaning..","status": "",  "cleaning_by": ""},
  {"description": "Again suck (or) drain out waste water from the tank.","status": "",  "cleaning_by": ""},
  {"description": "Ensure the tank is clean, if not repeat the cleaning procedure.  After cleaning, close the drain valve and open the tank inlet valve.","status": "",  "cleaning_by": ""},
  {"description": "Fill the tank with water up to bottom level (or) one fourth of the tank. Take approx. 1000 ml water sample in a clean glass beaker and check for clarity visually .","status": "",  "cleaning_by": ""},
  {"description": "If the water is clean and clear, release it for use in the plant by filling the tank further and by opening the outlet valve. If water does not meet clarity requirement repeat the cleaning procedure.","status": "",  "cleaning_by": ""}];
  constructor(private service: DataAccessService, private datePipe: DatePipe, private router: Router) { }

  ngOnInit(): void {
    this.getAwaitingCleaningProcess();
    this.getLabours();
    this.getPersons();
  }
  view(index){
    this.selectedResults = this.results[index];
  this.isView = true;
  }
  getAwaitingCleaningProcess() {
    this.service.get('engineering/watertank.php?type=getAwaitingCleaningProcess').subscribe(response => {
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
    temp['descriptions']=this.descriptions;
    this.service.post('engineering/watertank.php?type=saveTankSanitization', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        this.router.navigate(['/engineering/water'])
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
