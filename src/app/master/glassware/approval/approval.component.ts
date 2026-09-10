import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  reports;
  selectedReview = [];
  isView = false;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
   this.getPendingGlasswares();
  }

  getPendingGlasswares() {
    this.service.get('qc/glassware.php?type=getPendingGlasswares').subscribe(response => {
      this.reports = response;
    });
  }

  viewReviews(index) {
    this.selectedReview = this.reports[index];
    this.isView = true;
  }
  
  updateGlassware(status) {
    this.service.get('qc/glassware.php?type=updateGlassware&status=' + status + '&id=' + this.selectedReview['id']).subscribe(response =>{
      if (response['status'] == 'success') {
        alertify.success('Record Updated successfully');
        this.isView = false;
        this.getPendingGlasswares();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
   
  


}



  


