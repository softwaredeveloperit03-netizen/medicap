import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-induction',
  templateUrl: './induction.component.html',
  styleUrls: ['./induction.component.css']
})
export class InductionComponent implements OnInit {

  trainings;
  isHide;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getTranings();
  }

  getTranings() {
    this.service.get('employee.php?type=getInductionTraining')
    .subscribe(response => {
      this.trainings = response;
    });
  }

  approveTraining(value) {
    this.service.get('employee.php?type=approveTraining&id=' + value)
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.getTranings();
      } else {
        alert('An error occured');
      }
    });
  }
  downloadreport(){
    this.service.open('pdf1/training.php?type=inductiontraining');
  }
}
