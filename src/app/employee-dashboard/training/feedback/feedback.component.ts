import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-feedback',
  templateUrl: './feedback.component.html',
  styleUrls: ['./feedback.component.css'],
})
export class FeedbackComponent implements OnInit {
  isView = false;
  results;

  selectedTraining = [];
  feedback_exp = [];
  branches: any;
  constructor(private service: DataAccessService) {}

  feedback = '';

  ngOnInit(): void {
    this.getCompletedTrainings();
  }

  getCompletedTrainings() {
    this.service
      .get('training.php?type=getemployeeTrainningFeedback')
      .subscribe((response) => {
        this.results = response;
      });
  }





 
  rate_exp = false;
  rate_exp1 = false;
  rate_exp2 = false;
  rate_exp3 = false;


  savefeedback(data) {

  
    let temp = data.value;

  
    this.service.post('training.php?type=saveFeedbackTraining&tid=' +this.selectedTraining['tid'], JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alert('saved Successfully');
          this.getCompletedTrainings();
          this.isView = false;
          data.reset();
          
        } else {
          alert('An error occured');
        }
      });
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }
}
