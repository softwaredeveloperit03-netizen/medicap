import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isView=false;
  results;
  selectedResult=[];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingExternalTrainers();
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  getPendingExternalTrainers(){
    this.service.get('qa/trainer.php?type=getPendingExternalTrainers').subscribe(response=>{
      this.results=response;
    });
  }

  pdf(value) {
    if (value == 'certificate') {
      window.open(this.service.url + this.selectedResult['certificate']);
    }
  }
  pdfResume(value) {
    if (value == 'resume') {
      window.open(this.service.url + this.selectedResult['resume']);
    }
  }
  updateExternalTrainer(status) {
    this.service.get('qa/trainer.php?type=updateExternalTrainer&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Updated successfully');
        this.isView = false;
        this.getPendingExternalTrainers();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

 

}
