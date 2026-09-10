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
  salesData=[];
  product=[];
  isView=false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingInvoices();
  }


  getPendingInvoices(){
    this.service.get('dispatch/invoice.php?type=getInprocessInvoices').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.salesData=this.selectedResult['sales_data']
    for(let i=0;i<this.salesData.length;i++){
      console.log(this.salesData[i].singleProduct)
      this.product[i]=this.salesData[i].singleProduct[0]
    }
    // this.product=this.salesData['singleProduct']
    this.isView=true;
    console.log(this.product)
  }

  updateInvoice(status) {
    this.service.get('dispatch/invoice.php?type=updateInvoice&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('tax Invoice Updated Successfully');
        this.isView = false;
        this.getPendingInvoices();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
