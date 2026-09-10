import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-step',
  templateUrl: './step.component.html',
  styleUrls: ['./step.component.css']
})
export class StepComponent implements OnInit {

  isView = false;
  details;
       
  test_description = '';
  general_instruction = '';
  line_clearance = '';
  enviornmental_checks = '';
  inprocess_checks = '';


  enviornments;
  selectedDescriptions = [];
  selectedInstructions = [];
  selectedLines = [];
  selectedEnviorments = [];
  selectedInprocesses = [];
  equipements;
 

  options = [
    { "name": "Equipments / Instruments","option": "equipment", "status": false, "list": []},
    { "name": "Line Clearance","option": "line_clearance", "status": false, "list": []},
    { "name": "Enviornmental Checks","option": "enviornmental_checks", "status": false, "list": []},
    { "name": "Inprocess Checks","option": "inprocess_checks", "status": false, "list": []}
  ];

  selectedEquipment = [];
  
  inprocess_testing = false;
  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router) { }

  ngOnInit() {
    this.route.paramMap.subscribe(params => {
      this.getStageDetails(params.get('id'));
    });
    this.getEquipments();
  }

  getStageDetails(id) {
    this.service.get('production/product.php?type=getStageDetails&id=' + id).subscribe(response => {
      this.details = response;
      this.isView = true;
    });
  }

  getEquipments() {
    this.service.get('production/product.php?type=getEquipments').subscribe(response =>{
      this.equipements = response;
    });
  }
  

  addDescription() {
    if (this.test_description.length > 0) {
      let index = this.selectedDescriptions.length;
      let temp = {};
      temp['test_description'] = this.test_description;
      this.selectedDescriptions[index] = temp;
      this.test_description = '';
    }
  }

  addInstruction() {
    if (this.general_instruction.length > 0) {
      let index = this.selectedInstructions.length;
      let temp = {};
      temp['general_instruction'] = this.general_instruction;
      this.selectedInstructions[index] = temp;
      this.general_instruction = '';
    }
  }

  addLines() {
    if (this.line_clearance.length > 0) {
      let index = this.selectedLines.length;
      let temp = {};
      temp['line_clearance'] = this.line_clearance;
      this.selectedLines[index] = temp;
      this.line_clearance = '';
    }
  }

  addLineClearance(index) {
    if (this.line_clearance !== '') {
      let list = this.options[index].list;
      let temp = {};
      temp['checkpoint'] = this.line_clearance;
      list[list.length] = temp;
      this.options[index].list = list;
      this.line_clearance = '';
    }
  }

  addEnviornmentChecks(index) {
    if (this.enviornmental_checks !== '') {
      let list = this.options[index].list;
      let temp = {};
      temp['checkpoint'] = this.enviornmental_checks;
      list[list.length] = temp;
      this.options[index].list = list;
      this.enviornmental_checks = '';
    }
  }

  addInprocessChecks(index) {
    if (this.inprocess_checks !== '') {
      let list = this.options[index].list;
      let temp = {};
      temp['checkpoint'] = this.inprocess_checks;
      list[list.length] = temp;
      this.options[index].list = list;
      this.inprocess_checks = '';
    }
  }

  getEquipmentDetails(index) {
    index = index - 1;
    this.selectedEquipment = this.equipements[index];
  }

  addEquipment(index) {
    if (this.selectedEquipment.length !== 0) {
      let list = this.options[index].list;
      list[list.length] = this.selectedEquipment;
      this.selectedEquipment = [];
    }
  }

  saveStage() {
    const test = new FormData();
    
    if(this.details['stage'] == 'DISPENSING'){

      test["instruction"] = this.selectedInstructions;
      test["checkpoint"] = this.selectedLines;
    } else{

      test["name"] = "Procedure / method Description";
      test["option"] = "procedure";
      test["status"] = true;
      test["list"] = this.selectedDescriptions;
      
      test["name"] = "General Instruction";
      test["option"] = "instruction";
      test["status"] = true;
      test["list"] = this.selectedInstructions;
      

      for (let i = 0; i < this.options.length; i++) {
        let option = this.options[i];
        if (option['status'] == true) {
          if (option['list'].length == 0) {
            alert('All fields are required');
            return;
          }
        }
      }
    }

    test['inprocess_testing'] = this.inprocess_testing;
    test['instructions'] = this.selectedInstructions;
    test['procedures'] = this.selectedDescriptions;

    this.service.post('production/stage.php?type=saveStage&id=' + this.details['id'] + '&no=' + this.details['no'], JSON.stringify(test)).subscribe(response => {
      if (response['status'] === 'success') {
        alert('Test saved successfully.');
        this.router.navigate(['/master/']);
      } else {
        alert('An error Occured, Please try again!');
      }
    });
  }

}
