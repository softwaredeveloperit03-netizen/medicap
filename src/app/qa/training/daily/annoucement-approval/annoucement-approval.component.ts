import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-annoucement-approval',
  templateUrl: './annoucement-approval.component.html',
  styleUrls: ['./annoucement-approval.component.css']
})
export class AnnoucementApprovalComponent implements OnInit {

  selectedNeed=[];
   training = [];
   isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getdailytraining();
   }

   
  getdailytraining() {
    this.service.get('training.php?type=getCheckedDailyAnnoucements').subscribe((response: any) => {
      this.training = response;
    });
  }

  

  view(index) {
    this.selectedNeed = this.training[index];
    this.isView = true;
  }
 
 
  update(status) {
    this.service.get('training.php?type=approveDailyAnnoucement&status=' + status + '&id=' + this.selectedNeed['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.getdailytraining();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
