import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-rawchecking',
  templateUrl: './rawchecking.component.html',
  styleUrls: ['./rawchecking.component.css']
})
export class RawcheckingComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];

  calculated = {
    gross: 0,
    disc: 0,
    taxable: 0,
    other: 0,
    round: 0,
    net: 0,
  };
  gst_app: any;
  gst_type: any;
  bill_curr: any;
  pay_mode: any;
  po_no: any;
  po_date: any;
  final_total: any;
  wholeProducts: any;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSales();
  }
  getPendingSales(){
    this.service.get('dispatch/sales.php?type=getPendingOrdersRaw').subscribe(response=>{
      this.results=response;
    });
  }

  getCalculations(){
    let data = this.selectedResult['materials'];
    this.calculated={
      gross: 0,
      disc: 0,
      taxable: 0,
      other: 0,
      round: 0,
      net: 0,
    }
    data.map(res=>{
      console.log(res.disc_total);
      this.calculated.gross += parseFloat(res.gross_total);
      // console.log(res);
      this.calculated.disc += parseFloat(res.disc_total);
      this.calculated.taxable += parseFloat(res.taxable);
      this.calculated.other += parseFloat(res.other);
      this.calculated.net += parseFloat(res.net_total);
    });
    console.log(this.calculated); 
  }

  view(index){
    this.selectedResult=this.results[index];  
    this.getCalculations();
    this.selectedResult=this.results[index];
    this.gst_app=this.selectedResult['gst_app'];
    this.gst_type=this.selectedResult['gst_type'];
    this.bill_curr=this.selectedResult['bill_curr'];
    this.pay_mode=this.selectedResult['pay_mode'];
    this.po_no=this.selectedResult['po_no'];
    this.po_date=this.selectedResult['po_date'];
    this.final_total=this.selectedResult['final_total'];
    if(this.selectedResult['sales_data'].length>0){
    this.wholeProducts = JSON.parse(this.selectedResult['sales_data']); 
    }else{
      this.wholeProducts = [];
    }
    console.log(this.selectedResult['sales_data']);
    this.getCalculations();
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
