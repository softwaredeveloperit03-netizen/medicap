import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css']
})
export class InprocessComponent implements OnInit {

  isNew = false;
  results;
  balances;
  equipments;

  selectedIndex = -1;
  selectedBMR = [];

  instruction = '';
  clearance = '';
  procedure = '';
  inprocess = '';
  balance = '';
  equipment = '';
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getPendingMasters();
    this.getBalances();
    this.getEquipments();
  }

  getPendingMasters() {
    this.service.get('production/master.php?type=getPendingMasters').subscribe(response => {
      this.results = response;
    });
  }

  getBalances() {
    this.service.get('balance.php?type=getBalances').subscribe(response => {
      this.balances = response;
    });
  }

  getEquipments() {
    this.service.get('equipments.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

  view(index) {
    this.selectedIndex = index;
    let steps = [
      { "name": "General Instructions", "value": "instruction", "status": false, "process": "pending", "details": []},
      { "name": "Line Clearance", "value": "clearance", "status": false, "process": "pending", "details": []},
      { "name": "Balance Verification", "value": "balance", "status": false, "process": "pending", "details": []},
      { "name": "Environmental Checkes", "value": "area", "status": false, "process": "pending", "details": []},
      { "name": "Manufacturing Procedure", "value": "procedure", "status": false, "process": "pending", "details": []},
      { "name": "Equipment Cleaning Status", "value": "cleaning", "status": false, "process": "pending", "details": []},
      { "name": "Inprocess Checks", "value": "inprocess", "status": false, "process": "pending", "details": []},
    ];

    this.selectedBMR = this.results[index];
    let stages = this.selectedBMR['stages'];
    for (let i = 0; i < stages.length; i++) {
      let stage = stages[i];
      if (stage['status'] == 'pending') {
        stage['steps'] = steps;
      }
    }
    this.isNew = true;
  }

  saveSteps(index) {
    let stages = this.selectedBMR['stages'];
    let temp = stages[index];
    
    
    if (temp['status'] == 'pending') {
      temp['status'] = 'inprocess';
      let steps = temp['steps'];
      let temp1 = [];
      for (let i = 0; i < steps.length; i++) {
        if (steps[i].status == true) {
          temp1[temp1.length] = steps[i];
        }
      }
      temp1[temp1.length] = { "name": "Equipment List", "value": "equipment", "status": false, "process": "pending", "details": []};
      temp['steps'] = temp1;
      let data = this.results[this.selectedIndex];
      stages = data['stages'];
      stages[index] = temp;
      
    } else if (temp['status'] == 'inprocess') {
      temp['status'] = 'done';
      let steps = temp['steps'];
      for (let i = 0; i < steps.length; i++) {
        let step = steps[i];
        if (step['value'] == 'balance') {
          let details = step['details'];
          let test = {};
          test['balance'] = this.balance;
          test['status'] = 'pending';
          this.balance = '';
          details = test;
          step['details'] = details;
        }
        steps[i] = step;
      }
      temp['steps'] = steps;
      stages[index] = temp;

    }

    this.service.post('production/master.php?type=saveSteps&id=' + this.selectedBMR['id'], JSON.stringify(stages)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isNew = false;
        this.getPendingMasters();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  add(value, i, j) {
    let stages = this.selectedBMR['stages'];
    let stage = stages[i];
    let steps = stage['steps'];
    let step = steps[j];
    let details = step['details'];
    let temp = {};
    if (value == 'instruction') {
      temp['checkpoint'] = this.instruction;
      this.instruction = '';
    } else if (value == 'clearance') {
      temp['checkpoint'] = this.clearance;
      this.clearance = '';
    } else if (value == 'procedure') {
      temp['checkpoint'] = this.procedure;
      this.procedure = '';
    } else if (value == 'inprocess') {
      temp['checkpoint'] = this.inprocess;
      this.inprocess = '';
    } else if (value == 'equipment') {
      temp['checkpoint'] = this.equipment;
      this.equipment = '';
    }
    details[details.length] = temp;
    step['details'] = details;
    steps[j] = step;
    stage['steps'] = steps;
    stages[i] = stage;
    this.selectedBMR['stages'] = stages;
    
  }

  del(value, i, j, z) {
    let stages = this.selectedBMR['stages'];
    let stage = stages[i];
    let steps = stage['steps'];
    let step = steps[j];
    let details = step['details'];
    details.splice(z, 1);
    step['details'] = details;
    steps[j] = step;
    stage['steps'] = steps;
    stages[i] = stage;
    this.selectedBMR['stages'] = stages;
  }

}
