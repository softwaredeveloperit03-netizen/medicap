import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dispatch-report',
  templateUrl: './dispatch-report.component.html',
  styleUrls: ['./dispatch-report.component.css']
})
export class DispatchReportComponent implements OnInit {
  isView = false;
  isNew = false;
 

  results;
  selectedResult=[];
  client;
  from_date='';
  to_date='';
  client_code='';
  gst_app: any;
  gst_type: any;
  bill_curr: any;
  pay_mode: any;
  po_date: any;
  po_no: any;
  wholeProducts: any[]=[];
  final_total=0;

  
  constructor(private service: DataAccessService, private fb: FormBuilder) { }

  ngOnInit() {
     
     this.getDispatchProducts();
   }
 

  getDispatchProducts() {
    this.service.get('dispatch.php?type=getSalesOrders').subscribe(response => {
      this.results =  response;
    });
  }
  
   
  onOrderView(index) {
    this.selectedResult=this.results[index];
    this.gst_app = this.selectedResult['gst_app'];
    this.gst_type=this.selectedResult['gst_type'];
    this.bill_curr=this.selectedResult['bill_curr'];
    this.pay_mode=this.selectedResult['pay_mode'];
    this.po_no=this.selectedResult['po_no'];
    this.po_date=this.selectedResult['po_date'];
    this.final_total=this.selectedResult['final_total'];
    console.log(this.selectedResult['sales_data']);
    let data = JSON.parse(this.selectedResult['sales_data']);
    this.wholeProducts= data;
     this.getCalculations();
    this.isView=true;
  }
   

  getCalculations(){
    let total =0;

    for(let i =  0; i< this.wholeProducts.length; i++){

        total = total + this.wholeProducts[i].net_total;

    }

    this.final_total =  total;
  }

  onPDFView(item) {
    const url = this.service.url + 'dispatch.php?type=generateDispatchPDF&order_id=' + item.order_id + '&client_code=' + item.client_code + '&token=' + localStorage.getItem('token');
    window.open(url, "_blank");
  }

}
