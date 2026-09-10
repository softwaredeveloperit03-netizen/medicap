import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  dosages;

  /* types = [
    {"name": "instruction", "label": "General Instructions", "status": false},
    {"name": "line_clearance", "label": "Line Clearance", "status": false},
    {"name": "procedure", "label": "Procedures", "status": false},
    {"name": "equipment", "label": "Equipments", "status": false},
    {"name": "weighing", "label": "Weighing of Material", "status": false},
    {"name": "ischeck", "label": "Inprocess Checks", "status": false},
    {"name": "isenvironment", "label": "Environmental Checks", "status": false},
    {"name": "istesting", "label": "Inprocess Testing", "status": false},
    {"name": "reconciliation", "label": "Reconciliation", "status": false},
  ];

  types1 = [
    {"name": "instruction", "label": "General Instructions", "status": false},
    {"name": "line_clearance", "label": "Line Clearance", "status": false},
    {"name": "procedure", "label": "Procedures", "status": false},
    {"name": "equipment", "label": "Equipments", "status": false},
    {"name": "weighing", "label": "Weighing of Material", "status": false},
    {"name": "ischeck", "label": "Inprocess Checks", "status": false},
    {"name": "isenvironment", "label": "Environmental Checks", "status": false},
    {"name": "istesting", "label": "Inprocess Testing", "status": false},
    {"name": "reconciliation", "label": "Reconciliation", "status": false},
  ]; */

  processes = [];
  dosage_form = '';
  process_type = '';
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getDosages();
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  add(data) {
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.processes[this.processes.length] = temp;
    data.resetForm();
  }

  saveProcess() {
    for (let i = 0; i < this.processes.length; i++) {
      this.processes[i].dosage_form = this.dosage_form;
      this.processes[i].process_type = this.process_type;
    }
    this.service.post('bmr/process.php?type=saveProcess', JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Manufacturing Processes Saved Successfully');
        this.router.navigate(['/production/ebmr/process']);
        this.dosage_form = '';
        this.processes = [];
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
