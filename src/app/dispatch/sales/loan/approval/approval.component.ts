import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
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
    this.getPendingSales();
  }
  
  getPendingSales(){
    this.service.get('dispatch/sales.php?type=getPendingLoan').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  updateSales(status){
    this.service.get('dispatch/sales.php?type=updateOrder&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('sales Order updated Successfuly');
        this.isView = false;
        this.getPendingSales();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
