import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-induction-training-head',
  templateUrl: './induction-training-head.component.html',
  styleUrls: ['./induction-training-head.component.css']
})
export class InductionTrainingHeadComponent implements OnInit {

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
    this.service.get('employee.php?type=approveTraining&id=' + value).subscribe(response => {
      if (response['status'] === 'success') {
        this.getTranings();
      } else {
        alert('An error occured');
      }
    });
  }
}
