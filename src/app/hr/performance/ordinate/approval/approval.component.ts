import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView=false;
  selectedCheckList=[];
  results;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  updateSales(status){
    this.service.get('hr/appraisalchecklist.php?type=updateOrder=' + status + '&id=' + this.selectedCheckList['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('sales Order updated Successfully');
        this.isView = false;
        // this.getPendingSales();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  view(index) {
    this.selectedCheckList = this.results[index];
    this.isView = true;
  }

}
