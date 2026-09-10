import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isEquipment = true;
  equipments;
  selectedEquipment = [];

  questions = [];
  isView = false;
  results;

  selectedResult = [];


  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingTheroticalTests();
  }

  getPendingTheroticalTests() {
    this.service.get('qa/personal.php?type=getPendingTheroticalTests').subscribe(response => {
      this.equipments = response;
    });
  }

  selectEquipment(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEquipment = this.equipments[index];
    }
  }

  addQuestion(data) {
    this.questions[this.questions.length] = data.value;
  }

  view(index) {
    this.selectedEquipment = this.equipments[index];
    this.isView = true;
  }
  

  updateTheroticalTest(status) {
    this.service.get('qa/personal.php?type=updateTheroticalTest&status=' + status + '&id=' + this.selectedEquipment['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record updated successfully');
        this.isView = false;
        this.getPendingTheroticalTests();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
