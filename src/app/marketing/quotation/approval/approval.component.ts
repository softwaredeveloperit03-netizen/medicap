import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingQuotations();
  }

  getPendingQuotations(){
    this.service.get('marketing/quotation.php?type=getPendingQuotations').subscribe(response=>{
      this.results=response;
    });
  }

  view(data){
    this.selectedResult = data;
    this.isView = true;
  }

  update(status) {

    this.service.get('marketing/quotation.php?type=updateQuotation&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alert('Quatation Status Updated Successfully');
        this.isView = false;
        this.getPendingQuotations();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });

  }

}
