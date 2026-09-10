import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-therotical-test',
  templateUrl: './therotical-test.component.html',
  styleUrls: ['./therotical-test.component.css']
})
export class TheroticalTestComponent implements OnInit {

  isEquipment = true;
  equipments;
  selectedEquipment = [];

  questions = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getEquipments();
  }

  getEquipments() {
    this.service.get('equipments.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

  selectEquipment(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEquipment = this.equipments[index];
    }
  }

  checkType(value) {
    if (value == 'Equipment') {
      this.isEquipment = true;
    } else {
      this.isEquipment = false;
    }
  }

  addQuestion(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.questions[this.questions.length] = data.value;
    data.resetForm();
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    if (this.questions.length == 0) {
      alert('Questions are required');
      return;
    }
    let temp = data.value;
    temp['questions'] = this.questions;
    this.service.post('qa/personal.php?type=saveTheroticalTest', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.questions = [];
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
