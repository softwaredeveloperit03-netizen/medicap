import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  trainings;
  isView= false;
  selectedTraining=[];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getApprovedOnJobTraining();
  }

  getApprovedOnJobTraining(){
    this.service.get('qa/training.php?type=getApprovedOnJobTraining').subscribe(response => {
      this.trainings = response;
    })
  }

  view(index){
    this.selectedTraining = this.trainings[index];
    this.isView= true;

  }

}
